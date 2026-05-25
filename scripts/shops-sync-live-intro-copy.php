<?php

/**
 * Sync shop intro + split-highlight copy from culversquare.co.uk retailer pages.
 *
 * Maps live /retailers/{slug}/ copy into:
 *   - shop_intro_block.intro_body (first 1–2 paragraphs)
 *   - shop_split_highlight (kicker / headline / body from the natural break)
 *
 * Does not replace hero, store details, hours, or related shops.
 *
 *   wp eval-file wp-content/themes/culvers/scripts/shops-sync-live-intro-copy.php
 *   wp eval-file .../shops-sync-live-intro-copy.php dry-run
 *   wp eval-file .../shops-sync-live-intro-copy.php -- accessorize-london
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI
 */

use App\Directory\ShopIntroCta;
use App\Directory\ShopLiveIntroCopy;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

if (! function_exists('get_field') || ! function_exists('update_field')) {
    WP_CLI::error('ACF is required.');
}

$dryRun = in_array('dry-run', $args, true) || in_array('--dry-run', $args, true);
$onlyLocal = null;
foreach ($args as $arg) {
    if (! is_string($arg) || $arg === '' || $arg === 'dry-run' || $arg === '--dry-run') {
        continue;
    }
    $onlyLocal = sanitize_title($arg);
}

$map = ShopLiveIntroCopy::liveToLocalMap();
$liveSlugs = ShopLiveIntroCopy::discoverLiveSlugs();

if ($liveSlugs === []) {
    WP_CLI::warning('No retailer links found on live shopping page — using static slug map only.');
    $liveSlugs = array_keys($map);
}

$updated = 0;
$skipped = 0;
$failed = 0;

foreach ($liveSlugs as $liveSlug) {
    $localSlug = $map[$liveSlug] ?? null;
    if ($localSlug === null) {
        WP_CLI::log(sprintf('skip live "%s" — no local culvers_shop mapping', $liveSlug));
        ++$skipped;
        continue;
    }

    if ($onlyLocal !== null && $onlyLocal !== $localSlug) {
        continue;
    }

    $posts = get_posts([
        'post_type' => 'culvers_shop',
        'name' => $localSlug,
        'post_status' => 'any',
        'posts_per_page' => 1,
    ]);

    if ($posts === []) {
        WP_CLI::warning(sprintf('no local post for %s (live %s)', $localSlug, $liveSlug));
        ++$failed;
        continue;
    }

    $post = $posts[0];
    $content = ShopLiveIntroCopy::fetchRetailerContent($liveSlug);
    if ($content === null || $content['paras'] === []) {
        WP_CLI::warning(sprintf('no copy scraped for %s / %s', $localSlug, $liveSlug));
        ++$skipped;
        continue;
    }

    $split = ShopLiveIntroCopy::splitForBlocks(
        get_the_title($post) ?: $content['title'],
        $content['paras'],
        $content['lists']
    );

    if ($split['intro_html'] === '') {
        WP_CLI::warning(sprintf('empty intro after split for %s', $localSlug));
        ++$skipped;
        continue;
    }

    $components = get_field('components', $post->ID);
    if (! is_array($components) || $components === []) {
        WP_CLI::warning(sprintf('%s has no flexible components — populate in WP admin first', $localSlug));
        ++$failed;
        continue;
    }

    $introIdx = null;
    $splitIdx = null;
    foreach ($components as $i => $row) {
        if (! is_array($row)) {
            continue;
        }
        $layout = (string) ($row['acf_fc_layout'] ?? '');
        if ($layout === 'shop_intro_block') {
            $introIdx = $i;
        }
        if ($layout === 'shop_split_highlight') {
            $splitIdx = $i;
        }
    }

    if ($introIdx === null) {
        WP_CLI::warning(sprintf('%s missing shop_intro_block row', $localSlug));
        ++$failed;
        continue;
    }

    $components[$introIdx]['intro_body'] = $split['intro_html'];

    $cta = ShopIntroCta::resolve(
        $localSlug,
        $liveSlug,
        get_the_title($post) ?: $content['title']
    );
    if ($cta !== null) {
        $components[$introIdx]['intro_cta_label'] = $cta['label'];
        $components[$introIdx]['intro_cta_url'] = $cta['url'];
    }

    if ($splitIdx !== null) {
        $components[$splitIdx]['split_use_tabs'] = 0;
        $components[$splitIdx]['split_kicker'] = $split['split_kicker'];
        $components[$splitIdx]['split_headline'] = $split['split_headline'];
        $components[$splitIdx]['split_body'] = $split['split_body_html'];
        $components[$splitIdx]['split_cta_label'] = '';
        $components[$splitIdx]['split_cta_url'] = '';
    } else {
        WP_CLI::warning(sprintf('%s has no shop_split_highlight — intro only', $localSlug));
    }

    $headlinePreview = $split['split_headline'] !== '' ? $split['split_headline'] : '(none)';
    WP_CLI::log(sprintf(
        '%s%s ← live/%s | intro %d chars | split headline: %s',
        $dryRun ? '[dry-run] ' : '',
        $localSlug,
        $liveSlug,
        strlen($split['intro_html']),
        $headlinePreview
    ));

    if (! $dryRun) {
        update_field('components', $components, $post->ID);
        ++$updated;
    }
}

WP_CLI::success(sprintf(
    'Done. updated=%d skipped=%d failed=%d%s',
    $updated,
    $skipped,
    $failed,
    $dryRun ? ' (dry-run)' : ''
));
