<?php

/**
 * Import live offers + what's-on content from culversquare.co.uk into local CPTs.
 *
 *   cd app/public && wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *       wp-content/themes/culvers/scripts/directory-offers-whats-on-import.php
 *
 * Dry run:
 *
 *   ... eval-file .../directory-offers-whats-on-import.php dry-run
 *
 * Prune demo posts not touched by the import (moves to draft):
 *
 *   ... eval-file .../directory-offers-whats-on-import.php prune
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Directory\DirectoryLiveImport;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

if (! function_exists('update_field')) {
    \WP_CLI::error('ACF is required.');
}

$cliArgs = $args ?? [];
$dryRun = in_array('dry-run', $cliArgs, true) || in_array('--dry-run', $cliArgs, true);
$prune = in_array('prune', $cliArgs, true) || in_array('--prune', $cliArgs, true);

wp_set_current_user(1);

$importer = new DirectoryLiveImport();
$counts = $importer->run($dryRun, $prune);

\WP_CLI::success(sprintf(
    'Import complete%s — offers: %d, events: %d, news: %d%s',
    $dryRun ? ' (dry-run)' : '',
    $counts['offers'],
    $counts['events'],
    $counts['news'],
    $prune ? ', pruned: ' . $counts['pruned'] : ''
));
