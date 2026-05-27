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
     * Returns the seeded location → title + item list. Built as a method (not a `const`) so
     * each label/title is a literal `__()` call that `wp i18n make-pot` can extract statically.
     *
     * @return array<string, array{title: string, items: list<array{label: string, url: string}>}>
     */
    private static function locations(): array
    {
        return [
            'footer_column_one' => [
                'title' => __('Culver Square — What’s Here', 'culvers'),
                'items' => [
                    ['label' => __('Plan My Visit', 'culvers'), 'url' => '#'],
                    ['label' => __('What’s On', 'culvers'), 'url' => '#'],
                    ['label' => __('Guest Services', 'culvers'), 'url' => '#'],
                    ['label' => __('AccessAble Guide', 'culvers'), 'url' => '#'],
                ],
            ],
            'footer_column_two' => [
                'title' => __('Culver Square — Useful Links', 'culvers'),
                'items' => [
                    ['label' => __('Careers', 'culvers'), 'url' => '#'],
                    ['label' => __('Leasing Opportunities', 'culvers'), 'url' => '#'],
                    ['label' => __('Commercialisation Opportunities', 'culvers'), 'url' => '#'],
                    ['label' => __('Parking', 'culvers'), 'url' => '#'],
                    ['label' => __('Opening hours', 'culvers'), 'url' => '#'],
                ],
            ],
            'footer_brand_subnav' => [
                'title' => __('Culver Square — Footer legal row', 'culvers'),
                'items' => [
                    ['label' => __('Cookie Policy', 'culvers'), 'url' => '/cookie-policy/'],
                    ['label' => __('Accessibility', 'culvers'), 'url' => '#'],
                    ['label' => __('Privacy Policy', 'culvers'), 'url' => '/privacy-policy/'],
                    ['label' => __('Terms & Conditions', 'culvers'), 'url' => '/terms-and-conditions/'],
                ],
            ],
        ];
    }

    public static function maybeInstall(): void
    {
        if ((bool) get_theme_mod(self::OPTION_DISABLED, false)) {
            return;
        }

        if (! current_theme_supports('menus')) {
            return;
        }

        foreach (array_keys(self::locations()) as $location) {
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

        $defs = self::locations();
        if (! isset($defs[$location])) {
            return;
        }

        $menuId = self::resolveMenuId($location);
        if ($menuId <= 0) {
            return;
        }

        $items = wp_get_nav_menu_items($menuId);
        if (! is_array($items) || $items === []) {
            self::populateItems($menuId, $defs[$location]['items']);
        }

        /** @var array<string, int> $locations */
        $locations[$location] = $menuId;
        set_theme_mod('nav_menu_locations', $locations);

        self::persistMenuId($location, $menuId);
    }

    private static function persistMenuId(string $location, int $menuId): void
    {
        $map = get_option('culvers_figma_footer_menu_term_ids', []);
        if (! is_array($map)) {
            $map = [];
        }
        $map[$location] = $menuId;
        update_option('culvers_figma_footer_menu_term_ids', $map, false);
    }

    private static function resolveMenuId(string $location): int
    {
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

        $defs = self::locations();
        $title = $defs[$location]['title'] ?? __('Footer menu', 'culvers');
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
            // Labels are already pulled from `__()` calls in {@see self::locations()}; no second
            // translation pass here (calling `__($variable)` is invisible to gettext extraction).
            $label = (string) $row['label'];
            $urlInput = trim((string) $row['url']);
            if ($urlInput === '#' || $urlInput === '') {
                $url = '#';
            } elseif (str_starts_with($urlInput, '/')) {
                $url = home_url($urlInput);
            } else {
                $url = esc_url_raw($urlInput);
            }

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
