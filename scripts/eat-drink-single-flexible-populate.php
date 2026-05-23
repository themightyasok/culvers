<?php

/**
 * Full Greggs-shaped flexible stack on every culvers_eat_drink single, with live copy,
 * store details, centre map (from Plan My Visit), and opening hours.
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/eat-drink-single-flexible-populate.php
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI
 */

use App\Directory\EatDrinkSingleFlexiblePopulate;

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

$userId = (int) apply_filters('culvers_eat_drink_single_populate_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

$result = EatDrinkSingleFlexiblePopulate::runAll($dryRun, $onlyLocal);

WP_CLI::success(sprintf(
    'Done. updated=%d failed=%d%s',
    $result['updated'],
    $result['failed'],
    $dryRun ? ' (dry-run)' : ''
));
