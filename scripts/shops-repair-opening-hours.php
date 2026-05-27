<?php

/**
 * Repair retailer opening hours on imported shop singles: heading, context, day rows.
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/shops-repair-opening-hours.php
 *
 *   .../shops-repair-opening-hours.php clarks
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI
 */

use App\Directory\ShopLiveSync;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

$dryRun = in_array('dry-run', $args, true) || in_array('--dry-run', $args, true);
$onlySlug = null;
foreach ($args as $arg) {
    if (! is_string($arg) || $arg === '') {
        continue;
    }
    if (in_array($arg, ['dry-run', '--dry-run'], true)) {
        continue;
    }
    $onlySlug = sanitize_title($arg);
}

$result = ShopLiveSync::repairRetailerOpeningHours($dryRun, $onlySlug);

WP_CLI::success(sprintf(
    'Done. repaired=%d skipped=%d failed=%d%s',
    $result['repaired'],
    $result['skipped'],
    $result['failed'],
    $dryRun ? ' (dry-run)' : ''
));
