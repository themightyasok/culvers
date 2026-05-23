<?php

/**
 * Apply the Senior Supervisor flexible stack to every culvers_career single.
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/careers-single-flexible-populate.php
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/careers-single-flexible-populate.php dry-run
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI
 */

use App\Directory\CareerSingleFlexiblePopulate;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

$dryRun = in_array('dry-run', $args, true) || in_array('--dry-run', $args, true);
$onlySlug = null;
foreach ($args as $arg) {
    if (! is_string($arg) || $arg === '' || $arg === 'dry-run' || $arg === '--dry-run') {
        continue;
    }
    $onlySlug = sanitize_title($arg);
}

$userId = (int) apply_filters('culvers_careers_single_populate_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

$result = CareerSingleFlexiblePopulate::runAll($dryRun, $onlySlug);

WP_CLI::success(sprintf(
    'Done. updated=%d failed=%d%s',
    $result['updated'],
    $result['failed'],
    $dryRun ? ' (dry-run)' : ''
));
