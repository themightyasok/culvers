<?php

/**
 * Remove stored ACF post meta for flexible-row chrome that no longer drives the front end
 * ({@see \App\Helpers\ComponentLayoutChrome}): `component_width` and all `background_*` keys.
 *
 * ACF stores flexible sub-fields as flat postmeta: `components_{row}_{field}` plus optional
 * `_components_{row}_{field}` field-key references — both are deleted.
 *
 * Run:
 *   cd app/public && ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/migrations/2026-05-18-drop-flexible-layout-chrome-meta.php
 *
 * Idempotent: deleting missing keys is a no-op.
 */

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI (wp eval-file) so WordPress is bootstrapped under CLI.\n");
    exit(1);
}

global $wpdb;

// Tail after `components_{index}_` for chrome fields (must match ComponentRegistry background + width).
$chromeSuffix = '(component_width|background_type|background_color|background_gradient_color_from'
    . '|background_gradient_color_to|background_gradient_angle|background_image|background_image_color'
    . '|background_parallax|background_video|background_video_youtube_url|background_overlay'
    . '|background_overlay_opacity)';

// Values: components_0_background_type — references: _components_0_background_type
$pattern = '^_?components_[0-9]+_' . $chromeSuffix . '$';

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is interpolated safely.
$deleted = (int) $wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key REGEXP %s",
        $pattern
    )
);
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

WP_CLI::success(sprintf('Deleted flexible layout chrome meta rows: %d.', $deleted));
