<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Directory custom post types (Shops first; Eat & Drink / Careers mirror later).
 */
final class DirectoryPostTypes
{
    private const REWRITE_VERSION = 2;

    public static function register(): void
    {
        self::registerShopPostType();
        self::registerShopTaxonomies();
        self::maybeFlushRewrites();
        self::adjustShopArchiveQuery();
    }

    private static function registerShopPostType(): void
    {
        register_post_type(
            'culvers_shop',
            [
                'labels' => [
                    'name' => __('Shops', 'culvers'),
                    'singular_name' => __('Shop', 'culvers'),
                    'add_new_item' => __('Add New Shop', 'culvers'),
                    'edit_item' => __('Edit Shop', 'culvers'),
                    'view_item' => __('View Shop', 'culvers'),
                    'search_items' => __('Search Shops', 'culvers'),
                    'not_found' => __('No shops found.', 'culvers'),
                    'all_items' => __('All Shops', 'culvers'),
                    'archives' => __('Shop directory', 'culvers'),
                ],
                'description' => __('Retailers and brands in the centre directory.', 'culvers'),
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true,
                'has_archive' => true,
                'menu_icon' => 'dashicons-store',
                'menu_position' => 22,
                'supports' => ['title', 'thumbnail', 'editor', 'excerpt', 'revisions'],
                'rewrite' => ['slug' => 'shops', 'with_front' => false],
                'show_in_rest' => true,
                'capability_type' => 'post',
            ]
        );
    }

    private static function registerShopTaxonomies(): void
    {
        register_taxonomy(
            'culvers_shop_category',
            ['culvers_shop'],
            [
                'labels' => [
                    'name' => __('Shop categories', 'culvers'),
                    'singular_name' => __('Shop category', 'culvers'),
                    'search_items' => __('Search categories', 'culvers'),
                    'all_items' => __('All categories', 'culvers'),
                    'edit_item' => __('Edit category', 'culvers'),
                    'update_item' => __('Update category', 'culvers'),
                    'add_new_item' => __('Add category', 'culvers'),
                    'new_item_name' => __('New category name', 'culvers'),
                ],
                'hierarchical' => true,
                'public' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'show_in_nav_menus' => false,
                'show_in_rest' => true,
                'rewrite' => ['slug' => 'shop-category', 'with_front' => false],
            ]
        );

        register_taxonomy(
            'culvers_shop_type',
            ['culvers_shop'],
            [
                'labels' => [
                    'name' => __('Retailer types', 'culvers'),
                    'singular_name' => __('Retailer type', 'culvers'),
                    'search_items' => __('Search retailer types', 'culvers'),
                    'all_items' => __('All retailer types', 'culvers'),
                    'edit_item' => __('Edit retailer type', 'culvers'),
                    'update_item' => __('Update retailer type', 'culvers'),
                    'add_new_item' => __('Add retailer type', 'culvers'),
                    'new_item_name' => __('New retailer type name', 'culvers'),
                ],
                'hierarchical' => true,
                'public' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'show_in_nav_menus' => false,
                'show_in_rest' => true,
                'rewrite' => ['slug' => 'shop-type', 'with_front' => false],
            ]
        );
    }

    private static function maybeFlushRewrites(): void
    {
        $current = (int) get_option('culvers_directory_rewrite_ver', 0);
        if ($current >= self::REWRITE_VERSION) {
            return;
        }
        flush_rewrite_rules(false);
        update_option('culvers_directory_rewrite_ver', self::REWRITE_VERSION, true);
    }

    private static function adjustShopArchiveQuery(): void
    {
        add_action(
            'pre_get_posts',
            static function (\WP_Query $query): void {
                if (is_admin() || ! $query->is_main_query()) {
                    return;
                }
                if (! $query->is_post_type_archive('culvers_shop')) {
                    return;
                }
                $query->set('posts_per_page', -1);
                $query->set('orderby', 'title');
                $query->set('order', 'ASC');
            },
            10
        );
    }
}
