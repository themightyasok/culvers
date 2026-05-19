<?php

/**
 * Sync Eat & Drink directory venues to the five Figma retailers and remove placeholders.
 *
 *     ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *       wp eval-file wp-content/themes/culvers/scripts/eat-drink-directory-populate.php
 */

if (! class_exists(\App\Directory\EatDrinkDirectoryPopulate::class)) {
    if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI')) {
        \WP_CLI::error('EatDrinkDirectoryPopulate not autoloaded — composer install missing?');
    }

    return;
}

\App\Directory\EatDrinkDirectoryPopulate::runSeed(true);
