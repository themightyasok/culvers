<?php

/**
 * Repair empty centre_map repeaters on shop / eat-drink singles.
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *       wp-content/themes/culvers/scripts/shops-repair-centre-map.php
 *
 * Optional slugs (defaults to the five deploy shops + cosmic-tattoo):
 *
 *   .../shops-repair-centre-map.php clarks nerd-base dry-run
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\CentreMap\ShopCentreMapDefaults;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

if (! function_exists('update_field') || ! function_exists('get_field')) {
    \WP_CLI::error('ACF is required.');
}

$cliArgs = $args ?? [];
$dryRun = in_array('dry-run', $cliArgs, true) || in_array('--dry-run', $cliArgs, true);
$slugs = array_values(array_filter($cliArgs, static fn (string $arg): bool => ! in_array($arg, ['dry-run', '--dry-run'], true)));

if ($slugs === []) {
    $slugs = [
        'clarks',
        'fraser-hart',
        'colchester-aesthetics-beauty',
        'nerd-base',
        'phoenix-vapes',
        'cosmic-tattoo',
    ];
}

$repaired = 0;
$skipped = 0;

foreach ($slugs as $slug) {
    $post = get_page_by_path($slug, OBJECT, 'culvers_shop');
    if (! $post instanceof WP_Post) {
        \WP_CLI::warning(sprintf('skip — no culvers_shop "%s"', $slug));
        ++$skipped;
        continue;
    }

    $postId = (int) $post->ID;
    $components = get_field('components', $postId);
    if (! is_array($components) || $components === []) {
        \WP_CLI::warning(sprintf('skip %s — no components', $slug));
        ++$skipped;
        continue;
    }

    $before = 0;
    $needs = false;
    foreach ($components as $row) {
        if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'centre_map') {
            continue;
        }
        $cats = $row['centre_map_categories'] ?? [];
        $before = is_array($cats) ? count($cats) : 0;
        if ($before === 0) {
            $needs = true;
        }
        break;
    }

    if (! $needs) {
        \WP_CLI::log(sprintf('skip %s — centre_map already has %d categories', $slug, $before));
        ++$skipped;
        continue;
    }

    $components = ShopCentreMapDefaults::mergeIntoComponents($components, $postId);
    $after = count(ShopCentreMapDefaults::categoryRows());

    if ($dryRun) {
        \WP_CLI::log(sprintf('[dry-run] would repair %s (%d → %d categories)', $slug, $before, $after));
        ++$repaired;
        continue;
    }

    delete_field('components', $postId);
    update_field('components', $components, $postId);
    \WP_CLI::log(sprintf('repaired %s (%d → %d categories)', $slug, $before, $after));
    ++$repaired;
}

if ($dryRun) {
    \WP_CLI::success(sprintf('Dry run — would repair %d shop(s), skipped %d.', $repaired, $skipped));
} else {
    \WP_CLI::success(sprintf('Done — repaired %d shop(s), skipped %d.', $repaired, $skipped));
}
