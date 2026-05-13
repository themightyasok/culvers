<?php

declare(strict_types=1);

namespace App\Directory;

use WP_Query;

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
 *
 *   - /shops/                 culvers_shop
 *   - /eat-drink/             culvers_eat_drink
 *   - /careers/               culvers_career
 *   - /latest-events/         culvers_event
 *   - /latest-offers/         culvers_offer
 *   - /latest-news/           culvers_news
 *   - /whats-on/              flexible page that surfaces the three above
 *
 * ## Adding a new directory CPT
 *
 * 1. Append a row to {@see self::cpts()} with the CPT slug as the key.
 *    Provide labels, description, archive slug, menu icon, menu position,
 *    and (optionally) an `archive_sort` strategy.
 * 2. Append the CPT's taxonomies to {@see self::taxonomies()}, keyed by
 *    taxonomy slug, with `post_types` listing every CPT they attach to.
 * 3. Bump {@see self::REWRITE_VERSION} so flush_rewrite_rules() runs once
 *    on the next request and the new permalinks light up.
 *
 * Everything else — the universal `register_post_type` defaults (public,
 * show_ui, capability_type, supports, …) and the `register_taxonomy`
 * defaults (hierarchical, show_admin_column, show_in_rest, …) — is folded
 * in by {@see self::buildPostType()} / {@see self::buildTaxonomy()} so the
 * config table only carries the bits that actually vary per CPT.
 */
final class DirectoryPostTypes
{
    /**
     * Bump history:
     *
     *   v3 → original baseline (events at /whats-on/).
     *   v4 → events nested under /whats-on/latest-events/, offers + news
     *        added under /whats-on/latest-{offers,news}/.
     *   v5 → events / offers / news flattened to top-level
     *        /latest-{events,offers,news}/ — `/whats-on/` is a landing
     *        page (Page CPT), not an archive parent.
     *   v6 → no URL changes; refactor flush to keep behaviour identical
     *        after the config-table consolidation. Bumping forces a one-
     *        time rewrite flush so any cached rules from the old register
     *        path are replaced.
     */
    private const REWRITE_VERSION = 6;

    /** Sort newest-first on the post-type archive (offers, news). */
    private const SORT_NEWEST_FIRST = 'date-desc';

    /** Sort oldest-first on the post-type archive (events). */
    private const SORT_OLDEST_FIRST = 'date-asc';

    /** Sort alphabetically by title (shops, eat-drink, careers). */
    private const SORT_ALPHABETICAL = 'title-asc';

    public static function register(): void
    {
        foreach (self::cpts() as $cptSlug => $cfg) {
            self::buildPostType($cptSlug, $cfg);
        }

        foreach (self::taxonomies() as $taxonomySlug => $cfg) {
            self::buildTaxonomy($taxonomySlug, $cfg);
        }

        self::maybeFlushRewrites();
        self::adjustArchiveQueries();
        self::registerCareersArchivePins();
    }

