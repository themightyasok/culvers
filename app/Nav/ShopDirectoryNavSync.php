<?php

declare(strict_types=1);

namespace App\Nav;

/**
 * Points the Figma primary mega menu “Shop” branch at {@see get_post_type_archive_link('culvers_shop')}
 * and each category row at {@see add_query_arg()} ?category=… deep links.
 *
 * Also pulls stray top-level items whose titles match Shop directory categories back under Shop
 * (duplicates under Shop are removed). Top-level order is normalized so **Shop** is first.
 */
final class ShopDirectoryNavSync
{
    public const OPTION_VER = 'culvers_shop_mega_nav_urls_ver';

    public const CURRENT_VER = 4;

    public static function maybeSync(): void
    {
        if ((int) get_option(self::OPTION_VER, 0) >= self::CURRENT_VER) {
            return;
        }

        if (! self::syncPrimaryShopMegaLinks()) {
            return;
        }
    }

    /**
     * Patch assigned primary navigation menu in place (safe to call from CLI seeders).
     */
    public static function syncPrimaryShopMegaLinks(): bool
    {
        $locations = get_nav_menu_locations();
        $menuId = isset($locations['primary_navigation']) ? (int) $locations['primary_navigation'] : 0;

        if ($menuId <= 0) {
            return false;
        }

        $items = wp_get_nav_menu_items($menuId);
        if (! is_array($items) || $items === []) {
            return false;
        }

        $archive = CulverSquareFigmaPrimaryMenu::shopArchiveUrl();
        $childRows = CulverSquareFigmaPrimaryMenu::shopMegaCategoryLinkRows();

        $shopParentDbId = 0;

        foreach ($items as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }
            $parentId = (int) get_post_meta((int) $item->ID, '_menu_item_menu_item_parent', true);
            if ($parentId !== 0) {
                continue;
            }
            if (! CulverSquareFigmaPrimaryMenu::menuTitlesMatch((string) $item->post_title, __('Shop', 'culvers'))) {
                continue;
            }

            $shopParentDbId = (int) $item->ID;

            wp_update_nav_menu_item($menuId, (int) $item->ID, [
                'menu-item-db-id' => (int) $item->ID,
                'menu-item-title' => $item->post_title,
                'menu-item-url' => esc_url_raw($archive),
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
            ]);
            break;
        }

        if ($shopParentDbId <= 0) {
            return false;
        }

        self::reparentOrphanShopCategoryItems($menuId, $shopParentDbId, $items, $childRows);

        $items = wp_get_nav_menu_items($menuId);
        if (! is_array($items)) {
            $items = [];
        }

        foreach ($items as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }
            $parentId = (int) get_post_meta((int) $item->ID, '_menu_item_menu_item_parent', true);
            if ($parentId !== $shopParentDbId) {
                continue;
            }

            $url = self::resolveChildUrl((string) $item->post_title, $childRows);
            if ($url === null) {
                continue;
            }

            wp_update_nav_menu_item($menuId, (int) $item->ID, [
                'menu-item-db-id' => (int) $item->ID,
                'menu-item-title' => $item->post_title,
                'menu-item-url' => $url,
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
            ]);
        }

        self::reorderTopLevelShopFirst($menuId);

        update_option(self::OPTION_VER, self::CURRENT_VER, true);

        return true;
    }

    private static function reorderTopLevelShopFirst(int $menuId): void
    {
        $items = wp_get_nav_menu_items($menuId);
        if (! is_array($items) || $items === []) {
            return;
        }

        $topLevel = [];
        foreach ($items as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }
            $id = (int) $item->ID;
            if (get_post_status($id) === false) {
                continue;
            }
            if ((int) get_post_meta($id, '_menu_item_menu_item_parent', true) !== 0) {
                continue;
            }
            $topLevel[] = $item;
        }

        if ($topLevel === []) {
            return;
        }

        usort($topLevel, static function (\WP_Post $a, \WP_Post $b): int {
            $aShop = CulverSquareFigmaPrimaryMenu::menuTitlesMatch((string) $a->post_title, __('Shop', 'culvers'));
            $bShop = CulverSquareFigmaPrimaryMenu::menuTitlesMatch((string) $b->post_title, __('Shop', 'culvers'));
            if ($aShop !== $bShop) {
                return $aShop ? -1 : 1;
            }

            return ((int) $a->menu_order) <=> ((int) $b->menu_order);
        });

        $order = 1;
        foreach ($topLevel as $item) {
            if ((int) $item->menu_order !== $order) {
                wp_update_post([
                    'ID' => (int) $item->ID,
                    'menu_order' => $order,
                ]);
            }
            ++$order;
        }
    }

    /**
     * @param list<\WP_Post>|array<int, \WP_Post|\WP_Post_Type|false> $items
     * @param list<array{title: string, url: string}>                 $childRows
     */
    private static function reparentOrphanShopCategoryItems(int $menuId, int $shopParentDbId, array $items, array $childRows): void
    {
        $megaParents = CulverSquareFigmaPrimaryMenu::primaryMegaParentTitles();

        foreach ($items as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }
            $itemId = (int) $item->ID;
            if (get_post_status($itemId) === false) {
                continue;
            }
            $parentId = (int) get_post_meta($itemId, '_menu_item_menu_item_parent', true);
            if ($parentId !== 0) {
                continue;
            }

            $title = (string) $item->post_title;
            foreach ($megaParents as $megaTitle) {
                if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($title, $megaTitle)) {
                    continue 2;
                }
            }

            $url = self::resolveChildUrl($title, $childRows);
            if ($url === null) {
                continue;
            }

            $existingUnderShop = self::findShopChildMenuItemIdMatchingTitle($items, $shopParentDbId, $title);
            if ($existingUnderShop !== null && $existingUnderShop !== $itemId) {
                wp_delete_post($itemId, true);

                continue;
            }

            wp_update_nav_menu_item($menuId, $itemId, [
                'menu-item-db-id' => $itemId,
                'menu-item-title' => $item->post_title,
                'menu-item-url' => $url,
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
                'menu-item-parent-id' => $shopParentDbId,
            ]);
        }
    }

    /**
     * @param iterable<int, \WP_Post|false> $items
     */
    private static function findShopChildMenuItemIdMatchingTitle(iterable $items, int $shopParentDbId, string $title): ?int
    {
        foreach ($items as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }
            $cid = (int) $item->ID;
            if (get_post_status($cid) === false) {
                continue;
            }
            $parentId = (int) get_post_meta($cid, '_menu_item_menu_item_parent', true);
            if ($parentId !== $shopParentDbId) {
                continue;
            }
            if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch((string) $item->post_title, $title)) {
                return (int) $item->ID;
            }
        }

        return null;
    }

    /**
     * @param list<array{title: string, url: string}> $childRows
     */
    private static function resolveChildUrl(string $menuTitle, array $childRows): ?string
    {
        foreach ($childRows as $row) {
            if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($menuTitle, $row['title'])) {
                return $row['url'];
            }
        }

        return null;
    }
}
