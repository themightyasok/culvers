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
            $panelImage = $branch['panel_image'];

            foreach ($branch['children'] as $childTitle) {
                $childResult = wp_update_nav_menu_item($menuId, 0, [
                    'menu-item-title' => $childTitle,
                    'menu-item-url' => '#',
                    'menu-item-status' => 'publish',
                    'menu-item-type' => 'custom',
                    'menu-item-parent-id' => $parentDbId,
                ]);

                if (is_wp_error($childResult)) {
                    continue;
                }

                $childItemId = (int) $childResult;
                $attachId = self::attachmentIdForSourceUrl($panelImage);
                if ($attachId > 0) {
                    update_post_meta($childItemId, NavMenuItemMeta::META_PREVIEW_ATTACHMENT, $attachId);
                } else {
                    update_post_meta($childItemId, NavMenuItemMeta::META_PREVIEW_URL, esc_url_raw($panelImage));
                }
            }
        }
    }

    /**
     * @return list<array{title: string, url: string, panel_image: string, children: list<string>}>
     */
    private static function branches(): array
    {
        return [
            [
                'title' => __('Shop', 'culvers'),
                'url' => '#',
                'panel_image' => self::IMG_SHOP,
                'children' => [
                    __('Beauty & Wellbeing', 'culvers'),
                    __('Fashion', 'culvers'),
                    __('Jewellery', 'culvers'),
                    __('Toys & Gifts', 'culvers'),
                    __('Technology', 'culvers'),
                    __('Services', 'culvers'),
                    __('Home', 'culvers'),
                ],
            ],
            [
                'title' => __('Eat & Drink', 'culvers'),
                'url' => '#',
                'panel_image' => self::IMG_EAT_DRINK,
                'children' => [
                    __('Grab & Go', 'culvers'),
                    __('Restaurant', 'culvers'),
                    __('Healthy Options', 'culvers'),
                    __('Cafe', 'culvers'),
                ],
            ],
            [
                'title' => __('Plan my visit', 'culvers'),
                'url' => '#',
                'panel_image' => self::IMG_PLAN_VISIT,
                'children' => [
                    __('Getting Here', 'culvers'),
                    __('Centre Map', 'culvers'),
                    __('Accessible Access', 'culvers'),
                    __('Opening Hours', 'culvers'),
                ],
            ],
            [
                'title' => __("what's on", 'culvers'),
                'url' => '#',
                'panel_image' => self::IMG_WHATS_ON,
                'children' => [
                    __('Latest Events', 'culvers'),
                    __('Latest News', 'culvers'),
                    __('Latest Offers', 'culvers'),
                ],
            ],
            [
                'title' => __('Guest Services', 'culvers'),
                'url' => '#',
                'panel_image' => self::IMG_GUEST_SERVICES,
                'children' => [
                    __('About Colchester', 'culvers'),
                    __('Click & Collect', 'culvers'),
                    __('Security', 'culvers'),
                    __('Facilities', 'culvers'),
                    __("FAQ's", 'culvers'),
                    __('Contact Us', 'culvers'),
                ],
            ],
        ];
    }
}
