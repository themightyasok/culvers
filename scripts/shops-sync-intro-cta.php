<?php

/**
 * Set shop_intro_block CTA label + URL from live retailer pages (or fallbacks).
 *
 * From WordPress root (app/public):
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/shops-sync-intro-cta.php dry-run
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/shops-sync-intro-cta.php
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI
 */

use App\Directory\DirectoryIntroCta;

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

$postIds = get_posts([
    'post_type' => 'culvers_shop',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'suppress_filters' => true,
]);

$updated = 0;
$skipped = 0;
$failed = 0;

foreach ($postIds as $postId) {
    $postId = (int) $postId;
    $slug = (string) get_post_field('post_name', $postId);

    if ($onlyLocal !== null && $onlyLocal !== $slug) {
        continue;
    }

    $liveSlug = DirectoryIntroCta::liveSlugForLocal($slug);
    $cta = DirectoryIntroCta::resolve($slug, $liveSlug, (string) get_the_title($postId));

    if ($cta === null) {
        WP_CLI::warning(sprintf('%s — no brand website found', $slug));
        ++$skipped;
        continue;
    }

    $components = get_field('components', $postId);
    if (! is_array($components) || $components === []) {
        WP_CLI::warning(sprintf('%s — no components', $slug));
        ++$failed;
        continue;
    }

    $introIdx = null;
    foreach ($components as $i => $row) {
        if (is_array($row) && ($row['acf_fc_layout'] ?? '') === 'shop_intro_block') {
            $introIdx = $i;
            break;
        }
    }

    if ($introIdx === null) {
        WP_CLI::warning(sprintf('%s — missing shop_intro_block', $slug));
        ++$failed;
        continue;
    }

    $beforeLabel = (string) ($components[$introIdx]['intro_cta_label'] ?? '');
    $beforeUrl = (string) ($components[$introIdx]['intro_cta_url'] ?? '');

    if ($beforeLabel === $cta['label'] && $beforeUrl === $cta['url']) {
        WP_CLI::log(sprintf('skip  %s — already current', $slug));
        ++$skipped;
        continue;
    }

    WP_CLI::log(sprintf(
        '%s%s %s → %s',
        $dryRun ? '[dry-run] ' : '',
        $slug,
        $cta['label'],
        $cta['url']
    ));

    $components[$introIdx]['intro_cta_label'] = $cta['label'];
    $components[$introIdx]['intro_cta_url'] = $cta['url'];

    if (! $dryRun) {
        update_field('components', $components, $postId);
    }

    ++$updated;
}

WP_CLI::success(sprintf(
    'Done. updated=%d skipped=%d failed=%d%s',
    $updated,
    $skipped,
    $failed,
    $dryRun ? ' (dry-run)' : ''
));
