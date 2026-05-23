<?php

/**
 * Sync eat & drink venue pages from culversquare.co.uk dining retailers.
 *
 * Updates intro/split copy, hero logo, store details (phone, address, brand social).
 *
 * From WordPress root (app/public):
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/eat-drink-sync-from-live.php dry-run
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/eat-drink-sync-from-live.php
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/eat-drink-sync-from-live.php greggs
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI
 */

use App\Directory\EatDrinkLiveSync;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

$dryRun = in_array('dry-run', $args, true) || in_array('--dry-run', $args, true);
$onlyLocal = null;
foreach ($args as $arg) {
    if (! is_string($arg) || $arg === '' || $arg === 'dry-run' || $arg === '--dry-run') {
        continue;
    }
    $onlyLocal = sanitize_title($arg);
}

$userId = (int) apply_filters('culvers_eat_drink_sync_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

$result = EatDrinkLiveSync::run($dryRun, $onlyLocal);

WP_CLI::success(sprintf(
    'Done. updated=%d skipped=%d failed=%d%s',
    $result['updated'],
    $result['skipped'],
    $result['failed'],
    $dryRun ? ' (dry-run)' : ''
));
