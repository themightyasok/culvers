<?php

/**
 * Seeds `/shops/` demo retailers from Figma Developer Release MCP assets + aligns Shop mega-menu URLs.
 *
 * Same CLI pattern as `homepage-populate-flexible.php` — run from WP root via Local wrapper:
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file wp-content/themes/culvers/scripts/shops-directory-populate.php
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Directory\ShopDirectoryPopulate;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

ShopDirectoryPopulate::runSeed(true);
