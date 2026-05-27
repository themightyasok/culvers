<?php

declare(strict_types=1);

namespace App\Nav;

/**
 * Resolves placeholder (`#`) URLs in the assigned primary mega menu to the
 * matching live page / archive / deep-link.
 *
 * Companion to {@see ShopDirectoryNavSync} (which only knows about the Shop
 * branch). This class wires the rest of the mega tree — Eat & Drink, Plan my
 * visit, what's on, Guest Services, Careers — once their target pages exist.
 *
 * Safe to re-run from CLI. Items whose URL has been hand-edited away from `#`
 * are left alone so manual customisations are preserved. Bumps a version
 * option so the front-controller bootstrap only needs to call
 * {@see self::maybeSync()} once per release.
 */
final class PrimaryNavLinkSync
{
    public const OPTION_VER = 'culvers_primary_nav_link_sync_ver';

    /**
     * Bumped when the resolver table changes so existing menus pick up the
     * new URLs without anyone touching Appearance → Menus by hand.
     * v2: What's On children moved to /whats-on/latest-{events,offers,news}/.
     * v3: flattened to top-level /latest-{events,offers,news}/ (What's On
     *     is a landing page, not an archive parent).
     * v4: append missing Careers mega branch (parent + Open roles) targeting
     *     the `culvers_career` archive and wire URLs.
     * v5: Careers removed from primary bar (footer only); Figma order enforced in {@see PrimaryNav}.
     * v6: Plan my visit + Guest Services children deep-link to in-page section anchors.
     * v7: Header utility links (Centre Map / Getting Here) deep-link to section anchors.
     * v8: Accessible Access deep-links to Plan my visit #accessible-guide section.
     */
    public const CURRENT_VER = 8;

    public static function maybeSync(): void
    {
        if ((int) get_option(self::OPTION_VER, 0) >= self::CURRENT_VER) {
            return;
        }

        self::syncAssignedPrimaryMenu();
        self::syncHeaderShortcutUrls();
        update_option(self::OPTION_VER, self::CURRENT_VER, true);
    }

    /**
     * Theme mods for the header utility pills — separate from the mega menu tree.
     */
    public static function syncHeaderShortcutUrls(): void
    {
        $home = static fn (string $path): string => function_exists('home_url') ? home_url($path) : $path;

        $shortcuts = [
            'culvers_centre_map_url' => $home('/plan-my-visit/#centre-map'),
            'culvers_getting_here_url' => $home('/plan-my-visit/#getting-here'),
        ];

        $stale = [
            '',
            '#',
            $home('/plan-my-visit/'),
        ];

        foreach ($shortcuts as $modKey => $canonical) {
            $current = (string) get_theme_mod($modKey, '#');
            if (in_array($current, $stale, true)) {
                set_theme_mod($modKey, $canonical);
            }
        }
    }

    /**
     * Patch the primary navigation menu in place.
     *
     * Returns true if a menu was found and processed; false if no primary
     * navigation location is assigned (typical during a fresh install).
     */
    public static function syncAssignedPrimaryMenu(): bool
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

        $byParent = self::indexParentMatches($items);
        $resolver = self::resolverMap();
        $stale = self::staleUrlsToOverwrite();

        foreach ($items as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }
            $itemId = (int) $item->ID;
            // `wp_get_nav_menu_items()` enriches WP_Post with `->url` at runtime,
            // but the PHPStan stubs don't know about it. Read the canonical
            // value from post meta — the same field WP writes back to.
            $currentUrl = (string) get_post_meta($itemId, '_menu_item_url', true);

            if (! self::isPlaceholderUrl($currentUrl) && ! in_array($currentUrl, $stale, true)) {
                continue;
            }

            $parentDbId = (int) get_post_meta($itemId, '_menu_item_menu_item_parent', true);
            $parentTitle = $parentDbId > 0 && isset($byParent[$parentDbId])
                ? $byParent[$parentDbId]
                : '';
            $title = (string) $item->post_title;

            $url = $resolver($title, $parentTitle);
            if ($url === null) {
                continue;
            }

