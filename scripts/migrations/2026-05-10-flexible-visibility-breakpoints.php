<?php

/**
 * Migrate legacy flexible visibility_mobile === 'hidden' → visibility_hide_phone (boolean meta).
 *
 * Run:
 *   cd app/public && ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/migrations/2026-05-10-flexible-visibility-breakpoints.php
 *
 * Idempotent: only sets visibility_hide_phone when legacy meta is `hidden` and phone hide is still unset/false.
 */

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI (wp eval-file) so WordPress is bootstrapped under CLI.\n");
    exit(1);
}

global $wpdb;

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is interpolated safely.
$rows = $wpdb->get_results(
    "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
     WHERE meta_key REGEXP '^components_[0-9]+_visibility_mobile$'
       AND meta_value = 'hidden'"
);
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

$updated = 0;

foreach ($rows as $row) {
    if (! preg_match('/^components_(\d+)_visibility_mobile$/', $row->meta_key, $m)) {
        continue;
    }
    $index = $m[1];
    $postId = (int) $row->post_id;
    $phoneKey = 'components_' . $index . '_visibility_hide_phone';
    $existing = get_post_meta($postId, $phoneKey, true);
    if ($existing === '1' || $existing === 1 || $existing === true) {
        continue;
    }

    update_post_meta($postId, $phoneKey, '1');
    ++$updated;
}

echo sprintf("Done. Rows migrated: %d.\n", $updated);
