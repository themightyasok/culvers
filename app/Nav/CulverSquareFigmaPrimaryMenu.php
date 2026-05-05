<?php

declare(strict_types=1);

namespace App\Nav;

/**
 * Installs the Culver Square primary mega menu from the Developer Release Figma file,
 * sideloads mega hero images into the Media Library, and attaches previews to submenu rows.
 *
 * Disable via {@see self::OPTION_DISABLED}. Remote URLs are only used if a download fails.
 */
final class CulverSquareFigmaPrimaryMenu
{
    public const OPTION_TERM_ID = 'culvers_figma_primary_menu_term_id';

    public const OPTION_DISABLED = 'culvers_disable_figma_primary_menu_install';

    /** @var non-empty-string */
    private const OPTION_ATTACHMENT_MAP = 'culvers_figma_panel_attachment_map';

    /** Mega-panel hero images — nodes 72:4967, 72:5002, 72:4938, 72:5031, 72:5058 */
    private const IMG_SHOP = 'https://www.figma.com/api/mcp/asset/5738e923-4d8f-46f1-a22d-b83804ea27f4';

    private const IMG_EAT_DRINK = 'https://www.figma.com/api/mcp/asset/ce6da294-0a1f-4517-9ce0-92b8ce6fa117';

    private const IMG_PLAN_VISIT = 'https://www.figma.com/api/mcp/asset/0b682574-908b-40cb-bbfa-f4fa5619cc25';

    private const IMG_WHATS_ON = 'https://www.figma.com/api/mcp/asset/c9a0ed21-5020-4863-afd0-d74cf7385eef';

    private const IMG_GUEST_SERVICES = 'https://www.figma.com/api/mcp/asset/6c9ddd96-bd42-4d9c-a970-5d0952f53ad4';

    public static function maybeInstall(): void
    {
        if ((bool) get_theme_mod(self::OPTION_DISABLED, false)) {
            return;
        }

        if (! current_theme_supports('menus')) {
            return;
        }

        $locations = get_nav_menu_locations();
        $assigned = isset($locations['primary_navigation']) ? (int) $locations['primary_navigation'] : 0;

        if ($assigned > 0) {
            $term = wp_get_nav_menu_object($assigned);
            if ($term instanceof \WP_Term) {
                $items = wp_get_nav_menu_items($assigned);
                if (is_array($items) && $items !== []) {
                    return;
                }
            }
        }

        $menuId = self::resolveMenuId();
        if ($menuId <= 0) {
            return;
        }

        $items = wp_get_nav_menu_items($menuId);
        if (! is_array($items) || $items === []) {
            self::populateItems($menuId);
        }

        /** @var array<string, int> $locations */
        $locations['primary_navigation'] = $menuId;
        set_theme_mod('nav_menu_locations', $locations);

        update_option(self::OPTION_TERM_ID, (string) $menuId, true);

        self::hydrateAttachmentPreviewsForMenu($menuId);
    }

    /**
     * Upgrade submenu items that still only store remote URLs into attachment previews.
     */
    public static function maybeHydratePreviewAttachments(): void
    {
        if ((bool) get_theme_mod(self::OPTION_DISABLED, false)) {
            return;
        }

        $locations = get_nav_menu_locations();
        $assigned = isset($locations['primary_navigation']) ? (int) $locations['primary_navigation'] : 0;

        if ($assigned <= 0 || ! self::menuNeedsPreviewHydration($assigned)) {
            return;
        }

        if (get_transient('culvers_figma_preview_hydrate')) {
            return;
        }

        set_transient('culvers_figma_preview_hydrate', '1', 120);
        try {
            self::hydrateAttachmentPreviewsForMenu($assigned);
        } finally {
            delete_transient('culvers_figma_preview_hydrate');
        }
    }

