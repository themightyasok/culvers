<?php

declare(strict_types=1);

namespace App\Nav;

/**
 * Wires the three footer menu locations to the live page set.
 *
 * - `footer_column_one` ("What's Here") — matches Figma labels (incl. AccessAble Guide) + canonical URLs.
 * - `footer_column_two` ("Useful Links") — rebuilds once per seed version to Careers / Leasing /
 *   Parking / Opening hours with real routes (incl. `/careers/`).
 * - `footer_brand_subnav` (legal row) — forces URLs for Cookie / Accessibility / Privacy / Terms.
 *
 * Idempotent: safe to call from CLI or boot. “What’s Here” + Useful Links each have their own
 * version option; the legal row URL pass is additionally gated by `OPTION_VER` on the boot path
 * (CLI {@see syncAllLocations()} always applies the legal map).
 */
final class FooterNavLinkSync
{
    public const OPTION_VER = 'culvers_footer_nav_link_sync_ver';

    public const CURRENT_VER = 2;

    /** Footer column one is replaced wholesale (Figma footprint) once per bump. */
    public const OPTION_COLUMN_ONE_SHAPE_VER = 'culvers_footer_column_one_shape_ver';

    public const COLUMN_ONE_SHAPE_CURRENT_VER = 1;

    /** Useful Links is structurally rebuilt to match the Figma seed. */
    public const OPTION_USEFUL_LINKS_VER = 'culvers_footer_useful_links_seed_ver';

    public const USEFUL_LINKS_CURRENT_VER = 2;

    public static function maybeSync(): void
    {
        $locations = get_nav_menu_locations();

        if (isset($locations['footer_column_one'])) {
            self::resetWhatsHereColumn((int) $locations['footer_column_one']);
        }

        if (isset($locations['footer_column_two'])) {
            self::resetUsefulLinks((int) $locations['footer_column_two']);
        }

        if ((int) get_option(self::OPTION_VER, 0) >= self::CURRENT_VER) {
            return;
        }

        if (isset($locations['footer_brand_subnav'])) {
            self::applyCanonicalUrlMap((int) $locations['footer_brand_subnav'], self::legalRowMap());
        }

        update_option(self::OPTION_VER, self::CURRENT_VER, true);
    }

    public static function syncAllLocations(): void
    {
        $locations = get_nav_menu_locations();

        if (isset($locations['footer_column_one'])) {
            self::resetWhatsHereColumn((int) $locations['footer_column_one']);
        }

        if (isset($locations['footer_column_two'])) {
            self::resetUsefulLinks((int) $locations['footer_column_two']);
        }

        if (isset($locations['footer_brand_subnav'])) {
            self::applyCanonicalUrlMap((int) $locations['footer_brand_subnav'], self::legalRowMap());
        }
    }

    /**
     * Canonical “What’s Here” shape from footer Figma (developer release footer frame).
     *
     * @return list<array{label: string, url: string}>
     */
    private static function whatsHereDefinition(): array
    {
        $home = static fn (string $path): string => function_exists('home_url') ? home_url($path) : $path;

        return [
            ['label' => __('Plan My Visit', 'culvers'), 'url' => $home('/plan-my-visit/')],
            ['label' => __('What’s On', 'culvers'), 'url' => $home('/whats-on/')],
            ['label' => __('Guest Services', 'culvers'), 'url' => $home('/guest-services/')],
            ['label' => __('AccessAble Guide', 'culvers'), 'url' => $home('/accessible-guide/')],
        ];
    }

    /**
     * Modern Slavery Statement currently lives at /modern-slavery-statement/
     * (stub page seeded by the populate script). Privacy / Terms / Cookie
     * already point to the right URL — listed here so a future menu reset
     * recovers cleanly.
     *
     * @return array<string, string>
     */
    private static function legalRowMap(): array
    {
        $home = static fn (string $path): string => function_exists('home_url') ? home_url($path) : $path;

        return self::canonicaliseMap([
            'Modern Slavery Statement' => $home('/modern-slavery-statement/'),
            'Privacy Policy' => $home('/privacy-policy/'),
            'Terms & Conditions' => $home('/terms-and-conditions/'),
            'Cookies' => $home('/cookie-policy/'),
            'Cookie Policy' => $home('/cookie-policy/'),
            'Accessibility' => $home('/accessible-guide/'),
        ]);
    }

