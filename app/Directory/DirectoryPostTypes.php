<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Directory custom post types — Shops, Eat & Drink, Careers, plus the three
 * sibling content types featured on the What's On landing (Latest Events /
 * Latest Offers / Latest News). Every directory CPT shares the same shape
 * (post type + 1–2 hierarchical taxonomies + flexible-content single +
 * alphabetical / chronological archive) so the matching
 * `archive-culvers-*.blade.php` and `single-culvers-*.blade.php` templates
 * can stay in lock-step.
 *
 * URL layout (all top-level — `/whats-on/` is a curated landing page, not
 * an archive parent):
 *   - /shops/                 culvers_shop
 *   - /eat-drink/             culvers_eat_drink
 *   - /careers/               culvers_career
 *   - /latest-events/         culvers_event
 *   - /latest-offers/         culvers_offer
 *   - /latest-news/           culvers_news
 *   - /whats-on/              flexible page that surfaces the three above
 *
 * Bumping {@see self::REWRITE_VERSION} flushes rewrite rules once on the
 * next request so new post types, taxonomies, and slug moves pick up their
 * archive + single permalinks without anyone touching Settings → Permalinks
 * by hand.
 */
final class DirectoryPostTypes
{
    /**
     * Bump history:
     *   v3 → original baseline (events at /whats-on/).
     *   v4 → events nested under /whats-on/latest-events/, offers + news
     *        added under /whats-on/latest-{offers,news}/.
     *   v5 → events / offers / news flattened to top-level
     *        /latest-{events,offers,news}/ — `/whats-on/` is a landing
     *        page (Page CPT), not an archive parent.
     */
    private const REWRITE_VERSION = 5;

