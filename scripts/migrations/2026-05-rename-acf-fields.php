<?php

/**
 * One-shot ACF flexible-content meta_key rename — aligns existing post meta with the
 * new subject-prefixed field naming rule (one prefix per layout, e.g. `cards_*`,
 * `scroller_*`, `details_*`, `info_*`, `hours_*`, `content_*`, `video_*`).
 *
 * Run via the Local environment wrapper:
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/migrations/2026-05-rename-acf-fields.php
 *
 * Idempotent: only acts when matching old keys still exist for a post + row.
 *
 * @phpstan-type RenameRules array<string, string>
 */

if (! defined('WPINC')) {
    fwrite(STDERR, "Run via wp eval-file (WordPress must be bootstrapped).\n");
    exit(1);
}

global $wpdb;

/**
 * Top-level layout field renames: old_field => new_field, applied as `components_{i}_{old}` → `components_{i}_{new}`
 * (and recursively to any sub-field meta with the same prefix, including `_components_{i}_{old}` field-key rows).
 *
 * @var array<string, array<string, string>> $TOP_LEVEL
 */
$TOP_LEVEL = [
    'three_card_block' => [
        'block_heading' => 'cards_heading',
        'block_subheading' => 'cards_subheading',
        'block_heading_level' => 'cards_heading_level',
        'block_body' => 'cards_body',
        'three_cards' => 'cards_items',
        'blog_category_tabs' => 'cards_blog_categories',
        'blog_posts_per_category' => 'cards_blog_per_category',
        'blog_view_all_url' => 'cards_view_all_url',
        'blog_view_all_label' => 'cards_view_all_label',
    ],
    'horizontal_scroller' => [
        'header_text' => 'scroller_header_text',
        'header_alignment' => 'scroller_header_alignment',
        'header_text_alignment' => 'scroller_header_text_alignment',
        'subheading_text' => 'scroller_subheading_text',
        'body_text' => 'scroller_body_text',
        'intro_flush_to_content' => 'scroller_intro_flush',
        'intro_flush' => 'scroller_intro_flush',
        'header_text_color' => 'scroller_header_text_color',
        'header_text_size' => 'scroller_header_text_size',
        'header_text_weight' => 'scroller_header_text_weight',
        'subheading_text_color' => 'scroller_subheading_text_color',
        'subheading_text_size' => 'scroller_subheading_text_size',
        'subheading_text_weight' => 'scroller_subheading_text_weight',
        'body_text_color' => 'scroller_body_text_color',
        'body_text_size' => 'scroller_body_text_size',
        'body_text_weight' => 'scroller_body_text_weight',
        'button_text' => 'scroller_button_text',
        'button_link' => 'scroller_button_link',
        'button_variant' => 'scroller_button_variant',
        'button_size' => 'scroller_button_size',
        'button_show_arrow' => 'scroller_button_show_arrow',
        'speed' => 'scroller_speed',
        'disabled' => 'scroller_disabled',
        'item_spacing' => 'scroller_item_spacing',
        'remove_vertical_padding' => 'scroller_remove_vertical_padding',
        'item_kicker_size' => 'scroller_item_kicker_size',
        'item_kicker_weight' => 'scroller_item_kicker_weight',
        'item_heading_size' => 'scroller_item_heading_size',
        'item_heading_weight' => 'scroller_item_heading_weight',
        'item_body_size' => 'scroller_item_body_size',
        'item_body_weight' => 'scroller_item_body_weight',
        'header_padding_top' => 'scroller_header_padding_top',
        'header_padding_bottom' => 'scroller_header_padding_bottom',
        'subheader_padding_top' => 'scroller_subheader_padding_top',
        'subheader_padding_bottom' => 'scroller_subheader_padding_bottom',
        'body_padding_top' => 'scroller_body_padding_top',
        'body_padding_bottom' => 'scroller_body_padding_bottom',
        'item_kicker_padding_top' => 'scroller_item_kicker_padding_top',
        'item_kicker_padding_bottom' => 'scroller_item_kicker_padding_bottom',
        'item_heading_padding_top' => 'scroller_item_heading_padding_top',
        'item_heading_padding_bottom' => 'scroller_item_heading_padding_bottom',
        'item_body_padding_top' => 'scroller_item_body_padding_top',
        'item_body_padding_bottom' => 'scroller_item_body_padding_bottom',
        'scroll_cards' => 'scroller_items',
    ],
    'video_block' => [
        'video' => 'video_file',
        'poster' => 'video_poster',
        'play_button_label' => 'video_play_label',
    ],
    'info_block' => [
        'heading' => 'info_heading',
        'subheading' => 'info_subheading',
        'body' => 'info_body',
        'heading_semantic_level' => 'info_heading_level',
    ],
    'opening_hours' => [
        'heading' => 'hours_heading',
        'subheading' => 'hours_subheading',
        'body' => 'hours_body',
        'heading_semantic_level' => 'hours_heading_level',
        'graphic_left' => 'hours_graphic_left',
        'graphic_right' => 'hours_graphic_right',
    ],
    'content_section' => [
        'heading' => 'content_heading',
        'body' => 'content_body',
        'heading_semantic_level' => 'content_heading_level',
    ],
    'shop_store_details' => [
        'contact_label' => 'details_contact_label',
        'contact_phone' => 'details_contact_phone',
        'address_label' => 'details_address_label',
        'address_text' => 'details_address',
        'social_label' => 'details_social_label',
        'social_instagram_url' => 'details_instagram_url',
        'social_instagram_handle' => 'details_instagram_handle',
    ],
];

