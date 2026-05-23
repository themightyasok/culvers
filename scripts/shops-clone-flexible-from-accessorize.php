<?php

/**
 * Clone Accessorize London flexible page content onto every other published shop.
 *
 * Preserves per-shop identity assets (never overwritten):
 *   • Featured image (directory card hover photo)
 *   • `shop_logo` ACF
 *   • First `image_hero` row: `hero_image`, `hero_image_mobile`, `hero_logo`,
 *     `hero_title_line`, `hero_subtitle_line`
 *
 * Everything else in `components` is copied from the source shop.
 *
 * Local only — run via with-local-env.sh:
 *
 *   wp eval-file wp-content/themes/culvers/scripts/shops-clone-flexible-from-accessorize.php
 *
 * Dry-run (no writes):
 *
 *   wp eval-file .../shops-clone-flexible-from-accessorize.php -- --dry-run
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

if (! function_exists('update_field') || ! function_exists('get_field')) {
    WP_CLI::error('ACF is required.');
}

$dryRun = in_array('--dry-run', $args ?? [], true);
$sourceSlug = 'accessorize-london';

$sourceIds = get_posts([
    'post_type' => 'culvers_shop',
    'name' => $sourceSlug,
    'post_status' => 'any',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'suppress_filters' => true,
]);

if (! is_array($sourceIds) || ! isset($sourceIds[0])) {
    WP_CLI::error(sprintf('Source shop "%s" not found.', $sourceSlug));
}

$sourceId = (int) $sourceIds[0];
$sourceComponents = get_field('components', $sourceId);

if (! is_array($sourceComponents) || $sourceComponents === []) {
    WP_CLI::error(sprintf('Source shop ID %d has no flexible `components`.', $sourceId));
}

$targets = get_posts([
    'post_type' => 'culvers_shop',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'suppress_filters' => true,
    'exclude' => [$sourceId],
]);

if ($targets === []) {
    WP_CLI::warning('No other published shops to update.');
    exit(0);
}

/**
 * @return array{hero_image: mixed, hero_image_mobile: mixed, hero_logo: mixed, hero_title_line: mixed, hero_subtitle_line: mixed}|null
 */
function culvers_preserve_hero_slice(array $rows): ?array
{
    foreach ($rows as $row) {
        if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'image_hero') {
            continue;
        }

        return [
            'hero_image' => $row['hero_image'] ?? null,
            'hero_image_mobile' => $row['hero_image_mobile'] ?? null,
            'hero_logo' => $row['hero_logo'] ?? null,
            'hero_title_line' => $row['hero_title_line'] ?? '',
            'hero_subtitle_line' => $row['hero_subtitle_line'] ?? '',
        ];
    }

    return null;
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @param  array<string, mixed>|null   $preserve
 * @return list<array<string, mixed>>
 */
function culvers_merge_hero_into_components(array $rows, ?array $preserve): array
{
    if ($preserve === null) {
        return $rows;
    }

    foreach ($rows as $i => $row) {
        if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'image_hero') {
            continue;
        }

        foreach ($preserve as $key => $value) {
            if ($value !== null && $value !== '' && $value !== []) {
                $row[$key] = $value;
            }
        }
        $rows[$i] = $row;
        break;
    }

    return $rows;
}

$updated = 0;

foreach ($targets as $targetId) {
    $targetId = (int) $targetId;
    $slug = (string) get_post_field('post_name', $targetId);
    $title = (string) get_the_title($targetId);

    $preserveHero = culvers_preserve_hero_slice(
        is_array($existing = get_field('components', $targetId)) ? $existing : []
    );
    $shopLogo = get_field('shop_logo', $targetId);
    $thumbId = (int) get_post_thumbnail_id($targetId);

    $merged = culvers_merge_hero_into_components($sourceComponents, $preserveHero);

    if ($dryRun) {
        WP_CLI::log(sprintf('[dry-run] Would update %s (ID %d)', $slug, $targetId));
        ++$updated;
        continue;
    }

    $ok = update_field('components', $merged, $targetId);
    if ($shopLogo !== null && $shopLogo !== false && $shopLogo !== '') {
        update_field('shop_logo', $shopLogo, $targetId);
    }
    if ($thumbId > 0) {
        set_post_thumbnail($targetId, $thumbId);
    }

    if ($ok === false) {
        WP_CLI::warning(sprintf('update_field returned false for %s (ID %d)', $slug, $targetId));
    } else {
        WP_CLI::log(sprintf('Updated %s — %s (ID %d)', $slug, $title, $targetId));
    }
    ++$updated;
}

WP_CLI::success(sprintf(
    '%s %d shop(s) from source %s (ID %d). Hero, logo, and featured image preserved per shop.',
    $dryRun ? 'Would update' : 'Updated',
    $updated,
    $sourceSlug,
    $sourceId
));
