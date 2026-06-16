<?php

/**
 * Delete attachments listed in a media-library-audit report (unused + parent-only).
 *
 * Local use only — reads JSON from audit-media-library-usage.php output.
 *
 *   cd app/public && ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/delete-unused-media-from-audit.php -- \
 *     /Users/admin/Work/Society/Culvers/media-library-audit/report.json
 *
 * @phpstan-ignore-file
 */

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI (wp eval-file).\n");
    exit(1);
}

$argv = $_SERVER['argv'] ?? [];
$reportPath = '';
foreach ($argv as $i => $arg) {
    if ($arg === '--' && isset($argv[$i + 1])) {
        $reportPath = (string) $argv[$i + 1];
        break;
    }
}

if ($reportPath === '' || ! is_readable($reportPath)) {
    \WP_CLI::error('Pass readable report JSON path after --');
}

$report = json_decode((string) file_get_contents($reportPath), true);
if (! is_array($report)) {
    \WP_CLI::error('Invalid report JSON');
}

/** @var list<int> $ids */
$ids = [];
foreach (['unused_attachments', 'parent_only_attachments'] as $bucket) {
    foreach ($report[$bucket] ?? [] as $item) {
        $id = (int) ($item['id'] ?? 0);
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
}
$ids = array_values($ids);
sort($ids, SORT_NUMERIC);

$before = (int) wp_count_posts('attachment')->inherit;
\WP_CLI::log(sprintf('Report: %d IDs to delete (unused + parent-only). Library before: %d inherit attachments.', count($ids), $before));

$deleted = 0;
$skipped = 0;
$failed = 0;
$bytes = 0;

foreach ($ids as $id) {
    $post = get_post($id);
    if (! $post instanceof WP_Post || $post->post_type !== 'attachment') {
        $skipped++;
        continue;
    }

    $file = get_attached_file($id);
    if (is_string($file) && is_readable($file)) {
        $bytes += (int) filesize($file);
    }

    $result = wp_delete_attachment($id, true);
    if ($result === false || $result === null) {
        $failed++;
        \WP_CLI::warning("Failed to delete attachment #{$id}");
        continue;
    }

    $deleted++;
}

$after = (int) wp_count_posts('attachment')->inherit;
\WP_CLI::success(sprintf(
    'Done. Deleted %d, skipped %d (missing), failed %d. Library after: %d (removed %d). ~%s primary files.',
    $deleted,
    $skipped,
    $failed,
    $after,
    $before - $after,
    size_format($bytes)
));
