<?php

/**
 * Repair deploy shop card logos: register on-disk SVG/PNG files and set
 * `shop_logo` + hero `hero_logo`. Only touches {@see ShopLiveSync::DEPLOY_MEDIA_BY_SLUG}
 * slugs (and optionally cosmic-tattoo card logo). Skips posts that already have shop_logo.
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/shops-repair-deploy-logos.php
 *
 *   .../shops-repair-deploy-logos.php fix-cosmic-tattoo-card
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI
 */

use App\Directory\ShopLiveSync;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

$dryRun = in_array('dry-run', $args, true) || in_array('--dry-run', $args, true);
$fixCosmic = in_array('fix-cosmic-tattoo-card', $args, true)
    || in_array('--fix-cosmic-tattoo-card', $args, true);
$onlySlug = null;
foreach ($args as $arg) {
    if (! is_string($arg) || $arg === '') {
        continue;
    }
    if (in_array($arg, ['dry-run', '--dry-run', 'fix-cosmic-tattoo-card', '--fix-cosmic-tattoo-card'], true)) {
        continue;
    }
    $onlySlug = sanitize_title($arg);
}

$result = ShopLiveSync::repairDeployLogos($dryRun, $onlySlug, $fixCosmic);

WP_CLI::success(sprintf(
    'Done. repaired=%d skipped=%d failed=%d%s',
    $result['repaired'],
    $result['skipped'],
    $result['failed'],
    $dryRun ? ' (dry-run)' : ''
));
