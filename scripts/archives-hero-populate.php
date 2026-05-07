<?php

/**
 * Seeds the four directory archive heroes (shops, eat-drink, whats-on,
 * careers) with imagery + headline copy from the Figma developer release.
 *
 * Same CLI pattern as `homepage-populate-flexible.php` and
 * `shops-directory-populate.php` — run from WP root via Local wrapper:
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/archives-hero-populate.php
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Directory\DirectoryArchiveHeroPopulate;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

DirectoryArchiveHeroPopulate::runSeed(true);