    /**
     * Canonical Useful Links shape from the Figma developer release.
     *
     * @return list<array{label: string, url: string}>
     */
    private static function usefulLinksDefinition(): array
    {
        $home = static fn (string $path): string => function_exists('home_url') ? home_url($path) : $path;

        return [
            ['label' => __('Careers', 'culvers'), 'url' => $home('/careers/')],
            ['label' => __('Leasing Opportunities', 'culvers'), 'url' => $home('/leasing-opportunities/')],
            ['label' => __('Parking', 'culvers'), 'url' => $home('/plan-my-visit/')],
            ['label' => __('Opening hours', 'culvers'), 'url' => $home('/plan-my-visit/')],
        ];
    }

    /**
     * Replace existing items in a menu with a fresh canonical set.
     *
     * Used for `footer_column_two` because the live menu had drifted from the
     * Figma seed and the user explicitly opted to reset to the seed shape.
     */
    private static function resetUsefulLinks(int $menuId): void
    {
        if ($menuId <= 0) {
            return;
        }

        if ((int) get_option(self::OPTION_USEFUL_LINKS_VER, 0) >= self::USEFUL_LINKS_CURRENT_VER) {
            return;
        }

        $items = wp_get_nav_menu_items($menuId);
        if (is_array($items)) {
            foreach ($items as $item) {
                if ($item instanceof \WP_Post) {
                    wp_delete_post((int) $item->ID, true);
                }
            }
        }

        foreach (self::usefulLinksDefinition() as $row) {
            wp_update_nav_menu_item($menuId, 0, [
                'menu-item-title' => $row['label'],
                'menu-item-url' => esc_url_raw($row['url']),
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
            ]);
        }

        update_option(self::OPTION_USEFUL_LINKS_VER, self::USEFUL_LINKS_CURRENT_VER, true);
    }

    /**
     * Rebuild “What’s Here” to the canonical Figma list (destructive once per `{@see COLUMN_ONE_SHAPE_CURRENT_VER}` bump).
     */
    private static function resetWhatsHereColumn(int $menuId): void
    {
        if ($menuId <= 0) {
            return;
        }

        if ((int) get_option(self::OPTION_COLUMN_ONE_SHAPE_VER, 0) >= self::COLUMN_ONE_SHAPE_CURRENT_VER) {
            return;
        }

        $items = wp_get_nav_menu_items($menuId);
        if (is_array($items)) {
            foreach ($items as $item) {
                if ($item instanceof \WP_Post) {
                    wp_delete_post((int) $item->ID, true);
                }
            }
        }

        foreach (self::whatsHereDefinition() as $row) {
            wp_update_nav_menu_item($menuId, 0, [
                'menu-item-title' => $row['label'],
                'menu-item-url' => esc_url_raw($row['url']),
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
            ]);
        }

        update_option(self::OPTION_COLUMN_ONE_SHAPE_VER, self::COLUMN_ONE_SHAPE_CURRENT_VER, true);
    }

    /**
     * Apply canonical URLs for every footer legal-row item matching the Figma palette.
     *
     * @param array<string, string> $canonicalKeyToUrl from {@see legalRowMap()}
     */
    private static function applyCanonicalUrlMap(int $menuId, array $canonicalKeyToUrl): void
    {
        if ($menuId <= 0 || $canonicalKeyToUrl === []) {
            return;
        }

        $items = wp_get_nav_menu_items($menuId);
        if (! is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }

            $id = (int) $item->ID;
            $key = self::canonicaliseTitle((string) $item->post_title);
            if (! isset($canonicalKeyToUrl[$key])) {
                continue;
            }

            $targetUrl = $canonicalKeyToUrl[$key];
            $currentUrl = trim((string) get_post_meta($id, '_menu_item_url', true));

            if ($currentUrl !== $targetUrl) {
                wp_update_nav_menu_item($menuId, $id, [
                    'menu-item-db-id' => $id,
                    'menu-item-title' => $item->post_title,
                    'menu-item-url' => esc_url_raw($targetUrl),
                    'menu-item-status' => 'publish',
                    'menu-item-type' => 'custom',
                ]);
            }
        }
    }

    /**
     * @param array<string, string> $map title → URL
     * @return array<string, string> canonicalisedTitle → URL
     */
    private static function canonicaliseMap(array $map): array
    {
        $out = [];
        foreach ($map as $title => $url) {
            $out[self::canonicaliseTitle((string) $title)] = $url;
        }

        return $out;
    }

    private static function canonicaliseTitle(string $title): string
    {
        $decoded = function_exists('html_entity_decode')
            ? html_entity_decode($title, ENT_QUOTES, 'UTF-8')
            : $title;

        $normalised = strtr($decoded, [
            "\u{2019}" => "'",
            "\u{2018}" => "'",
            "\u{2013}" => '-',
            "\u{2014}" => '-',
        ]);

        return strtolower(trim((string) preg_replace('/\s+/', ' ', $normalised)));
    }
}