    public static function register(): void
    {
        self::registerShopPostType();
        self::registerShopTaxonomies();

        self::registerEatDrinkPostType();
        self::registerEatDrinkTaxonomies();

        self::registerEventPostType();
        self::registerEventTaxonomies();

        self::registerOfferPostType();
        self::registerOfferTaxonomies();

        self::registerNewsPostType();
        self::registerNewsTaxonomies();

        self::registerCareerPostType();
        self::registerCareerTaxonomies();

        self::maybeFlushRewrites();
        self::adjustArchiveQueries();
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

    private static function registerEatDrinkPostType(): void
    {
        register_post_type(
            'culvers_eat_drink',
            [
                'labels' => [
                    'name' => __('Eat & Drink', 'culvers'),
                    'singular_name' => __('Eat & Drink venue', 'culvers'),
                    'add_new_item' => __('Add New venue', 'culvers'),
                    'edit_item' => __('Edit venue', 'culvers'),
                    'view_item' => __('View venue', 'culvers'),
                    'search_items' => __('Search venues', 'culvers'),
                    'not_found' => __('No venues found.', 'culvers'),
                    'all_items' => __('All venues', 'culvers'),
                    'archives' => __('Eat & Drink directory', 'culvers'),
                ],
                'description' => __('Restaurants, cafés, bars and takeaways in the centre.', 'culvers'),
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true,
                'has_archive' => 'eat-drink',
                'menu_icon' => 'dashicons-coffee',
                'menu_position' => 23,
                'supports' => ['title', 'thumbnail', 'editor', 'excerpt', 'revisions'],
                'rewrite' => ['slug' => 'eat-drink', 'with_front' => false],
                'show_in_rest' => true,
                'capability_type' => 'post',
            ]
        );
    }

    private static function registerEatDrinkTaxonomies(): void
    {
        register_taxonomy(
            'culvers_eat_drink_category',
            ['culvers_eat_drink'],
            [
                'labels' => [
                    'name' => __('Cuisine categories', 'culvers'),
                    'singular_name' => __('Cuisine category', 'culvers'),
                    'search_items' => __('Search cuisines', 'culvers'),
                    'all_items' => __('All cuisines', 'culvers'),
                    'edit_item' => __('Edit cuisine', 'culvers'),
                    'update_item' => __('Update cuisine', 'culvers'),
                    'add_new_item' => __('Add cuisine', 'culvers'),
                    'new_item_name' => __('New cuisine name', 'culvers'),
                ],
                'hierarchical' => true,
                'public' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'show_in_nav_menus' => false,
                'show_in_rest' => true,
                'rewrite' => ['slug' => 'eat-drink-category', 'with_front' => false],
            ]
        );

        register_taxonomy(
            'culvers_eat_drink_type',
            ['culvers_eat_drink'],
            [
                'labels' => [
                    'name' => __('Venue types', 'culvers'),
                    'singular_name' => __('Venue type', 'culvers'),
                    'search_items' => __('Search venue types', 'culvers'),
                    'all_items' => __('All venue types', 'culvers'),
                    'edit_item' => __('Edit venue type', 'culvers'),
                    'update_item' => __('Update venue type', 'culvers'),
                    'add_new_item' => __('Add venue type', 'culvers'),
                    'new_item_name' => __('New venue type name', 'culvers'),
                ],
                'hierarchical' => true,
                'public' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'show_in_nav_menus' => false,
                'show_in_rest' => true,
                'rewrite' => ['slug' => 'eat-drink-type', 'with_front' => false],
            ]
        );
    }

    private static function registerEventPostType(): void
    {
        register_post_type(
            'culvers_event',
            [
                'labels' => [
                    'name' => __('Latest Events', 'culvers'),
                    'singular_name' => __('Event', 'culvers'),
                    'add_new_item' => __('Add New Event', 'culvers'),
                    'edit_item' => __('Edit Event', 'culvers'),
                    'view_item' => __('View Event', 'culvers'),
                    'search_items' => __('Search Events', 'culvers'),
                    'not_found' => __('No events found.', 'culvers'),
                    'all_items' => __('All Events', 'culvers'),
                    'archives' => __('Latest Events', 'culvers'),
                ],
                'description' => __('Workshops, performances, family events and seasonal moments.', 'culvers'),
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true,
                /* Archive lives at the top level — `/whats-on/` is a curated
                   landing page (Page CPT) that surfaces the three sibling
                   archives (latest-events, latest-offers, latest-news) but
                   isn't itself an archive parent. */
                'has_archive' => 'latest-events',
                'menu_icon' => 'dashicons-calendar-alt',
                'menu_position' => 24,
                'supports' => ['title', 'thumbnail', 'editor', 'excerpt', 'revisions'],
                'rewrite' => ['slug' => 'latest-events', 'with_front' => false],
                'show_in_rest' => true,
                'capability_type' => 'post',
            ]
        );
    }

    private static function registerEventTaxonomies(): void
    {
        register_taxonomy(
            'culvers_event_category',
            ['culvers_event'],
            [
                'labels' => [
                    'name' => __('Event categories', 'culvers'),
                    'singular_name' => __('Event category', 'culvers'),
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
                'rewrite' => ['slug' => 'event-category', 'with_front' => false],
            ]
        );
    }

    private static function registerOfferPostType(): void
    {
        register_post_type(
            'culvers_offer',
            [
                'labels' => [
                    'name' => __('Latest Offers', 'culvers'),
                    'singular_name' => __('Offer', 'culvers'),
                    'add_new_item' => __('Add New Offer', 'culvers'),
                    'edit_item' => __('Edit Offer', 'culvers'),
                    'view_item' => __('View Offer', 'culvers'),
                    'search_items' => __('Search Offers', 'culvers'),
                    'not_found' => __('No offers found.', 'culvers'),
                    'all_items' => __('All Offers', 'culvers'),
                    'archives' => __('Latest Offers', 'culvers'),
                ],
                'description' => __('Promotions, discounts and brand campaigns from across the centre.', 'culvers'),
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true,
                'has_archive' => 'latest-offers',
                'menu_icon' => 'dashicons-tag',
                'menu_position' => 24,
                'supports' => ['title', 'thumbnail', 'editor', 'excerpt', 'revisions'],
                'rewrite' => ['slug' => 'latest-offers', 'with_front' => false],
                'show_in_rest' => true,
                'capability_type' => 'post',
            ]
        );
    }

    private static function registerOfferTaxonomies(): void
    {
        register_taxonomy(
            'culvers_offer_category',
            ['culvers_offer'],
            [
                'labels' => [
                    'name' => __('Offer categories', 'culvers'),
                    'singular_name' => __('Offer category', 'culvers'),
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
                'rewrite' => ['slug' => 'offer-category', 'with_front' => false],
            ]
        );
    }

    private static function registerNewsPostType(): void
    {
        register_post_type(
            'culvers_news',
            [
                'labels' => [
                    'name' => __('Latest News', 'culvers'),
                    'singular_name' => __('News article', 'culvers'),
                    'add_new_item' => __('Add New Article', 'culvers'),
                    'edit_item' => __('Edit Article', 'culvers'),
                    'view_item' => __('View Article', 'culvers'),
                    'search_items' => __('Search News', 'culvers'),
                    'not_found' => __('No articles found.', 'culvers'),
                    'all_items' => __('All Articles', 'culvers'),
                    'archives' => __('Latest News', 'culvers'),
                ],
                'description' => __('Centre updates, retailer announcements and editorial articles.', 'culvers'),
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true,
                'has_archive' => 'latest-news',
                'menu_icon' => 'dashicons-megaphone',
                'menu_position' => 24,
                'supports' => ['title', 'thumbnail', 'editor', 'excerpt', 'revisions'],
                'rewrite' => ['slug' => 'latest-news', 'with_front' => false],
                'show_in_rest' => true,
                'capability_type' => 'post',
            ]
        );
    }

    private static function registerNewsTaxonomies(): void
    {
        register_taxonomy(
            'culvers_news_category',
            ['culvers_news'],
            [
                'labels' => [
                    'name' => __('News categories', 'culvers'),
                    'singular_name' => __('News category', 'culvers'),
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
                'rewrite' => ['slug' => 'news-category', 'with_front' => false],
            ]
        );
    }

    private static function registerCareerPostType(): void
    {
        register_post_type(
            'culvers_career',
            [
                'labels' => [
                    'name' => __('Careers', 'culvers'),
                    'singular_name' => __('Career', 'culvers'),
                    'add_new_item' => __('Add New Role', 'culvers'),
                    'edit_item' => __('Edit Role', 'culvers'),
                    'view_item' => __('View Role', 'culvers'),
                    'search_items' => __('Search Roles', 'culvers'),
                    'not_found' => __('No roles found.', 'culvers'),
                    'all_items' => __('All Roles', 'culvers'),
                    'archives' => __('Careers', 'culvers'),
                ],
                'description' => __('Open roles at Culver Square and across the centre.', 'culvers'),
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true,
                'has_archive' => 'careers',
                'menu_icon' => 'dashicons-businessperson',
                'menu_position' => 25,
                'supports' => ['title', 'thumbnail', 'editor', 'excerpt', 'revisions'],
                'rewrite' => ['slug' => 'careers', 'with_front' => false],
                'show_in_rest' => true,
                'capability_type' => 'post',
            ]
        );
    }

    private static function registerCareerTaxonomies(): void
    {
        register_taxonomy(
            'culvers_career_department',
            ['culvers_career'],
            [
                'labels' => [
                    'name' => __('Departments', 'culvers'),
                    'singular_name' => __('Department', 'culvers'),
                    'search_items' => __('Search departments', 'culvers'),
                    'all_items' => __('All departments', 'culvers'),
                    'edit_item' => __('Edit department', 'culvers'),
                    'update_item' => __('Update department', 'culvers'),
                    'add_new_item' => __('Add department', 'culvers'),
                    'new_item_name' => __('New department name', 'culvers'),
                ],
                'hierarchical' => true,
                'public' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'show_in_nav_menus' => false,
                'show_in_rest' => true,
                'rewrite' => ['slug' => 'department', 'with_front' => false],
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

    private static function adjustArchiveQueries(): void
    {
        add_action(
            'pre_get_posts',
            static function (\WP_Query $query): void {
                if (is_admin() || ! $query->is_main_query()) {
                    return;
                }

                if (
                    $query->is_post_type_archive('culvers_shop')
                    || $query->is_post_type_archive('culvers_eat_drink')
                    || $query->is_post_type_archive('culvers_career')
                ) {
                    $query->set('posts_per_page', -1);
                    $query->set('orderby', 'title');
                    $query->set('order', 'ASC');
                    return;
                }

                if ($query->is_post_type_archive('culvers_event')) {
                    /* Chronological listing of upcoming events; "date" sort is post publish date,
                       which works well as a default until an explicit start-date meta is wired in. */
                    $query->set('posts_per_page', -1);
                    $query->set('orderby', 'date');
                    $query->set('order', 'ASC');
                    return;
                }

                if (
                    $query->is_post_type_archive('culvers_offer')
                    || $query->is_post_type_archive('culvers_news')
                ) {
                    /* Offers + News surface newest-first — recency matters more
                       than alphabetical for time-sensitive promos and editorial. */
                    $query->set('posts_per_page', -1);
                    $query->set('orderby', 'date');
                    $query->set('order', 'DESC');
                }
            },
            10
        );
    }
}