            wp_update_nav_menu_item($menuId, $itemId, [
                'menu-item-db-id' => $itemId,
                'menu-item-title' => $item->post_title,
                'menu-item-url' => esc_url_raw($url),
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
                'menu-item-parent-id' => $parentDbId,
            ]);
        }

        return true;
    }

    /**
     * Build a `{db_id => post_title}` lookup so children can resolve their parent's title.
     *
     * @param iterable<int, mixed> $items
     * @return array<int, string>
     */
    private static function indexParentMatches(iterable $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }
            $out[(int) $item->ID] = (string) $item->post_title;
        }

        return $out;
    }

    private static function isPlaceholderUrl(string $url): bool
    {
        // Treat only literal `#` and empty as placeholders. Anything else has
        // been hand-authored (or already synced) and should be preserved.
        $trimmed = trim($url);

        return $trimmed === '' || $trimmed === '#';
    }

    /**
     * URLs the sync is allowed to overwrite even when they're not literal
     * placeholders — added when a CURRENT_VER bump moves canonical
     * destinations:
     *   v1 → wired all What's On children to /whats-on/.
     *   v2 → deep-linked them to /whats-on/latest-{events,offers,news}/.
     *   v3 → flattened to top-level /latest-{events,offers,news}/.
     * Both prior shapes appear here so a v1- or v2-era menu still gets
     * lifted to the current canonical URLs on the next sync pass.
     *
     * @return list<string>
     */
    private static function staleUrlsToOverwrite(): array
    {
        $home = static fn (string $path): string => function_exists('home_url') ? home_url($path) : $path;

        return [
            $home('/whats-on/'),
            $home('/whats-on/latest-events/'),
            $home('/whats-on/latest-offers/'),
            $home('/whats-on/latest-news/'),
            $home('/plan-my-visit/'),
            $home('/guest-services/'),
            $home('/accessible-guide/'),
        ];
    }

    /**
     * Returns a closure(`$title`, `$parentTitle`) → string|null that maps a menu
     * item to its canonical URL. Centralised so the URL list reads as a single
     * specification table rather than scattered if/else branches.
     *
     * @return callable(string, string): (string|null)
     */
    private static function resolverMap(): callable
    {
        $home = static fn (string $path): string => function_exists('home_url') ? home_url($path) : $path;

        // Top-level mega triggers — title → archive/page URL.
        $topLevel = [
            'Shop' => $home('/shops/'),
            'Eat & Drink' => $home('/eat-drink/'),
            'Plan my visit' => $home('/plan-my-visit/'),
            "what's on" => $home('/whats-on/'),
            'Guest Services' => $home('/guest-services/'),
            'Careers' => CulverSquareFigmaPrimaryMenu::careerArchiveUrl(),
        ];

        // Children keyed by [parent title][child title] → URL.
        // Categories that map to the existing client-side filter rely on the
        // `?category=` / `?type=` query params read by directory-archive.js.
        $children = [
            'Shop' => [
                'Beauty & Wellbeing' => $home('/shops/?category=beauty-wellbeing'),
                'Toys & Gifts' => $home('/shops/?category=toys-gifts'),
                // Existing rows already point at the right URL but are listed
                // here for completeness so a future menu reset still wires up.
                'Fashion' => $home('/shops/?category=fashion'),
                'Jewellery' => $home('/shops/?category=jewellery'),
                'Technology' => $home('/shops/?category=technology'),
                'Services' => $home('/shops/?category=services'),
                'Home' => $home('/shops/?category=home'),
            ],
            'Eat & Drink' => [
                'Grab & Go' => $home('/eat-drink/?type=grab-go'),
                'Restaurants' => $home('/eat-drink/?type=restaurants'),
                'Healthy Options' => $home('/eat-drink/?type=healthy-options'),
                'Cafés' => $home('/eat-drink/?type=cafes'),
            ],
            'Plan my visit' => [
                'Getting Here' => $home('/plan-my-visit/#getting-here'),
                'Centre Map' => $home('/plan-my-visit/#centre-map'),
                'Opening Hours' => $home('/plan-my-visit/#opening-hours'),
                'Accessible Access' => $home('/plan-my-visit/#accessible-guide'),
            ],
            "what's on" => [
                'Latest Events' => $home('/latest-events/'),
                'Latest News' => $home('/latest-news/'),
                'Latest Offers' => $home('/latest-offers/'),
            ],
            'Guest Services' => [
                'About Colchester' => $home('/guest-services/#about-colchester'),
                'Click & Collect' => $home('/guest-services/#click-collect'),
                'Security' => $home('/guest-services/#security'),
                'Facilities' => $home('/guest-services/#facilities'),
                "FAQ's" => $home('/guest-services/#faqs'),
                'Contact Us' => $home('/contact/'),
            ],
            'Careers' => [
                'Open roles' => CulverSquareFigmaPrimaryMenu::careerArchiveUrl(),
            ],
        ];

        return static function (string $title, string $parentTitle) use ($topLevel, $children): ?string {
            $titleKey = self::canonicalTitle($title);
            $parentKey = self::canonicalTitle($parentTitle);

            if ($parentKey === '') {
                foreach ($topLevel as $candidate => $url) {
                    if (self::canonicalTitle($candidate) === $titleKey) {
                        return $url;
                    }
                }

                return null;
            }

            foreach ($children as $parentLookup => $rows) {
                if (self::canonicalTitle($parentLookup) !== $parentKey) {
                    continue;
                }
                foreach ($rows as $childLookup => $url) {
                    if (self::canonicalTitle($childLookup) === $titleKey) {
                        return $url;
                    }
                }
            }

            return null;
        };
    }

    /**
     * Loose comparator — strips entities, smart-quote variants, casing, and stray
     * whitespace so menu titles match regardless of how they were authored.
     */
    private static function canonicalTitle(string $title): string
    {
        $decoded = function_exists('html_entity_decode')
            ? html_entity_decode($title, ENT_QUOTES, 'UTF-8')
            : $title;

        $normalised = strtr($decoded, [
            "\u{2019}" => "'",  // RIGHT SINGLE QUOTATION MARK
            "\u{2018}" => "'",  // LEFT SINGLE QUOTATION MARK
            "\u{2013}" => '-',  // EN DASH
            "\u{2014}" => '-',  // EM DASH
        ]);

        return strtolower(trim((string) preg_replace('/\s+/', ' ', $normalised)));
    }
}
