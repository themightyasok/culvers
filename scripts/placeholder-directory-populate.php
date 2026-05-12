<?php

declare(strict_types=1);

/**
 * Idempotent placeholder content populate for the thin directory CPTs
 * (Eat & Drink, Career, Event, News, Offer). Designed to be invoked via:
 *
 *     ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *       wp eval-file wp-content/themes/culvers/scripts/placeholder-directory-populate.php
 *
 * See {@see App\Directory\PlaceholderDirectorySeeder} for the data contract.
 */

if (! class_exists(\App\Directory\PlaceholderDirectorySeeder::class)) {
    if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI')) {
        \WP_CLI::error('PlaceholderDirectorySeeder not autoloaded — composer install missing?');
    }

    return;
}

$report = \App\Directory\PlaceholderDirectorySeeder::runSeed();

if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI')) {
    foreach ($report as $cpt => $row) {
        \WP_CLI::log(sprintf('• %-10s %d created · %d updated', $cpt, $row['created'], $row['updated']));
    }
    \WP_CLI::success('Placeholder directory content populated.');
}
