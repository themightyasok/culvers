<?php

declare(strict_types=1);

namespace App\Nav;

/**
 * Wires the three footer menu locations to the live page set.
 *
 * - `footer_column_one` ("What's Here") — rewrites placeholder URLs in place.
 * - `footer_column_two` ("Useful Links") — rebuilds to the canonical Figma
 *   seed shape (Careers / Leasing Opportunities / Parking / Opening hours).
 * - `footer_brand_subnav` (legal row) — rewrites placeholder URLs in place.
 *
 * Idempotent: safe to call from CLI or boot. Bumps a per-location version
 * option so the boot path only does work when something has changed.
 */
final class FooterNavLinkSync
{
    public const OPTION_VER = 'culvers_footer_nav_link_sync_ver';

    public const CURRENT_VER = 1;

    /** Useful Links is structurally rebuilt to match the Figma seed. */
    public const OPTION_USEFUL_LINKS_VER = 'culvers_footer_useful_links_seed_ver';

    public const USEFUL_LINKS_CURRENT_VER = 1;

    public static function maybeSync(): void
    {
        if ((int) get_option(self::OPTION_VER, 0) >= self::CURRENT_VER) {
            return;
        }

        self::syncAllLocations();

        update_option(self::OPTION_VER, self::CURRENT_VER, true);
    }

    public static function syncAllLocations(): void
    {
        $locations = get_nav_menu_locations();

        if (isset($locations['footer_column_one'])) {
            self::patchPlaceholderUrls((int) $locations['footer_column_one'], self::columnOneMap());
        }

        if (isset($locations['footer_column_two'])) {
            self::resetUsefulLinks((int) $locations['footer_column_two']);
        }

        if (isset($locations['footer_brand_subnav'])) {
            self::patchPlaceholderUrls((int) $locations['footer_brand_subnav'], self::legalRowMap());
        }
    }

    /**
     * @return array<string, string> map of canonicalised title → URL
     */
    private static function columnOneMap(): array
    {
        $home = static fn (string $path): string => function_exists('home_url') ? home_url($path) : $path;

        return self::canonicaliseMap([
            'Centre Map' => $home('/plan-my-visit/'),
            'Getting Here' => $home('/plan-my-visit/'),
            'Opening Hours' => $home('/plan-my-visit/'),
            'Accessible Access' => $home('/accessible-guide/'),
            // The original Figma seed for column one is the same shape as the
            // mega Plan-My-Visit branch; keep these as fallbacks if an editor
            // restores the seeded labels later.
            'Plan My Visit' => $home('/plan-my-visit/'),
            "What's On" => $home('/whats-on/'),
            'Guest Services' => $home('/guest-services/'),
            'Accessibility Guide' => $home('/accessible-guide/'),
        ]);
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
            ['label' => __('Opening Hours', 'culvers'), 'url' => $home('/plan-my-visit/')],
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
     * Sweep a menu and rewrite items whose URL is a placeholder (`#`/empty)
     * when the title matches an entry in `$titleToUrl`.
     *
     * @param array<string, string> $titleToUrl already-canonicalised map
     */
    private static function patchPlaceholderUrls(int $menuId, array $titleToUrl): void
    {
        if ($menuId <= 0) {
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
            // See note in PrimaryNavLinkSync: `wp_get_nav_menu_items()` adds
            // a runtime `->url` property that isn't on the WP_Post stubs.
            $current = trim((string) get_post_meta((int) $item->ID, '_menu_item_url', true));
            if ($current !== '' && $current !== '#') {
                continue;
            }

            $key = self::canonicaliseTitle((string) $item->post_title);
            if (! isset($titleToUrl[$key])) {
                continue;
            }

            wp_update_nav_menu_item($menuId, (int) $item->ID, [
                'menu-item-db-id' => (int) $item->ID,
                'menu-item-title' => $item->post_title,
                'menu-item-url' => esc_url_raw($titleToUrl[$key]),
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
            ]);
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
