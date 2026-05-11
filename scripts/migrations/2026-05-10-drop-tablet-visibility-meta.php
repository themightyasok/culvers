<?php

/**
 * Remove legacy flexible-row meta keys `visibility_hide_tablet` (three-band visibility retired).
 *
 * ACF stores flexible sub-fields as flat postmeta: `components_{row}_visibility_hide_tablet`.
 *
 * Run:
 *   cd app/public && ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/migrations/2026-05-10-drop-tablet-visibility-meta.php
 */

if (! defined('WPINC')) {
    fwrite(STDERR, "Run via wp eval-file (WordPress must be bootstrapped).\n");
    exit(1);
}

global $wpdb;

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is interpolated safely.
$deleted = (int) $wpdb->query(
    "DELETE FROM {$wpdb->postmeta}
     WHERE meta_key REGEXP '^components_[0-9]+_visibility_hide_tablet$'"
);
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

echo sprintf("Done. Deleted visibility_hide_tablet meta rows: %d.\n", $deleted);
