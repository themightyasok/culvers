<?php

/**
 * Assign distinct mega-menu hover preview images to each primary submenu item (matches CulverSquareFigmaPrimaryMenu config).
 *
 * Run from the WordPress root with Local’s environment (same pattern as homepage-populate-flexible.php):
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file wp-content/themes/culvers/scripts/mega-menu-sync-previews.php
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Nav\CulverSquareFigmaPrimaryMenu;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

$n = CulverSquareFigmaPrimaryMenu::cliSyncDistinctChildPreviews();

if ($n <= 0) {
    \WP_CLI::warning(
        'No submenu rows updated. Check that a menu is assigned to the Primary navigation location '
        . 'and that parent/child labels match the Culver Square defaults (or run sync after fixing titles).'
    );
} else {
    \WP_CLI::success(sprintf('Updated mega preview metadata on %d submenu item(s).', $n));
}