    /**
     * Per-CPT config table.
     *
     * Each entry holds only the fields that vary between directory CPTs —
     * universal defaults (public, show_ui, supports, capability_type, …)
     * are filled in by {@see self::buildPostType()}.
     *
     * Field reference:
     *   • `description`      — admin description for the CPT.
     *   • `archive_slug`     — front-end archive path AND single-post slug
     *                          prefix. Used as both `has_archive` and
     *                          `rewrite[slug]` so the archive URL and the
     *                          single-post URL share a consistent prefix.
     *   • `menu_icon`        — Dashicons name for the WP-Admin sidebar.
     *   • `menu_position`    — sidebar position (lower = higher up).
     *   • `archive_sort`     — one of `SORT_*` constants. Defaults to
     *                          alphabetical.
     *   • `labels`           — full WP `register_post_type` labels array.
     *                          Stored as literal `__()` calls so gettext
     *                          tooling can extract every msgid.
     *
     * @return array<string, array{
     *     description: string,
     *     archive_slug: string,
     *     menu_icon: string,
     *     menu_position: int,
     *     archive_sort?: string,
     *     labels: array<string, string>,
     * }>
     */
    private static function cpts(): array
    {
        return [
            'culvers_shop' => [
                'description' => __('Retailers and brands in the centre directory.', 'culvers'),
                'archive_slug' => 'shops',
                'menu_icon' => 'dashicons-store',
                'menu_position' => 22,
                'archive_sort' => self::SORT_ALPHABETICAL,
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
            ],

            'culvers_eat_drink' => [
                'description' => __('Restaurants, cafés, bars and takeaways in the centre.', 'culvers'),
                'archive_slug' => 'eat-drink',
                'menu_icon' => 'dashicons-coffee',
                'menu_position' => 23,
                'archive_sort' => self::SORT_ALPHABETICAL,
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
            ],

            'culvers_event' => [
                'description' => __('Workshops, performances, family events and seasonal moments.', 'culvers'),
                /* Archive lives at the top level — `/whats-on/` is a curated
                   landing page (Page CPT) that surfaces the three sibling
                   archives (latest-events, latest-offers, latest-news) but
                   isn't itself an archive parent. */
                'archive_slug' => 'latest-events',
                'menu_icon' => 'dashicons-calendar-alt',
                'menu_position' => 24,
                'archive_sort' => self::SORT_OLDEST_FIRST,
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
            ],

            'culvers_offer' => [
                'description' => __('Promotions, discounts and brand campaigns from across the centre.', 'culvers'),
                'archive_slug' => 'latest-offers',
                'menu_icon' => 'dashicons-tag',
                'menu_position' => 24,
                'archive_sort' => self::SORT_NEWEST_FIRST,
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
            ],

            'culvers_news' => [
                'description' => __('Centre updates, retailer announcements and editorial articles.', 'culvers'),
                'archive_slug' => 'latest-news',
                'menu_icon' => 'dashicons-megaphone',
                'menu_position' => 24,
                'archive_sort' => self::SORT_NEWEST_FIRST,
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
            ],

            'culvers_career' => [
                'description' => __('Open roles at Culver Square and across the centre.', 'culvers'),
                'archive_slug' => 'careers',
                'menu_icon' => 'dashicons-businessperson',
                'menu_position' => 25,
                'archive_sort' => self::SORT_ALPHABETICAL,
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
            ],
        ];
    }

    /**
     * Per-taxonomy config table.
     *
     * Field reference:
     *   • `post_types`  — list of CPT slugs the taxonomy attaches to.
     *   • `rewrite_slug` — front-end URL prefix for term archives.
     *   • `labels`      — `register_taxonomy` labels array (full literal
     *                     strings for gettext extraction).
     *
     * @return array<string, array{
     *     post_types: list<string>,
     *     rewrite_slug: string,
     *     labels: array<string, string>,
     * }>
     */
    private static function taxonomies(): array
    {
        return [
            'culvers_shop_category' => [
                'post_types' => ['culvers_shop'],
                'rewrite_slug' => 'shop-category',
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
            ],

            'culvers_shop_type' => [
                'post_types' => ['culvers_shop'],
                'rewrite_slug' => 'shop-type',
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
            ],

            'culvers_eat_drink_category' => [
                'post_types' => ['culvers_eat_drink'],
                'rewrite_slug' => 'eat-drink-category',
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
            ],

            'culvers_eat_drink_type' => [
                'post_types' => ['culvers_eat_drink'],
                'rewrite_slug' => 'eat-drink-type',
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
            ],

            'culvers_event_category' => [
                'post_types' => ['culvers_event'],
                'rewrite_slug' => 'event-category',
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
            ],

            'culvers_offer_category' => [
                'post_types' => ['culvers_offer'],
                'rewrite_slug' => 'offer-category',
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
            ],

            'culvers_news_category' => [
                'post_types' => ['culvers_news'],
                'rewrite_slug' => 'news-category',
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
            ],

            'culvers_career_department' => [
                'post_types' => ['culvers_career'],
                'rewrite_slug' => 'department',
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
            ],
        ];
    }

