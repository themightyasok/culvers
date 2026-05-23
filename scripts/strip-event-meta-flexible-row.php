<?php

/**
 * Removes `event_meta` flexible rows from all directory singles that still have them.
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/strip-event-meta-flexible-row.php dry-run
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI
 */

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

if (! function_exists('get_field') || ! function_exists('update_field')) {
    WP_CLI::error('ACF is required.');
}

$dryRun = in_array('dry-run', $args, true) || in_array('--dry-run', $args, true);

$postTypes = ['culvers_news', 'culvers_event', 'culvers_offer', 'culvers_shop', 'culvers_eat_drink', 'culvers_career'];
$updated = 0;

foreach ($postTypes as $postType) {
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

    foreach ($postIds as $postId) {
        $postId = (int) $postId;
        $rows = get_field('components', $postId);
        if (! is_array($rows) || $rows === []) {
            continue;
        }

        $filtered = array_values(array_filter(
            $rows,
            static fn ($row): bool => ! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'event_meta'
        ));

        if (count($filtered) === count($rows)) {
            continue;
        }

        $slug = (string) get_post_field('post_name', $postId);
        WP_CLI::log(sprintf(
            '%s%s %s #%d — removed event_meta (%d → %d rows)',
            $dryRun ? '[dry-run] ' : '',
            $postType,
            $slug,
            $postId,
            count($rows),
            count($filtered)
        ));

        if (! $dryRun) {
            delete_field('components', $postId);
            update_field('components', $filtered, $postId);
        }

        ++$updated;
    }
}

WP_CLI::success(sprintf('Done. %s %d post(s).', $dryRun ? 'Would update' : 'Updated', $updated));
