<?php

/**
 * Clone `/leasing-opportunities/` → `/commercialisation-opportunities/` (same flexible components).
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *       wp-content/themes/culvers/scripts/clone-commercialisation-opportunities-page.php
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Nav\FooterNavLinkSync;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

const SOURCE_SLUG = 'leasing-opportunities';
const TARGET_SLUG = 'commercialisation-opportunities';
const TARGET_TITLE = 'Commercialisation Opportunities';

$source = get_page_by_path(SOURCE_SLUG, OBJECT, 'page');
if (! $source instanceof \WP_Post) {
    \WP_CLI::error(sprintf('Source page /%s/ not found.', SOURCE_SLUG));
}

$existing = get_page_by_path(TARGET_SLUG, OBJECT, 'page');
if ($existing instanceof \WP_Post) {
    $targetId = (int) $existing->ID;
    \WP_CLI::log(sprintf('Page /%s/ already exists (ID %d) — refreshing components copy.', TARGET_SLUG, $targetId));
} else {
    $insert = wp_insert_post(
        [
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => TARGET_TITLE,
            'post_name' => TARGET_SLUG,
            'post_content' => '',
        ],
        true
    );
    if (is_wp_error($insert)) {
        \WP_CLI::error($insert->get_error_message());
    }
    $targetId = (int) $insert;
    \WP_CLI::log(sprintf('Created page /%s/ (ID %d).', TARGET_SLUG, $targetId));
}

if (! function_exists('get_field') || ! function_exists('update_field')) {
    \WP_CLI::error('ACF is required.');
}

$components = get_field('components', (int) $source->ID);
if (! is_array($components) || $components === []) {
    \WP_CLI::warning('Source page has no flexible components — target left empty.');
} else {
    update_field('components', $components, $targetId);
    \WP_CLI::log(sprintf('Copied %d flexible row(s) from page %d.', count($components), (int) $source->ID));
}

FooterNavLinkSync::syncAllLocations();
update_option(FooterNavLinkSync::OPTION_VER, FooterNavLinkSync::CURRENT_VER, true);

wp_cache_flush();
flush_rewrite_rules(false);

\WP_CLI::success(sprintf(
    'Commercialisation page ready at %s',
    home_url('/' . TARGET_SLUG . '/')
));
