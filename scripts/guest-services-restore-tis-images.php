<?php

/**
 * Restores text-image-slider polaroid attachments on /guest-services/.
 *
 * Safe to re-run: only mutates the `text_image_slider` row's `tis_items` (labels,
 * copy, left/right images, Figma tilts). Other page components are untouched.
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *       wp-content/themes/culvers/scripts/guest-services-restore-tis-images.php
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Helpers\HomepageFlexibleAcfAttach;
use App\Helpers\PagesFlexibleSeedData;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

if (! function_exists('update_field')) {
    \WP_CLI::error('ACF is required (update_field missing).');
}

$page = get_page_by_path('guest-services', OBJECT, 'page');
if (! $page instanceof \WP_Post) {
    \WP_CLI::error('Guest Services page not found.');
}

$postId = (int) $page->ID;
$rows = get_field('components', $postId);
if (! is_array($rows) || $rows === []) {
    \WP_CLI::error('No flexible components on Guest Services.');
}

$seedTisItems = null;
foreach (PagesFlexibleSeedData::guestServicesPage() as $seedRow) {
    if (($seedRow['acf_fc_layout'] ?? '') === 'text_image_slider') {
        $seedTisItems = $seedRow['tis_items'] ?? null;
        break;
    }
}

if (! is_array($seedTisItems) || $seedTisItems === []) {
    \WP_CLI::error('Seed data has no text_image_slider items.');
}

$mutated = false;
foreach ($rows as $index => $row) {
    if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'text_image_slider') {
        continue;
    }

    $rows[$index]['tis_items'] = $seedTisItems;
    $mutated = true;
    break;
}

if (! $mutated) {
    \WP_CLI::error('Guest Services has no text_image_slider layout.');
}

$attached = HomepageFlexibleAcfAttach::attachFlexibleRows($rows);
$result = update_field('components', $attached, $postId);

if ($result === false) {
    \WP_CLI::warning('update_field returned false — verify in wp-admin.');
}

$verify = get_field('components', $postId);
$leftOk = false;
foreach ($verify as $row) {
    if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'text_image_slider') {
        continue;
    }
    $first = $row['tis_items'][0] ?? null;
    $left = is_array($first) ? ($first['item_image_left'] ?? null) : null;
    $leftOk = is_array($left) && ! empty($left['url']);
    break;
}

\WP_CLI::success(sprintf(
    'Guest Services text-image-slider: %d rows, images %s.',
    count($seedTisItems),
    $leftOk ? 'restored' : 'MISSING — check seeds in resources/images/seeds/'
));
