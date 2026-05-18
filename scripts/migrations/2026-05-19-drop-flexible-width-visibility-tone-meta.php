<?php

/**
 * Remove legacy post meta for ACF fields removed from the flexible layout registry:
 * `component_width`, `body_text_tone`, visibility toggles, and legacy `visibility_mobile`;
 * plus page-level `full_screen_scrolling` (removed from the theme — meta cleanup only).
 *
 * Run:
 *   cd app/public && ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/migrations/2026-05-19-drop-flexible-width-visibility-tone-meta.php
 */

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI (wp eval-file) so WordPress is bootstrapped under CLI.\n");
    exit(1);
}

global $wpdb;

$rowSuffix = '(component_width|body_text_tone|visibility_hide_phone|visibility_hide_desktop|visibility_mobile)';
$rowPattern = '^_?components_[0-9]+_' . $rowSuffix . '$';

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is interpolated safely.
$deletedRows = (int) $wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key REGEXP %s",
        $rowPattern
    )
);

$deletedPost = (int) $wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('full_screen_scrolling', '_full_screen_scrolling')"
);
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

WP_CLI::success(sprintf(
    'Deleted meta rows: flexible sub-fields %d, full_screen_scrolling %d.',
    $deletedRows,
    $deletedPost
));