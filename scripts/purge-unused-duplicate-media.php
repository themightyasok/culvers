<?php

/**
 * Delete Media Library image attachments that are unused AND share a title with
 * another attachment (duplicate titles). Keeps the used copy; if none are used,
 * keeps the highest ID.
 *
 * Dry-run by default. Pass --apply to delete.
 *
 *   wp eval-file wp-content/themes/culvers/scripts/purge-unused-duplicate-media.php
 *   wp eval-file wp-content/themes/culvers/scripts/purge-unused-duplicate-media.php -- --apply
 *
 * @phpstan-ignore-file
 */

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI (wp eval-file).\n");
    exit(1);
}

$argvList = $_SERVER['argv'] ?? [];
$apply = in_array('--apply', $argvList, true)
    || in_array('apply', $argvList, true)
    || getenv('CULVERS_PURGE_APPLY') === '1';

global $wpdb;

/** @var array<int, true> $used */
$used = [];

$mark = static function (int $id) use (&$used): void {
    if ($id > 0) {
        $used[$id] = true;
    }
};

$thumbs = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id'");
foreach ($thumbs as $raw) {
    $mark((int) $raw);
}

$mega = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_culvers_mega_preview_attachment_id'");
foreach ($mega as $raw) {
    $mark((int) $raw);
}

$metaRows = $wpdb->get_results(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE '%\"ID\"%' OR meta_value REGEXP '^[0-9]+$'",
    ARRAY_A
);
foreach ($metaRows ?: [] as $row) {
    $val = $row['meta_value'] ?? '';
    if (is_numeric($val)) {
        $mark((int) $val);
        continue;
    }
    if (! is_string($val) || $val === '') {
        continue;
    }
    if (preg_match_all('/s:2:\"ID\";i:(\d+);/', $val, $m)) {
        foreach ($m[1] as $id) {
            $mark((int) $id);
        }
    }
    if (preg_match_all('/\"ID\";i:(\d+);/', $val, $m2)) {
        foreach ($m2[1] as $id) {
            $mark((int) $id);
        }
    }
}

$options = $wpdb->get_results(
    "SELECT option_value FROM {$wpdb->options}
     WHERE option_name LIKE 'options_%' OR option_name LIKE 'theme_mods_%' OR option_name LIKE 'culvers_%'",
    ARRAY_A
);
foreach ($options ?: [] as $row) {
    $val = (string) ($row['option_value'] ?? '');
    if (preg_match_all('/s:2:\"ID\";i:(\d+);/', $val, $m)) {
        foreach ($m[1] as $id) {
            $mark((int) $id);
        }
    }
}

$attachments = $wpdb->get_results(
    "SELECT ID, post_title FROM {$wpdb->posts}
     WHERE post_type='attachment' AND post_mime_type LIKE 'image/%'
     ORDER BY ID ASC",
    ARRAY_A
);

/** @var array<string, list<array{id:int, title:string}>> $byTitle */
$byTitle = [];
foreach ($attachments ?: [] as $row) {
    $title = trim((string) ($row['post_title'] ?? ''));
    if ($title === '') {
        continue;
    }
    $byTitle[$title][] = [
        'id' => (int) $row['ID'],
        'title' => $title,
    ];
}

$toDelete = [];
foreach ($byTitle as $title => $group) {
    if (count($group) < 2) {
        continue;
    }
    $usedInGroup = [];
    $unusedInGroup = [];
    foreach ($group as $item) {
        if (isset($used[$item['id']])) {
            $usedInGroup[] = $item;
        } else {
            $unusedInGroup[] = $item;
        }
    }
    if ($unusedInGroup === []) {
        continue;
    }
    if ($usedInGroup !== []) {
        foreach ($unusedInGroup as $item) {
            $toDelete[] = $item;
        }
        continue;
    }
    usort($unusedInGroup, static fn (array $a, array $b): int => $b['id'] <=> $a['id']);
    array_shift($unusedInGroup);
    foreach ($unusedInGroup as $item) {
        $toDelete[] = $item;
    }
}

WP_CLI::log(sprintf(
    '%s: %d unused duplicate image attachment(s) across duplicate titles.',
    $apply ? 'APPLY' : 'DRY-RUN',
    count($toDelete)
));

$deleted = 0;
foreach ($toDelete as $item) {
    WP_CLI::log(($apply ? 'DELETE' : 'would delete') . " #{$item['id']} — {$item['title']}");
    if ($apply) {
        $result = wp_delete_attachment($item['id'], true);
        if ($result) {
            $deleted++;
        } else {
            WP_CLI::warning("Failed to delete #{$item['id']}");
        }
    }
}

if ($apply) {
    WP_CLI::success("Deleted {$deleted} unused duplicate attachment(s).");
} else {
    WP_CLI::success('Dry-run complete. Re-run with -- --apply to delete.');
}