/**
 * Repeater sub-field renames: applied as `components_{i}_{repeater}_{N}_{old}` → `..._{new}`
 * after the parent repeater itself is renamed. Keyed by layout, then by NEW repeater field name.
 *
 * @var array<string, array<string, array<string, string>>> $REPEATER_SUB
 */
$REPEATER_SUB = [
    'horizontal_scroller' => [
        'scroller_items' => [
            'image' => 'item_image',
            'image_alt_text' => 'item_image_alt',
        ],
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

/**
 * Rename every meta_key starting with $oldPrefix (exact or `oldPrefix_*`) to use $newPrefix
 * for a single post. Safely handles a pre-existing destination key (deletes the stale source).
 *
 * Idempotency / collision safety: when the new prefix begins with the old prefix followed by
 * an underscore (e.g. `video → video_file`), child-prefix matches are skipped. Otherwise a
 * second run would erroneously absorb already-renamed siblings (`video_poster` → `video_file_poster`).
 */
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

        $subRules = $REPEATER_SUB[$layout] ?? [];
        foreach ($subRules as $repeater => $subMap) {
            $count = (int) get_post_meta($postId, "components_{$i}_{$repeater}", true);
            if ($count <= 0) {
                continue;
            }

            for ($n = 0; $n < $count; $n++) {
                foreach ($subMap as $oldSub => $newSub) {
                    $oldPrefix = "components_{$i}_{$repeater}_{$n}_{$oldSub}";
                    $newPrefix = "components_{$i}_{$repeater}_{$n}_{$newSub}";
                    $renamed = $renamePrefixForPost($postId, $oldPrefix, $newPrefix);
                    if ($renamed > 0) {
                        fwrite(STDOUT, "  [{$i}/{$layout}/{$repeater}#{$n}] {$oldSub} → {$newSub} ({$renamed} keys)\n");
                        $totals['renames'] += $renamed;
                    }

                    $oldFieldKeyPrefix = "_components_{$i}_{$repeater}_{$n}_{$oldSub}";
                    $newFieldKeyPrefix = "_components_{$i}_{$repeater}_{$n}_{$newSub}";
                    $renamedFk = $renamePrefixForPost($postId, $oldFieldKeyPrefix, $newFieldKeyPrefix);
                    if ($renamedFk > 0) {
                        fwrite(STDOUT, "  [{$i}/{$layout}/{$repeater}#{$n}] _key {$oldSub} → {$newSub} ({$renamedFk} keys)\n");
                        $totals['renames'] += $renamedFk;
                    }
                }
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
