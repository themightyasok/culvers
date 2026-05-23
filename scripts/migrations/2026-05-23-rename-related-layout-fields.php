<?php

/**
 * Rename shared `related_*` flexible fields on shop-related layouts to layout-distinct prefixes.
 *
 * Run via the Local environment wrapper:
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/migrations/2026-05-23-rename-related-layout-fields.php
 *
 * @see app/Components/shop_related_shops.php (`shops_related_*`)
 * @see app/Components/shop_related_eat_drink.php (`eat_drink_related_*`)
 */

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI (wp eval-file) so WordPress is bootstrapped under CLI.\n");
    exit(1);
}

global $wpdb;

/** @var array<string, array<string, string>> $TOP_LEVEL */
$TOP_LEVEL = [
    'shop_related_shops' => [
        'related_heading' => 'shops_related_heading',
        'related_heading_level' => 'shops_related_heading_level',
        'related_view_all_url' => 'shops_related_view_all_url',
        'related_view_all_label' => 'shops_related_view_all_label',
        'related_shop_posts' => 'shops_related_posts',
    ],
    'shop_related_eat_drink' => [
        'related_heading' => 'eat_drink_related_heading',
        'related_heading_level' => 'eat_drink_related_heading_level',
        'related_view_all_url' => 'eat_drink_related_view_all_url',
        'related_view_all_label' => 'eat_drink_related_view_all_label',
        'related_eat_drink_posts' => 'eat_drink_related_posts',
    ],
];

/** Replace the leading prefix of a meta_key. */
$replacePrefix = static function (string $metaKey, string $oldPrefix, string $newPrefix): string {
    if ($metaKey === $oldPrefix) {
        return $newPrefix;
    }

    if (str_starts_with($metaKey, $oldPrefix . '_')) {
        return $newPrefix . substr($metaKey, strlen($oldPrefix));
    }

    return $metaKey;
};

$renamePrefixForPost = static function (int $postId, string $oldPrefix, string $newPrefix) use ($wpdb, $replacePrefix): int {
    $newSwallowsOld = str_starts_with($newPrefix, $oldPrefix . '_');

    if ($newSwallowsOld) {
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_id, meta_key FROM {$wpdb->postmeta}
                 WHERE post_id = %d AND meta_key = %s",
                $postId,
                $oldPrefix
            )
        );
    } else {
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_id, meta_key FROM {$wpdb->postmeta}
                 WHERE post_id = %d
                   AND (meta_key = %s OR meta_key LIKE %s)",
                $postId,
                $oldPrefix,
                $wpdb->esc_like($oldPrefix . '_') . '%'
            )
        );
    }

    if (! is_array($rows) || $rows === []) {
        return 0;
    }

    $renamed = 0;
    foreach ($rows as $row) {
        $newKey = $replacePrefix((string) $row->meta_key, $oldPrefix, $newPrefix);
        if ($newKey === $row->meta_key) {
            continue;
        }

        $existing = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
                $postId,
                $newKey
            )
        );

        if ($existing > 0) {
            $wpdb->delete($wpdb->postmeta, ['meta_id' => (int) $row->meta_id]);
            continue;
        }

        $wpdb->update(
            $wpdb->postmeta,
            ['meta_key' => $newKey],
            ['meta_id' => (int) $row->meta_id]
        );
        $renamed++;
    }

    return $renamed;
};

/** @return list<int> */
$getPostIdsWithFlexibleContent = static function () use ($wpdb): array {
    $ids = $wpdb->get_col(
        "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'components'"
    );

    return array_map('intval', is_array($ids) ? $ids : []);
};

$totals = ['posts' => 0, 'rows' => 0, 'renames' => 0];

foreach ($getPostIdsWithFlexibleContent() as $postId) {
    $rawLayouts = get_post_meta($postId, 'components', true);
    $layouts = is_string($rawLayouts) ? maybe_unserialize($rawLayouts) : $rawLayouts;
    if (! is_array($layouts) || $layouts === []) {
        continue;
    }

    $totals['posts']++;
    fwrite(STDOUT, "Post #{$postId}\n");

    foreach ($layouts as $i => $layout) {
        if (! is_string($layout) || $layout === '') {
            continue;
        }
        $totals['rows']++;

        $rules = $TOP_LEVEL[$layout] ?? [];
        foreach ($rules as $oldField => $newField) {
            $oldPrefix = "components_{$i}_{$oldField}";
            $newPrefix = "components_{$i}_{$newField}";
            $renamed = $renamePrefixForPost($postId, $oldPrefix, $newPrefix);
            if ($renamed > 0) {
                fwrite(STDOUT, "  [{$i}/{$layout}] {$oldField} → {$newField} ({$renamed} keys)\n");
                $totals['renames'] += $renamed;
            }

            $oldFieldKeyPrefix = "_components_{$i}_{$oldField}";
            $newFieldKeyPrefix = "_components_{$i}_{$newField}";
            $renamedFk = $renamePrefixForPost($postId, $oldFieldKeyPrefix, $newFieldKeyPrefix);
            if ($renamedFk > 0) {
                fwrite(STDOUT, "  [{$i}/{$layout}] _key {$oldField} → {$newField} ({$renamedFk} keys)\n");
                $totals['renames'] += $renamedFk;
            }
        }
    }
}

if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}
if (function_exists('clean_post_cache')) {
    foreach ($getPostIdsWithFlexibleContent() as $postId) {
        clean_post_cache($postId);
    }
}

fwrite(STDOUT, sprintf(
    "\nDone. Posts: %d  Rows scanned: %d  Keys renamed: %d\n",
    $totals['posts'],
    $totals['rows'],
    $totals['renames']
));
