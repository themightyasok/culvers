<?php

/**
 * Writes the canonical homepage flexible layouts into the database (same payload as the old runtime defaults).
 *
 * Run from WordPress root with Local’s environment:
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file wp-content/themes/culvers/scripts/homepage-populate-flexible.php
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Helpers\HomepageFlexibleAcfAttach;
use App\Helpers\HomepageFlexibleSeedData;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

if (! function_exists('update_field')) {
    \WP_CLI::error('ACF is required (update_field missing).');
}

$front_id = (int) get_option('page_on_front');

if ($front_id <= 0 || ! is_string(get_post_status($front_id)) || get_post_status($front_id) !== 'publish') {
    $home = get_page_by_path('home', OBJECT, 'page');
    if ($home instanceof \WP_Post && $home->post_status === 'publish') {
        $front_id = (int) $home->ID;
    } else {
        $insert = wp_insert_post(
            [
                'post_title' => __('Home', 'culvers'),
                'post_name' => 'home',
                'post_status' => 'publish',
                'post_type' => 'page',
            ],
            true
        );
        if (is_wp_error($insert)) {
            \WP_CLI::error($insert->get_error_message());
        }
        $front_id = (int) $insert;
    }
}

// Idempotent: only write when the values would actually change. Re-running with no edits
// becomes a true no-op and avoids clobbering an admin's manual front-page reassignment.
if (get_option('show_on_front') !== 'page') {
    update_option('show_on_front', 'page');
}
if ((int) get_option('page_on_front') !== $front_id) {
    update_option('page_on_front', $front_id);
}

$user_id = (int) apply_filters('culvers_homepage_populate_user_id', 1);
if ($user_id > 0) {
    wp_set_current_user($user_id);
}

$rows = HomepageFlexibleAcfAttach::attachFlexibleRows(HomepageFlexibleSeedData::fullStack());

// Persist like the editor: replaces the flexible field value on that page.
$result = update_field('components', $rows, $front_id);

if ($result === false) {
    \WP_CLI::warning(
        'update_field returned false — check ACF field name `components` and post ID. Data may still have saved.'
    );
}

\WP_CLI::success(
    sprintf('Homepage flexible components saved on page ID %d (%d layouts).', $front_id, count($rows))
);