    private static function menuNeedsPreviewHydration(int $menuId): bool
    {
        foreach ((array) wp_get_nav_menu_items($menuId) as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }
            if ((int) get_post_meta($item->ID, '_menu_item_menu_item_parent', true) === 0) {
                continue;
            }
            $url = trim((string) get_post_meta($item->ID, NavMenuItemMeta::META_PREVIEW_URL, true));
            $aid = (int) get_post_meta($item->ID, NavMenuItemMeta::META_PREVIEW_ATTACHMENT, true);
            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) && $aid <= 0) {
                return true;
            }
        }

        return false;
    }

    private static function hydrateAttachmentPreviewsForMenu(int $menuId): void
    {
        foreach ((array) wp_get_nav_menu_items($menuId) as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }
            if ((int) get_post_meta($item->ID, '_menu_item_menu_item_parent', true) === 0) {
                continue;
            }
            $url = trim((string) get_post_meta($item->ID, NavMenuItemMeta::META_PREVIEW_URL, true));
            $aid = (int) get_post_meta($item->ID, NavMenuItemMeta::META_PREVIEW_ATTACHMENT, true);
            if ($aid > 0 || $url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $attachId = self::attachmentIdForSourceUrl($url);
            if ($attachId > 0) {
                update_post_meta($item->ID, NavMenuItemMeta::META_PREVIEW_ATTACHMENT, $attachId);
                delete_post_meta($item->ID, NavMenuItemMeta::META_PREVIEW_URL);
            }
        }
    }

    /**
     * Download once per URL; cache in option map for reuse across submenu rows.
     */
    private static function attachmentIdForSourceUrl(string $url): int
    {
        /** @var array<string, int> $map */
        $map = get_option(self::OPTION_ATTACHMENT_MAP, []);
        if (! is_array($map)) {
            $map = [];
        }

        if (isset($map[$url])) {
            $existing = (int) $map[$url];
            if ($existing > 0 && get_post_type($existing) === 'attachment') {
                return $existing;
            }
        }

        self::loadMediaAdminDependencies();

        if (! function_exists('download_url')) {
            return 0;
        }

        $tmp = download_url($url, 60);
        if (is_wp_error($tmp)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[culvers][figma-menu][download] ' . $tmp->get_error_message());
            }

            return 0;
        }

        $name = self::guessFilenameForTemp($tmp, $url);
        $file_array = [
            'name' => $name,
            'tmp_name' => $tmp,
        ];

        $id = media_handle_sideload($file_array, 0, __('Culver Square mega menu preview', 'culvers'));
        if (is_wp_error($id)) {
            if (is_string($tmp) && $tmp !== '' && file_exists($tmp)) {
                unlink($tmp);
            }
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[culvers][figma-menu][sideload] ' . $id->get_error_message());
            }

            return 0;
        }

        $map[$url] = (int) $id;
        update_option(self::OPTION_ATTACHMENT_MAP, $map, false);

        return (int) $id;
    }

    private static function loadMediaAdminDependencies(): void
    {
        if (! function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (! function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
        if (! function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
    }

    /** @param non-empty-string $tmp */
    private static function guessFilenameForTemp(string $tmp, string $url): string
    {
        $mime = '';
        if (function_exists('mime_content_type') && is_readable($tmp)) {
            $detected = mime_content_type($tmp);
            $mime = is_string($detected) ? $detected : '';
        }

        $ext = 'jpg';
        if (str_contains($mime, 'png')) {
            $ext = 'png';
        } elseif (str_contains($mime, 'webp')) {
            $ext = 'webp';
        } elseif (str_contains($mime, 'gif')) {
            $ext = 'gif';
        }

        return 'culver-square-mega-' . substr(md5($url), 0, 16) . '.' . $ext;
    }

    private static function resolveMenuId(): int
    {
        $savedId = (int) get_option(self::OPTION_TERM_ID, 0);
        if ($savedId > 0) {
            $existing = wp_get_nav_menu_object($savedId);
            if ($existing instanceof \WP_Term) {
                return $savedId;
            }
        }

        $created = wp_create_nav_menu(__('Culver Square — Figma primary', 'culvers'));
        if (is_wp_error($created)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[culvers][figma-menu] ' . $created->get_error_message());
            }

            return 0;
        }

        return (int) $created;
    }

    private static function populateItems(int $menuId): void
    {
        $shopArchive = self::shopArchiveUrl();

        foreach (self::branches() as $branch) {
            $parentResult = wp_update_nav_menu_item($menuId, 0, [
                'menu-item-title' => $branch['title'],
                'menu-item-url' => $branch['url'],
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
            ]);

            if (is_wp_error($parentResult)) {
                continue;
            }

            $parentDbId = (int) $parentResult;

            foreach ($branch['children'] as $childRow) {
                $childTitle = $childRow['title'];
                $previewUrl = $childRow['preview'];
                $isShopBranch = ($branch['parent_slug'] ?? '') === 'shop';
                $childUrl = $isShopBranch
                    ? esc_url_raw(add_query_arg(['category' => (string) $childRow['slug']], $shopArchive))
                    : '#';

                $childResult = wp_update_nav_menu_item($menuId, 0, [
                    'menu-item-title' => $childTitle,
                    'menu-item-url' => $childUrl,
                    'menu-item-status' => 'publish',
                    'menu-item-type' => 'custom',
                    'menu-item-parent-id' => $parentDbId,
                ]);

                if (is_wp_error($childResult)) {
                    continue;
                }

                $childItemId = (int) $childResult;
                self::applyPreviewToMenuItem($childItemId, $previewUrl);
            }
        }
    }

    /**
     * CLI / upgrades: assign distinct hover previews on submenu rows for the assigned primary menu.
     *
     * @return int Number of items updated
     */
    public static function cliSyncDistinctChildPreviews(): int
    {
        self::loadMediaAdminDependencies();

        $locations = get_nav_menu_locations();
        $menuId = isset($locations['primary_navigation']) ? (int) $locations['primary_navigation'] : 0;
        if ($menuId <= 0) {
            return 0;
        }

        $items = wp_get_nav_menu_items($menuId);
        if (! is_array($items) || $items === []) {
            return 0;
        }

        /** @var array<int, \WP_Post> $byId */
        $byId = [];
        foreach ($items as $post) {
            if ($post instanceof \WP_Post) {
                $byId[(int) $post->ID] = $post;
            }
        }

        $updated = 0;

        foreach ($items as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }
            $parentDbId = (int) get_post_meta((int) $item->ID, '_menu_item_menu_item_parent', true);
            if ($parentDbId === 0) {
                continue;
            }
            $parent = $byId[$parentDbId] ?? null;
            if (! $parent instanceof \WP_Post) {
                continue;
            }

            $previewUrl = self::resolveChildPreviewUrl($parent, $item);
            if (! is_string($previewUrl) || $previewUrl === '' || ! filter_var($previewUrl, FILTER_VALIDATE_URL)) {
                continue;
            }

            self::applyPreviewToMenuItem((int) $item->ID, $previewUrl);
            $updated++;
        }

        return $updated;
    }

    /**
     * Match submenu row to config by sanitized slug first, then by normalized title (handles apostrophes / casing).
     */
    private static function resolveChildPreviewUrl(\WP_Post $parent, \WP_Post $child): ?string
    {
        $lookup = self::childPreviewLookupMap();
        $parentSlug = sanitize_title((string) $parent->post_title);
        $childSlug = sanitize_title((string) $child->post_title);
        if (isset($lookup[$parentSlug][$childSlug])) {
            return $lookup[$parentSlug][$childSlug];
        }

        foreach (self::branches() as $branch) {
            if (! self::titlesMatch((string) $branch['title'], (string) $parent->post_title)) {
                continue;
            }
            foreach ($branch['children'] as $childRow) {
                if (self::titlesMatch((string) $childRow['title'], (string) $child->post_title)) {
                    return $childRow['preview'];
                }
            }
        }

        return null;
    }

    private static function titlesMatch(string $configuredTitle, string $menuTitle): bool
    {
        $norm = static function (string $s): string {
            $s = wp_strip_all_tags($s);
            $s = strtolower(trim($s));
            $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

            return $s;
        };

        return $norm($configuredTitle) === $norm($menuTitle);
    }

    public static function menuTitlesMatch(string $a, string $b): bool
    {
        return self::titlesMatch($a, $b);
    }

    /**
     * Top-level mega triggers (Shop, Eat & Drink, …) — used to distinguish stray Shop category rows.
     *
     * @return list<string>
     */
    public static function primaryMegaParentTitles(): array
    {
        $titles = [];
        foreach (self::branches() as $branch) {
            $titles[] = (string) $branch['title'];
        }

        return $titles;
    }

    public static function shopArchiveUrl(): string
    {
        $url = get_post_type_archive_link('culvers_shop');

        return is_string($url) && $url !== '' ? $url : home_url('/shops/');
    }

    /**
     * Mega-menu category rows under Shop → `/shops/?category={slug}`.
     *
     * @return list<array{title: string, url: string}>
     */
    public static function shopMegaCategoryLinkRows(): array
    {
        $archive = self::shopArchiveUrl();
        $out = [];

        foreach (self::branches() as $branch) {
            if (($branch['parent_slug'] ?? '') !== 'shop') {
                continue;
            }
            foreach ($branch['children'] as $childRow) {
                $out[] = [
                    'title' => (string) $childRow['title'],
                    'url' => esc_url_raw(add_query_arg(['category' => (string) $childRow['slug']], $archive)),
                ];
            }

            break;
        }

        return $out;
    }

    /**
     * @param non-empty-string $previewUrl
     */
    private static function applyPreviewToMenuItem(int $menuItemDbId, string $previewUrl): void
    {
        $attachId = self::attachmentIdForSourceUrl($previewUrl);
        if ($attachId > 0) {
            update_post_meta($menuItemDbId, NavMenuItemMeta::META_PREVIEW_ATTACHMENT, $attachId);
            delete_post_meta($menuItemDbId, NavMenuItemMeta::META_PREVIEW_URL);
        } else {
            update_post_meta($menuItemDbId, NavMenuItemMeta::META_PREVIEW_URL, esc_url_raw($previewUrl));
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function childPreviewLookupMap(): array
    {
        $map = [];
        foreach (self::branches() as $branch) {
            $p = $branch['parent_slug'];
            if (! isset($map[$p])) {
                $map[$p] = [];
            }
            foreach ($branch['children'] as $childRow) {
                $map[$p][$childRow['slug']] = $childRow['preview'];
            }
        }

        return $map;
    }

    /**
     * Stable demo imagery per submenu row (734×458 mega slot). Replace with production assets anytime.
     *
     * @return list<array{
     *     title: string,
     *     url: string,
     *     parent_slug: string,
     *     panel_image: string,
     *     children: list<array{slug: string, title: string, preview: string}>
     * }>
     */
    private static function branches(): array
    {
        $shopArchive = self::shopArchiveUrl();

        return [
            [
                'title' => __('Shop', 'culvers'),
                'url' => $shopArchive,
                'parent_slug' => 'shop',
                'panel_image' => self::IMG_SHOP,
                'children' => [
                    ['slug' => 'beauty-wellbeing', 'title' => __('Beauty & Wellbeing', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-shop-beauty-wellbeing/734/458'],
                    ['slug' => 'fashion', 'title' => __('Fashion', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-shop-fashion/734/458'],
                    ['slug' => 'jewellery', 'title' => __('Jewellery', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-shop-jewellery/734/458'],
                    ['slug' => 'toys-gifts', 'title' => __('Toys & Gifts', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-shop-toys-gifts/734/458'],
                    ['slug' => 'technology', 'title' => __('Technology', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-shop-technology/734/458'],
                    ['slug' => 'services', 'title' => __('Services', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-shop-services/734/458'],
                    ['slug' => 'home', 'title' => __('Home', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-shop-home/734/458'],
                ],
            ],
            [
                'title' => __('Eat & Drink', 'culvers'),
                'url' => '#',
                'parent_slug' => 'eat-drink',
                'panel_image' => self::IMG_EAT_DRINK,
                'children' => [
                    ['slug' => 'grab-go', 'title' => __('Grab & Go', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-eat-grab-go/734/458'],
                    ['slug' => 'restaurant', 'title' => __('Restaurant', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-eat-restaurant/734/458'],
                    ['slug' => 'healthy-options', 'title' => __('Healthy Options', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-eat-healthy/734/458'],
                    ['slug' => 'cafe', 'title' => __('Cafe', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-eat-cafe/734/458'],
                ],
            ],
            [
                'title' => __('Plan my visit', 'culvers'),
                'url' => '#',
                'parent_slug' => 'plan-my-visit',
                'panel_image' => self::IMG_PLAN_VISIT,
                'children' => [
                    ['slug' => 'getting-here', 'title' => __('Getting Here', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-plan-getting-here/734/458'],
                    ['slug' => 'centre-map', 'title' => __('Centre Map', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-plan-centre-map/734/458'],
                    ['slug' => 'accessible-access', 'title' => __('Accessible Access', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-plan-access/734/458'],
                    ['slug' => 'opening-hours', 'title' => __('Opening Hours', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-plan-hours/734/458'],
                ],
            ],
            [
                'title' => __("what's on", 'culvers'),
                'url' => '#',
                'parent_slug' => 'whats-on',
                'panel_image' => self::IMG_WHATS_ON,
                'children' => [
                    ['slug' => 'latest-events', 'title' => __('Latest Events', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-whats-events/734/458'],
                    ['slug' => 'latest-news', 'title' => __('Latest News', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-whats-news/734/458'],
                    ['slug' => 'latest-offers', 'title' => __('Latest Offers', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-whats-offers/734/458'],
                ],
            ],
            [
                'title' => __('Guest Services', 'culvers'),
                'url' => '#',
                'parent_slug' => 'guest-services',
                'panel_image' => self::IMG_GUEST_SERVICES,
                'children' => [
                    ['slug' => 'about-colchester', 'title' => __('About Colchester', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-guest-about/734/458'],
                    ['slug' => 'click-collect', 'title' => __('Click & Collect', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-guest-click-collect/734/458'],
                    ['slug' => 'security', 'title' => __('Security', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-guest-security/734/458'],
                    ['slug' => 'facilities', 'title' => __('Facilities', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-guest-facilities/734/458'],
                    ['slug' => 'faqs', 'title' => __("FAQ's", 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-guest-faq/734/458'],
                    ['slug' => 'contact-us', 'title' => __('Contact Us', 'culvers'), 'preview' => 'https://picsum.photos/seed/cs-guest-contact/734/458'],
                ],
            ],
        ];
    }
}
