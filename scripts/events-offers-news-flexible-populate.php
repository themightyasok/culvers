<?php

/**
 * Writes the full representative flexible `components` stack onto every
 * culvers_event, culvers_offer, and culvers_news post (placeholder copy/images).
 *
 * Stacks match the filled reference singles:
 *   - events → {@see CptSinglesFlexibleSeedData::easterEggHunt()}
 *   - offers → {@see CptSinglesFlexibleSeedData::hotelChocolatOffer()}
 *   - news   → {@see CptSinglesFlexibleSeedData::spring2026Lineup()}
 *
 * From WordPress root (app/public):
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/events-offers-news-flexible-populate.php dry-run
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/events-offers-news-flexible-populate.php
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI
 */

use App\Helpers\CptSinglesFlexibleSeedData;
use App\Helpers\HomepageFlexibleAcfAttach;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

if (! function_exists('update_field')) {
    WP_CLI::error('ACF is required (update_field missing).');
}

$dryRun = in_array('dry-run', $args, true) || in_array('--dry-run', $args, true);
$onlyType = null;
$onlySlug = null;

foreach ($args as $arg) {
    if (! is_string($arg) || $arg === '' || $arg === 'dry-run' || $arg === '--dry-run') {
        continue;
    }
    if (in_array($arg, ['culvers_event', 'culvers_offer', 'culvers_news'], true)) {
        $onlyType = $arg;
        continue;
    }
    $onlySlug = sanitize_title($arg);
}

$userId = (int) apply_filters('culvers_events_offers_news_populate_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

$postTypes = ['culvers_event', 'culvers_offer', 'culvers_news'];

/** Reference singles already authored — leave their DB content untouched. */
$preserveSlugs = [
    'easter-egg-hunt',
    'valentines-at-hotel-chocolat',
];

$updated = 0;
$skipped = 0;
$failed = 0;

foreach ($postTypes as $postType) {
    if ($onlyType !== null && $onlyType !== $postType) {
        continue;
    }

    $seedRows = CptSinglesFlexibleSeedData::canonicalRowsForPostType($postType);
    $attachedRows = HomepageFlexibleAcfAttach::attachFlexibleRows($seedRows);
    $layoutKeys = array_map(
        static fn (array $row): string => (string) ($row['acf_fc_layout'] ?? ''),
        $attachedRows
    );

    $postIds = get_posts([
        'post_type' => $postType,
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => -1,
        'fields' => 'ids',
        'suppress_filters' => true,
    ]);

    if (! is_array($postIds) || $postIds === []) {
        WP_CLI::log(sprintf('— %s: no posts', $postType));
        continue;
    }

    foreach ($postIds as $postId) {
        $postId = (int) $postId;
        $slug = (string) get_post_field('post_name', $postId);

        if ($onlySlug !== null && $onlySlug !== $slug) {
            continue;
        }

        $title = (string) get_the_title($postId);
        $before = get_field('components', $postId);
        $beforeLayouts = is_array($before)
            ? array_values(array_filter(array_map(
                static fn ($r) => is_array($r) ? (string) ($r['acf_fc_layout'] ?? '') : '',
                $before
            )))
            : [];

        if (in_array($slug, $preserveSlugs, true)) {
            WP_CLI::log(sprintf('skip  %s #%d (%s) — reference single preserved', $postType, $postId, $slug));
            ++$skipped;
            continue;
        }

        WP_CLI::log(sprintf(
            '%s%s %s #%d %s | %s → %s',
            $dryRun ? '[dry-run] ' : '',
            $postType,
            $slug,
            $postId,
            $title,
            implode(', ', $beforeLayouts) ?: '(empty)',
            implode(', ', $layoutKeys)
        ));

        if (! $dryRun) {
            // Replace in full — ACF flexible rows with matching layout keys do not
            // reliably merge new subfield values on update_field alone.
            delete_field('components', $postId);
            $result = update_field('components', $attachedRows, $postId);
            if ($result === false) {
                WP_CLI::warning(sprintf('update_field returned false for %s #%d', $postType, $postId));
                ++$failed;
                continue;
            }
        }

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
