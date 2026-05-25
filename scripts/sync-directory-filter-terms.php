<?php

/**
 * Sync directory filter taxonomies to Figma + re-assign Eat & Drink venue types.
 *
 *     ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *       wp eval-file wp-content/themes/culvers/scripts/sync-directory-filter-terms.php
 */

if (! class_exists(\App\Directory\ShopTaxonomySeeder::class)) {
    return;
}

\App\Directory\ShopTaxonomySeeder::syncNow();
\App\Directory\EatDrinkTaxonomySeeder::syncNow();

if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI')) {
    \WP_CLI::success('Directory filter terms synced (shops + eat & drink).');
}
