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
            if ($tmp !== '' && file_exists($tmp)) {
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

    /**
     * Wipe and rebuild the menu assigned to {@see primary_navigation} so Appearance → Menus
     * matches the live mega nav (Shop + nested categories, not flat top-level categories).
     *
     * @return array{menu_id: int, deleted: int, dry_run: bool, top_level: int, children: int}
     */
    public static function rebuildAssignedPrimaryMenu(bool $dryRun = false): array
    {
        $locations = get_nav_menu_locations();
        $menuId = isset($locations['primary_navigation']) ? (int) $locations['primary_navigation'] : 0;
        if ($menuId <= 0) {
            $menuId = self::resolveMenuId();
        }

        if ($menuId <= 0) {
            return [
                'menu_id' => 0,
                'deleted' => 0,
                'dry_run' => $dryRun,
                'top_level' => 0,
                'children' => 0,
            ];
        }

        if (! $dryRun) {
            $locations['primary_navigation'] = $menuId;
            set_theme_mod('nav_menu_locations', $locations);
            update_option(self::OPTION_TERM_ID, (string) $menuId, true);
        }

        $items = wp_get_nav_menu_items($menuId);
        $deleted = 0;
        if (is_array($items)) {
            $deleted = count($items);
            if (! $dryRun) {
                foreach ($items as $item) {
                    if ($item instanceof \WP_Post) {
                        wp_delete_post((int) $item->ID, true);
                    }
                }
            }
        }

        $topLevel = 0;
        $children = 0;
        foreach (self::branches() as $branch) {
            ++$topLevel;
            $children += count($branch['children']);
        }

        if (! $dryRun) {
            self::populateItems($menuId);
            PrimaryNavLinkSync::syncAssignedPrimaryMenu();
            self::hydrateAttachmentPreviewsForMenu($menuId);
        }

        return [
            'menu_id' => $menuId,
            'deleted' => $deleted,
            'dry_run' => $dryRun,
            'top_level' => $topLevel,
            'children' => $children,
        ];
    }

    private static function populateItems(int $menuId): void
    {
        $shopArchive = self::shopArchiveUrl();
        $position = 1;

        foreach (self::branches() as $branch) {
            $parentResult = wp_update_nav_menu_item($menuId, 0, [
                'menu-item-title' => $branch['title'],
                'menu-item-url' => $branch['url'],
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
                'menu-item-position' => $position,
            ]);
            ++$position;

            if (is_wp_error($parentResult)) {
                continue;
            }

            $parentDbId = (int) $parentResult;

            foreach ($branch['children'] as $childRow) {
                $childTitle = $childRow['title'];
                $previewUrl = $childRow['preview'];
                $isShopBranch = $branch['parent_slug'] === 'shop';
                $childSlug = (string) $childRow['slug'];

                /* URL resolution rules:
                 *  - Shop children deep-link into the directory archive via
                 *    `?category=` (single-slug categories — keep as-is).
                 *  - What's On children resolve to top-level archives at
                 *    `/<slug>/` (Latest Events / Offers / News are first-
                 *    class CPTs; the parent What's On is a landing page,
                 *    not an archive container).
                 *  - Children whose slug contains a "/" are treated as
                 *    nested paths under the site root (kept for any future
                 *    nested sub-archives — currently unused).
                 *  - Everything else falls back to "#" so the menu item
                 *    survives until {@see PrimaryNavLinkSync} or an editor
                 *    wires up the destination. */
                if ($isShopBranch) {
                    $childUrl = esc_url_raw(
                        add_query_arg(['category' => $childSlug], $shopArchive)
                    );
                } elseif ($branch['parent_slug'] === 'whats-on') {
                    $path = '/' . ltrim($childSlug, '/') . '/';
                    $childUrl = function_exists('home_url') ? home_url($path) : $path;
                } elseif ($branch['parent_slug'] === 'careers') {
                    $childUrl = esc_url_raw(self::careerArchiveUrl());
                } elseif (str_contains($childSlug, '/')) {
                    $path = '/' . trim($childSlug, '/') . '/';
                    $childUrl = function_exists('home_url') ? home_url($path) : $path;
                } else {
                    $childUrl = '#';
                }

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
     * WordPress attachment URL for a mega submenu row when menu-item meta is empty but
     * parent/child titles still match the Figma bootstrap config.
     *
     * Uses {@see self::OPTION_ATTACHMENT_MAP} populated during Figma sideload — no network
     * I/O on the front-end request.
     */
    public static function localAttachmentPreviewForMenuItems(\WP_Post $parent, \WP_Post $child): string
    {
        $src = self::resolveChildPreviewUrl($parent, $child);
        if ($src === null || $src === '' || ! filter_var($src, FILTER_VALIDATE_URL)) {
            return '';
        }

        /** @var mixed $map */
        $map = get_option(self::OPTION_ATTACHMENT_MAP, []);
        if (! is_array($map) || ! isset($map[$src])) {
            return '';
        }

        $aid = (int) $map[$src];
        if ($aid <= 0 || get_post_type($aid) !== 'attachment') {
            return '';
        }

        $url = wp_get_attachment_image_url($aid, 'large');

        return is_string($url) && $url !== '' ? esc_url($url) : '';
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

    public static function careerArchiveUrl(): string
    {
        $url = get_post_type_archive_link('culvers_career');

        return is_string($url) && $url !== '' ? $url : home_url('/careers/');
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
            if ($branch['parent_slug'] !== 'shop') {
                continue;
            }
            foreach ($branch['children'] as $childRow) {
                $out[] = [
                    'title' => $childRow['title'],
                    'url' => esc_url_raw(add_query_arg(['category' => $childRow['slug']], $archive)),
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
     * Mega-nav configuration for the Figma-bootstrapped primary menu.
     *
     * Per-child `preview` defaults to the parent branch's `panel_image` so
     * every hover preview ships a real Figma asset (rather than the prior
     * `picsum.photos` random landscapes that obviously aren't from the
     * developer release). Editors can override the per-row preview via the
     * Mega menu item meta box once a brand-specific export is available.
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
                    ['slug' => 'beauty-wellbeing', 'title' => __('Beauty & Wellbeing', 'culvers'), 'preview' => self::IMG_SHOP],
                    ['slug' => 'fashion', 'title' => __('Fashion', 'culvers'), 'preview' => self::IMG_SHOP],
                    ['slug' => 'jewellery', 'title' => __('Jewellery', 'culvers'), 'preview' => self::IMG_SHOP],
                    ['slug' => 'toys-gifts', 'title' => __('Toys & Gifts', 'culvers'), 'preview' => self::IMG_SHOP],
                    ['slug' => 'technology', 'title' => __('Technology', 'culvers'), 'preview' => self::IMG_SHOP],
                    ['slug' => 'services', 'title' => __('Services', 'culvers'), 'preview' => self::IMG_SHOP],
                    ['slug' => 'home', 'title' => __('Home', 'culvers'), 'preview' => self::IMG_SHOP],
                ],
            ],
            [
                'title' => __('Eat & Drink', 'culvers'),
                'url' => '#',
                'parent_slug' => 'eat-drink',
                'panel_image' => self::IMG_EAT_DRINK,
                'children' => [
                    ['slug' => 'grab-go', 'title' => __('Grab & Go', 'culvers'), 'preview' => self::IMG_EAT_DRINK],
                    ['slug' => 'restaurants', 'title' => __('Restaurants', 'culvers'), 'preview' => self::IMG_EAT_DRINK],
                    ['slug' => 'healthy-options', 'title' => __('Healthy Options', 'culvers'), 'preview' => self::IMG_EAT_DRINK],
                    ['slug' => 'cafes', 'title' => __('Cafés', 'culvers'), 'preview' => self::IMG_EAT_DRINK],
                ],
            ],
            [
                'title' => __('Plan my visit', 'culvers'),
                'url' => '#',
                'parent_slug' => 'plan-my-visit',
                'panel_image' => self::IMG_PLAN_VISIT,
                'children' => [
                    ['slug' => 'getting-here', 'title' => __('Getting Here', 'culvers'), 'preview' => self::IMG_PLAN_VISIT],
                    ['slug' => 'centre-map', 'title' => __('Centre Map', 'culvers'), 'preview' => self::IMG_PLAN_VISIT],
                    ['slug' => 'accessible-access', 'title' => __('Accessible Access', 'culvers'), 'preview' => self::IMG_PLAN_VISIT],
                    ['slug' => 'opening-hours', 'title' => __('Opening Hours', 'culvers'), 'preview' => self::IMG_PLAN_VISIT],
                ],
            ],
            [
                'title' => __("what's on", 'culvers'),
                'url' => '#',
                'parent_slug' => 'whats-on',
                'panel_image' => self::IMG_WHATS_ON,
                'children' => [
                    [
                        'slug' => 'latest-events',
                        'title' => __('Latest Events', 'culvers'),
                        'preview' => self::IMG_WHATS_ON,
                    ],
                    [
                        'slug' => 'latest-news',
                        'title' => __('Latest News', 'culvers'),
                        'preview' => self::IMG_WHATS_ON,
                    ],
                    [
                        'slug' => 'latest-offers',
                        'title' => __('Latest Offers', 'culvers'),
                        'preview' => self::IMG_WHATS_ON,
                    ],
                ],
            ],
            [
                'title' => __('Guest Services', 'culvers'),
                'url' => '#',
                'parent_slug' => 'guest-services',
                'panel_image' => self::IMG_GUEST_SERVICES,
                'children' => [
                    ['slug' => 'about-colchester', 'title' => __('About Colchester', 'culvers'), 'preview' => self::IMG_GUEST_SERVICES],
                    ['slug' => 'click-collect', 'title' => __('Click & Collect', 'culvers'), 'preview' => self::IMG_GUEST_SERVICES],
                    ['slug' => 'security', 'title' => __('Security', 'culvers'), 'preview' => self::IMG_GUEST_SERVICES],
                    ['slug' => 'facilities', 'title' => __('Facilities', 'culvers'), 'preview' => self::IMG_GUEST_SERVICES],
                    ['slug' => 'faqs', 'title' => __("FAQ's", 'culvers'), 'preview' => self::IMG_GUEST_SERVICES],
                    ['slug' => 'contact-us', 'title' => __('Contact Us', 'culvers'), 'preview' => self::IMG_GUEST_SERVICES],
                ],
            ],
        ];
    }
}
