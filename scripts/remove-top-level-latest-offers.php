<?php

/**
 * Remove top-level "Latest Offers" from primary_navigation (keep under What's On).
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
$deleted = 0;

if (is_array($items)) {
    foreach ($items as $item) {
        if (! $item instanceof WP_Post) {
            continue;
        }
        if ((int) $item->menu_item_parent !== 0) {
            continue;
        }
        if (stripos((string) $item->post_title, 'latest offers') === false) {
            continue;
        }
        wp_delete_post((int) $item->ID, true);
        WP_CLI::log(sprintf('Deleted top-level "%s" (ID %d)', $item->post_title, $item->ID));
        ++$deleted;
    }
}

WP_CLI::success("Removed {$deleted} top-level Latest Offers item(s).");
