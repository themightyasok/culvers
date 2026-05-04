<?php

declare(strict_types=1);

namespace App\Nav;

/**
 * Seeds three footer menus (two columns + brand strip) when their locations are empty.
 *
 * @see register_nav_menus() in {@see app/setup.php}
 */
final class CulverSquareFigmaFooterMenus
{
    public const OPTION_DISABLED = 'culvers_disable_figma_footer_menu_install';

    /**
     * @var array<string, array{title: string, items: list<array{label: string, url: string}>}>
     */
    private const LOCATIONS = [
        'footer_column_one' => [
            'title' => 'Culver Square — What’s Here',
            'items' => [
                ['label' => 'Plan My Visit', 'url' => '#'],
                ["label" => "What's On", 'url' => '#'],
                ['label' => 'Guest Services', 'url' => '#'],
                ['label' => 'Accessibility Guide', 'url' => '#'],
            ],
        ],
        'footer_column_two' => [
            'title' => 'Culver Square — Useful Links',
            'items' => [
                ['label' => 'Careers', 'url' => '#'],
                ['label' => 'Leasing Opportunities', 'url' => '#'],
                ['label' => 'Parking', 'url' => '#'],
                ['label' => 'Opening hours', 'url' => '#'],
            ],
        ],
        'footer_brand_subnav' => [
            'title' => 'Culver Square — Footer legal row',
            'items' => [
                ['label' => 'Cookie Policy', 'url' => '#'],
                ['label' => 'Accessibility', 'url' => '#'],
                ['label' => 'Privacy Policy', 'url' => '#'],
                ['label' => 'Terms & Conditions', 'url' => '#'],
            ],
        ],
    ];

    public static function maybeInstall(): void
    {
        if ((bool) get_theme_mod(self::OPTION_DISABLED, false)) {
            return;
        }

        if (! current_theme_supports('menus')) {
            return;
        }

        foreach (array_keys(self::LOCATIONS) as $location) {
            self::maybeInstallLocation((string) $location);
        }
    }

    private static function maybeInstallLocation(string $location): void
    {
        $locations = get_nav_menu_locations();
        $assigned = isset($locations[$location]) ? (int) $locations[$location] : 0;

        if ($assigned > 0) {
            $term = wp_get_nav_menu_object($assigned);
            if ($term instanceof \WP_Term) {
                $items = wp_get_nav_menu_items($assigned);
                if (is_array($items) && $items !== []) {
                    return;
                }
            }
        }

        if (! isset(self::LOCATIONS[$location])) {
            return;
        }

        $menuId = self::resolveMenuId($location);
        if ($menuId <= 0) {
            return;
        }

        $items = wp_get_nav_menu_items($menuId);
        if (! is_array($items) || $items === []) {
            self::populateItems($menuId, self::LOCATIONS[$location]['items']);
        }

        /** @var array<string, int> $locations */
        $locations[$location] = $menuId;
        set_theme_mod('nav_menu_locations', $locations);

        self::persistMenuId($location, $menuId);
    }

    private static function persistMenuId(string $location, int $menuId): void
    {
        /** @var array<string, int> $map */
        $map = get_option('culvers_figma_footer_menu_term_ids', []);
        if (! is_array($map)) {
            $map = [];
        }
        $map[$location] = $menuId;
        update_option('culvers_figma_footer_menu_term_ids', $map, false);
    }

    private static function resolveMenuId(string $location): int
    {
        /** @var array<string, mixed> $map */
        $map = get_option('culvers_figma_footer_menu_term_ids', []);
        if (is_array($map) && isset($map[$location])) {
            $savedId = (int) $map[$location];
            if ($savedId > 0) {
                $existing = wp_get_nav_menu_object($savedId);
                if ($existing instanceof \WP_Term) {
                    return $savedId;
                }
            }
        }

        $title = self::LOCATIONS[$location]['title'] ?? 'Footer menu';
        $created = wp_create_nav_menu($title);
        if (is_wp_error($created)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[culvers][figma-footer-menu] ' . $created->get_error_message());
            }

            return 0;
        }

        return (int) $created;
    }

    /**
     * @param list<array{label: string, url: string}> $links
     */
    private static function populateItems(int $menuId, array $links): void
    {
        foreach ($links as $row) {
            $label = __($row['label'], 'culvers');
            $url = esc_url_raw((string) $row['url']);
            $result = wp_update_nav_menu_item($menuId, 0, [
                'menu-item-title' => $label,
                'menu-item-url' => $url,
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
            ]);
            if (is_wp_error($result)) {
                continue;
            }
        }
    }
}
