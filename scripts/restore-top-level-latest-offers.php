<?php

/**
 * Restore top-level "Latest Offers" in primary_navigation (alongside What's On child).
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

$locations = get_nav_menu_locations();
$menuId = (int) ($locations['primary_navigation'] ?? 0);

if ($menuId <= 0) {
    WP_CLI::error('No primary_navigation menu assigned.');
}

$items = wp_get_nav_menu_items($menuId);
$hasTop = false;

if (is_array($items)) {
    foreach ($items as $item) {
        if (! $item instanceof WP_Post) {
            continue;
        }
        if (stripos((string) $item->post_title, 'latest offers') !== false
            && (int) $item->menu_item_parent === 0) {
            $hasTop = true;
            break;
        }
    }
}

if ($hasTop) {
    WP_CLI::success('Top-level Latest Offers already present.');
    exit(0);
}

$url = home_url('/latest-offers/');
$result = wp_update_nav_menu_item($menuId, 0, [
    'menu-item-title' => __('Latest Offers', 'culvers'),
    'menu-item-url' => $url,
    'menu-item-status' => 'publish',
    'menu-item-type' => 'custom',
    'menu-item-parent-id' => 0,
]);

if (is_wp_error($result)) {
    WP_CLI::error($result->get_error_message());
}

WP_CLI::success(sprintf('Restored top-level Latest Offers (menu item ID %d).', (int) $result));
