<?php

/**
 * Sync shop pages from culversquare.co.uk shopping retailers.
 *
 * Creates missing local culvers_shop posts, then updates intro/split copy,
 * hero logo + image, store details, opening hours, and taxonomies.
 *
 * From WordPress root (app/public):
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/shops-sync-from-live.php dry-run
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/shops-sync-from-live.php
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/shops-sync-from-live.php clarks
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/shops-sync-from-live.php create-only clarks
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Directory\ShopLiveSync;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

$dryRun = in_array('dry-run', $args, true) || in_array('--dry-run', $args, true);
$createOnly = in_array('create-only', $args, true) || in_array('--create-only', $args, true);
$useLocalMedia = in_array('use-local-media', $args, true) || in_array('--use-local-media', $args, true);
$onlyLocal = null;
foreach ($args as $arg) {
    if (! is_string($arg) || $arg === '') {
        continue;
    }
    if (in_array($arg, ['dry-run', '--dry-run', 'create-only', '--create-only', 'use-local-media', '--use-local-media'], true)) {
        continue;
    }
    $onlyLocal = sanitize_title($arg);
}

$userId = (int) apply_filters('culvers_shops_sync_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

$result = ShopLiveSync::run($dryRun, $onlyLocal, true, ! $createOnly, $useLocalMedia);

WP_CLI::success(sprintf(
    'Done. created=%d updated=%d skipped=%d failed=%d%s',
    $result['created'],
    $result['updated'],
    $result['skipped'],
    $result['failed'],
    $dryRun ? ' (dry-run)' : ''
));
