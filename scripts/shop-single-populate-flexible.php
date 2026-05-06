<?php

/**
 * Writes the full shop-detail flexible stack into the database for one `culvers_shop` post
 * (same workflow as homepage-populate-flexible / shops-directory-populate).
 *
 * Prerequisites: retailers seeded (`scripts/shops-directory-populate.php`) so slug + imagery URLs resolve.
 *
 * From WordPress root with Local’s environment:
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file wp-content/themes/culvers/scripts/shop-single-populate-flexible.php
 *
 * Optional slug (default accessorize-london):
 *
 *   ... wp eval-file .../shop-single-populate-flexible.php accessorize-london
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Directory\ShopDirectorySeedData;
use App\Directory\ShopSingleFlexibleSeedData;
use App\Helpers\HomepageFlexibleAcfAttach;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

if (! function_exists('update_field')) {
    \WP_CLI::error('ACF is required (update_field missing).');
}

$slug = isset($args[0]) && is_string($args[0]) && trim($args[0]) !== ''
    ? sanitize_title($args[0])
    : 'accessorize-london';

$posts = get_posts([
    'post_type' => 'culvers_shop',
    'name' => $slug,
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'suppress_filters' => true,
]);

if (! is_array($posts) || ! isset($posts[0])) {
    \WP_CLI::error(
        sprintf(
            'No published culvers_shop found with slug "%s". Run shops-directory-populate.php first.',
            $slug
        )
    );
}

$postId = (int) $posts[0];

$userId = (int) apply_filters('culvers_shop_single_populate_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

try {
    $rows = ShopSingleFlexibleSeedData::fullStackForSlug($slug, $postId);
} catch (\InvalidArgumentException $e) {
    \WP_CLI::error($e->getMessage());
}

$rows = HomepageFlexibleAcfAttach::attachFlexibleRows($rows);

$result = update_field('components', $rows, $postId);

update_field('opening_hours_summary', ShopDirectorySeedData::DEFAULT_HOURS_LINE, $postId);

if ($result === false) {
    \WP_CLI::warning(
        'update_field returned false — check ACF field name `components` and post ID. Data may still have saved.'
    );
}

\WP_CLI::success(
    sprintf(
        'Shop single flexible components saved on culvers_shop ID %d (%s) — %d layouts.',
        $postId,
        $slug,
        count($rows)
    )
);
