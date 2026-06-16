<?php

/**
 * One-shot migration for the centre-map single-source refactor.
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *       wp-content/themes/culvers/scripts/centre-map-single-source-migrate.php
 *
 * Add `dry-run` to preview without writing:
 *
 *   .../centre-map-single-source-migrate.php dry-run
 *
 * What it does (idempotent — safe to re-run, e.g. on staging after a DB push):
 *   1. Deletes every per-post `centre_map_categories` repeater meta row. The
 *      list now lives in code ({@see App\CentreMap\ShopCentreMapDefaults}), so
 *      the stored copies are dead weight that can only drift out of sync.
 *   2. Renames the `culvers_shop_category` term "Services" → "Speciality"
 *      (slug services → speciality) in line with the bug list (R25). No
 *      redirects — the slug changes in place.
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

/** @var array<int, string> $args */
$cliArgs = $args ?? [];
$dryRun = in_array('dry-run', $cliArgs, true) || in_array('--dry-run', $cliArgs, true);

global $wpdb;

// 1. Drop dead per-post centre_map_categories meta (count, field refs, sub-rows).
$like = '%centre_map_categories%';
$count = (int) $wpdb->get_var(
    $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", $like)
);

if ($dryRun) {
    \WP_CLI::log(sprintf('[dry-run] would delete %d centre_map_categories meta row(s).', $count));
} else {
    $deleted = (int) $wpdb->query(
        $wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", $like)
    );
    \WP_CLI::log(sprintf('Deleted %d centre_map_categories meta row(s).', $deleted));
}

// 2. Rename shop category term Services → Speciality (R25).
$services = get_term_by('slug', 'services', 'culvers_shop_category');
$speciality = get_term_by('slug', 'speciality', 'culvers_shop_category');

if ($speciality instanceof WP_Term) {
    \WP_CLI::log('Speciality term already present — no rename needed.');
} elseif (! $services instanceof WP_Term) {
    \WP_CLI::warning('No "services" shop category term found — skipping rename.');
} elseif ($dryRun) {
    \WP_CLI::log(sprintf(
        '[dry-run] would rename term %d "services" → "speciality" / "Speciality" (%d posts).',
        $services->term_id,
        $services->count
    ));
} else {
    $result = wp_update_term($services->term_id, 'culvers_shop_category', [
        'name' => 'Speciality',
        'slug' => 'speciality',
    ]);

    if (is_wp_error($result)) {
        \WP_CLI::error('Term rename failed: ' . $result->get_error_message());
    }

    \WP_CLI::log(sprintf('Renamed term %d → "Speciality" (slug speciality).', $services->term_id));
}

\WP_CLI::success($dryRun ? 'Dry run complete — no changes written.' : 'Centre-map single-source migration complete.');
