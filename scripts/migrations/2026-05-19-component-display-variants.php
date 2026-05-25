<?php

/**
 * Backfill display-variant fields on existing flexible rows (does not change hours_rows or other content).
 *
 * Run:
 *   cd app/public && ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/migrations/2026-05-19-component-display-variants.php
 *
 * Dry-run (log only):
 *   ... wp eval-file .../2026-05-19-component-display-variants.php -- --dry-run
 */

use App\Helpers\ImageHeroLogoSource;
use App\Helpers\OpeningHoursContext;

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI (wp eval-file) so WordPress is bootstrapped under CLI.\n");
    exit(1);
}

$dryRun = in_array('--dry-run', $_SERVER['argv'] ?? [], true);

if (! function_exists('get_field') || ! function_exists('update_field')) {
    WP_CLI::error('ACF is required.');
}

/** @var list<string> */
$retailerPostTypes = ['culvers_shop', 'culvers_eat_drink'];

/** @var list<string> */
$directoryPostTypes = ['culvers_shop', 'culvers_eat_drink', 'culvers_career'];

/**
 * @return array{changed: bool, components: list<array<string, mixed>>}
 */
$backfillComponents = static function (array $components, string $postType, int $postId) use (
    $retailerPostTypes,
    $directoryPostTypes
): array {
    $changed = false;

    foreach ($components as $i => $row) {
        if (! is_array($row)) {
            continue;
        }

        $layout = (string) ($row['acf_fc_layout'] ?? '');

        if ($layout === 'opening_hours') {
            $expected = in_array($postType, $retailerPostTypes, true)
                ? OpeningHoursContext::RETAILER
                : OpeningHoursContext::CENTRE;
            $current = is_string($row['hours_context'] ?? null) ? trim($row['hours_context']) : '';
            if ($current !== $expected) {
                $row['hours_context'] = $expected;
                $changed = true;
            }
        }

        if ($layout === 'shop_store_details') {
            $spacing = is_string($row['details_heading_spacing'] ?? null) ? trim($row['details_heading_spacing']) : '';
            if (! in_array($spacing, ['standard', 'carousel', 'compact'], true)) {
                $row['details_heading_spacing'] = 'compact';
                $changed = true;
            }
        }

        if ($layout === 'shop_related_shops') {
            $spacing = is_string($row['shops_related_heading_spacing'] ?? null) ? trim($row['shops_related_heading_spacing']) : '';
            if (! in_array($spacing, ['standard', 'carousel', 'compact'], true)) {
                $row['shops_related_heading_spacing'] = 'carousel';
                $changed = true;
            }
        }

        if ($layout === 'shop_related_eat_drink') {
            $spacing = is_string($row['eat_drink_related_heading_spacing'] ?? null)
                ? trim($row['eat_drink_related_heading_spacing'])
                : '';
            if (! in_array($spacing, ['standard', 'carousel', 'compact'], true)) {
                $row['eat_drink_related_heading_spacing'] = 'carousel';
                $changed = true;
            }
        }

        if ($layout === 'image_hero' && in_array($postType, $directoryPostTypes, true)) {
            $source = is_string($row['hero_logo_source'] ?? null) ? trim($row['hero_logo_source']) : '';
            if ($source === '' || $source === ImageHeroLogoSource::UPLOADED) {
                $resolved = ImageHeroLogoSource::DIRECTORY_LOGO;

                if ($postType === 'culvers_eat_drink' && function_exists('get_field')) {
                    $listingLogo = get_field('eat_drink_logo', $postId);
                    $hasListingLogo = is_array($listingLogo)
                        ? trim((string) ($listingLogo['url'] ?? '')) !== ''
                        : is_numeric($listingLogo) && (int) $listingLogo > 0;

                    if (! $hasListingLogo && (int) get_post_thumbnail_id($postId) > 0) {
                        $resolved = ImageHeroLogoSource::FEATURED;
                    }
                }

                if ($source !== $resolved) {
                    $row['hero_logo_source'] = $resolved;
                    $changed = true;
                }
            }
        }

        $components[$i] = $row;
    }

    return ['changed' => $changed, 'components' => $components];
};

$postTypes = get_post_types(['public' => true], 'names');
if (! is_array($postTypes)) {
    $postTypes = [];
}

$updatedPosts = 0;
$openingRetailer = 0;
$openingCentre = 0;

foreach ($postTypes as $postType) {
    $posts = get_posts([
        'post_type' => $postType,
        'post_status' => ['publish', 'draft', 'pending', 'future', 'private'],
        'posts_per_page' => -1,
        'fields' => 'ids',
        'suppress_filters' => true,
    ]);

    if (! is_array($posts)) {
        continue;
    }

    foreach ($posts as $postId) {
        $postId = (int) $postId;
        if ($postId <= 0) {
            continue;
        }

        $components = get_field('components', $postId);
        if (! is_array($components) || $components === []) {
            continue;
        }

        $result = $backfillComponents($components, (string) $postType, $postId);
        if (! $result['changed']) {
            continue;
        }

        foreach ($result['components'] as $row) {
            if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'opening_hours') {
                continue;
            }
            if (($row['hours_context'] ?? '') === OpeningHoursContext::RETAILER) {
                ++$openingRetailer;
            } elseif (($row['hours_context'] ?? '') === OpeningHoursContext::CENTRE) {
                ++$openingCentre;
            }
        }

        if ($dryRun) {
            WP_CLI::log(sprintf('Would update post #%d (%s)', $postId, $postType));
        } else {
            update_field('components', $result['components'], $postId);
            WP_CLI::log(sprintf('Updated post #%d (%s)', $postId, $postType));
        }

        ++$updatedPosts;
    }
}

WP_CLI::success(sprintf(
    '%s %d post(s). opening_hours: %d retailer, %d centre row(s) touched.',
    $dryRun ? 'Dry-run — would update' : 'Updated',
    $updatedPosts,
    $openingRetailer,
    $openingCentre
));