    /**
     * Fold a per-CPT config row into the universal `register_post_type`
     * defaults shared by every directory CPT and ship it to WP.
     *
     * @param array{
     *     description: string,
     *     archive_slug: string,
     *     menu_icon: string,
     *     menu_position: int,
     *     archive_sort?: string,
     *     labels: array<string, string>,
     * } $cfg
     */
    private static function buildPostType(string $slug, array $cfg): void
    {
        register_post_type(
            $slug,
            [
                'labels' => $cfg['labels'],
                'description' => $cfg['description'],
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true,
                /* Same string for has_archive AND rewrite[slug] keeps the
                   archive URL (`/<slug>/`) and the single-post URL
                   (`/<slug>/<post-slug>/`) consistently prefixed. */
                'has_archive' => $cfg['archive_slug'],
                'menu_icon' => $cfg['menu_icon'],
                'menu_position' => $cfg['menu_position'],
                'supports' => ['title', 'thumbnail', 'editor', 'excerpt', 'revisions'],
                'rewrite' => ['slug' => $cfg['archive_slug'], 'with_front' => false],
                'show_in_rest' => true,
                'capability_type' => 'post',
            ]
        );
    }

    /**
     * Fold a per-taxonomy config row into the universal `register_taxonomy`
     * defaults shared by every directory taxonomy and ship it to WP.
     *
     * @param array{
     *     post_types: list<string>,
     *     rewrite_slug: string,
     *     labels: array<string, string>,
     * } $cfg
     */
    private static function buildTaxonomy(string $slug, array $cfg): void
    {
        register_taxonomy(
            $slug,
            $cfg['post_types'],
            [
                'labels' => $cfg['labels'],
                'hierarchical' => true,
                'public' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'show_in_nav_menus' => false,
                'show_in_rest' => true,
                'rewrite' => ['slug' => $cfg['rewrite_slug'], 'with_front' => false],
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

    /**
     * Featured role(s) surfaced first on /careers/ while preserving title order for remaining cards.
     *
     * @see apply_filters('culvers_careers_archive_pin_slug', string $default, WP_Query $query)
     */
    private static function registerCareersArchivePins(): void
    {
        add_filter(
            'the_posts',
            static function (array $posts, WP_Query $query): array {
                if (! $query->is_main_query()) {
                    return $posts;
                }

                if (! $query->is_post_type_archive('culvers_career')) {
                    return $posts;
                }

                /** @var mixed $pinnedSlugRaw */
                $pinnedSlugRaw = apply_filters(
                    'culvers_careers_archive_pin_slug',
                    'senior-supervisor',
                    $query,
                );

                $pinnedSlug = is_string($pinnedSlugRaw) ? trim($pinnedSlugRaw) : '';
                if ($pinnedSlug === '') {
                    return $posts;
                }

                /** @var list<\WP_Post> $pinned */
                $pinned = [];
                /** @var list<\WP_Post> $tail */
                $tail = [];

                foreach ($posts as $post) {
                    if (! $post instanceof \WP_Post) {
                        continue;
                    }
                    if ($post->post_name === $pinnedSlug) {
                        $pinned[] = $post;

                        continue;
                    }
                    $tail[] = $post;
                }

                if ($pinned === []) {
                    return $posts;
                }

                return array_merge($pinned, $tail);
            },
            10,
            2
        );
    }

    /**
     * Override `posts_per_page` + sort order on every directory archive.
     *
     * The per-CPT sort strategy lives in the {@see self::cpts()} config row
     * (`archive_sort` → one of the `SORT_*` constants), so adding a new
     * directory CPT only needs a config row, not another `if` branch.
     */
    private static function adjustArchiveQueries(): void
    {
        add_action(
            'pre_get_posts',
            static function (WP_Query $query): void {
                if (is_admin() || ! $query->is_main_query()) {
                    return;
                }

                foreach (self::cpts() as $cptSlug => $cfg) {
                    if (! $query->is_post_type_archive($cptSlug)) {
                        continue;
                    }
                    $sort = $cfg['archive_sort'] ?? self::SORT_ALPHABETICAL;
                    $query->set('posts_per_page', -1);
                    [$orderby, $order] = self::sortToQueryArgs($sort);
                    $query->set('orderby', $orderby);
                    $query->set('order', $order);

                    return;
                }
            },
            10
        );
    }

    /**
     * @return array{0: string, 1: string} `[orderby, order]` pair for `WP_Query`.
     */
    private static function sortToQueryArgs(string $sort): array
    {
        return match ($sort) {
            /* Chronological listing — works as a default until an explicit
               start-date meta gets wired in for events. */
            self::SORT_OLDEST_FIRST => ['date', 'ASC'],
            /* Recency matters more than alphabetical for time-sensitive
               promos and editorial. */
            self::SORT_NEWEST_FIRST => ['date', 'DESC'],
            default => ['title', 'ASC'],
        };
    }
}
