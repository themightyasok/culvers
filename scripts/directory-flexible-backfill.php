<?php

/**
 * Persists the standard Page Components stack on directory singles that need it.
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *       wp-content/themes/culvers/scripts/directory-flexible-backfill.php
 *
 * Dry run (list only):
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *       wp-content/themes/culvers/scripts/directory-flexible-backfill.php dry-run
 *
 * Repairs: empty field, lone image_hero stub, or a canonical prefix missing trailing layouts.
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Directory\DirectoryFlexibleDefaults;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

if (! function_exists('update_field') || ! function_exists('get_field')) {
    \WP_CLI::error('ACF is required (get_field / update_field missing).');
}

$cliArgs = $args ?? [];
$dryRun = in_array('dry-run', $cliArgs, true)
    || in_array('--dry-run', $cliArgs, true)
    || getenv('CULVERS_DIRECTORY_FLEXIBLE_BACKFILL_DRY_RUN') === '1';

$userId = (int) apply_filters('culvers_directory_flexible_backfill_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

$updated = 0;
$skipped = 0;

foreach (DirectoryFlexibleDefaults::supportedPostTypes() as $postType) {
    $postIds = get_posts([
        'post_type' => $postType,
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => -1,
        'fields' => 'ids',
        'suppress_filters' => true,
    ]);

    if (! is_array($postIds)) {
        continue;
    }

    $expectedCount = count(DirectoryFlexibleDefaults::layoutKeysForPostType($postType));

    foreach ($postIds as $postId) {
        $postId = (int) $postId;
        if ($postId <= 0) {
            continue;
        }

        $current = DirectoryFlexibleDefaults::storedComponents($postId);
        if (! DirectoryFlexibleDefaults::shouldPersistDefaults($current, $postType)) {
            ++$skipped;
            continue;
        }

        $plan = DirectoryFlexibleDefaults::backfillPlan($current, $postType);
        if ($plan === null) {
            ++$skipped;
            continue;
        }

        $title = get_the_title($postId);
        $slug = get_post_field('post_name', $postId);
        $action = is_array($current) && $current !== [] ? 'append/repair' : 'write';

        if ($dryRun) {
            \WP_CLI::log(sprintf(
                '[dry-run] Would %s %d → %d layouts on %s #%d (%s / %s)',
                $action,
                is_array($current) ? count($current) : 0,
                count($plan),
                $postType,
                $postId,
                $title,
                $slug
            ));
            ++$updated;
            continue;
        }

        if (! DirectoryFlexibleDefaults::persistDefaultsForPost($postId)) {
            \WP_CLI::warning(sprintf('Could not update components for %s #%d', $postType, $postId));
            continue;
        }

        \WP_CLI::log(sprintf(
            '%s %d → %d layouts on %s #%d (%s)',
            $action === 'write' ? 'Wrote' : 'Repaired',
            is_array($current) ? count($current) : 0,
            count($plan),
            $postType,
            $postId,
            $title
        ));
        ++$updated;
    }

    \WP_CLI::log(sprintf('— %s: expected %d layouts per single', $postType, $expectedCount));
}

if ($dryRun) {
    \WP_CLI::success(sprintf('Dry run complete — would update %d post(s), skipped %d.', $updated, $skipped));
} else {
    \WP_CLI::success(sprintf('Backfill complete — updated %d post(s), skipped %d.', $updated, $skipped));
}
