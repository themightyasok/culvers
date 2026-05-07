<?php

declare(strict_types=1);

namespace App\Legal;

/**
 * Creates the legal policy pages once per site, assigns the policy template, and rewires
 * existing footer legal-row menu items so they point at the real policy pages
 * (`post_type=page` items so URL/slug changes propagate automatically).
 *
 * The fixed slugs (`privacy-policy`, `cookie-policy`, `terms-and-conditions`) double as the
 * routing key in {@see PolicyPages::layoutDataForPost()} so editors can rename the page
 * title without breaking the rendered layout.
 */
final class PolicyPageInstaller
{
    /**
     * Bumped to v2 to re-hydrate footer legal items that were left as `url=#` custom links by
     * the v1 strict-title match (e.g. "Cookies" never matched the "Cookie Policy" page).
     */
    private const OPTION_KEY = 'culvers_policy_pages_seed_v2';

    /**
     * Slug → display title (used both when seeding the page and when menu items have no match
     * for any of the {@see self::TITLE_ALIASES}).
     */
    private const PAGES = [
        'privacy-policy' => 'Privacy Policy',
        'cookie-policy' => 'Cookie Policy',
        'terms-and-conditions' => 'Terms & Conditions',
    ];

    /**
     * Lower-cased aliases that resolve a footer menu item title to a policy page slug.
     * Add new variants here whenever editors invent labels for the legal row.
     *
     * @var array<string, string>
     */
    private const TITLE_ALIASES = [
        'privacy policy' => 'privacy-policy',
        'privacy' => 'privacy-policy',
        'privacy notice' => 'privacy-policy',
        'cookie policy' => 'cookie-policy',
        'cookies' => 'cookie-policy',
        'cookie notice' => 'cookie-policy',
        'cookie statement' => 'cookie-policy',
        'terms & conditions' => 'terms-and-conditions',
        'terms and conditions' => 'terms-and-conditions',
        't&cs' => 'terms-and-conditions',
        'terms' => 'terms-and-conditions',
    ];

    public static function maybeSeed(): void
    {
        if (get_option(self::OPTION_KEY)) {
            return;
        }

        foreach (self::PAGES as $slug => $title) {
            self::ensurePolicyPage($slug, $title);
        }

        self::hydrateFooterLegalUrls();

        update_option(self::OPTION_KEY, '1', false);
    }

    /**
     * Idempotent: creates the page if missing, ensures it is published, and assigns the
     * policy template (so {@see PolicyPages} can resolve its layout).
     */
    private static function ensurePolicyPage(string $slug, string $title): int
    {
        $existing = get_posts([
            'name' => $slug,
            'post_type' => 'page',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'suppress_filters' => true,
            'fields' => 'ids',
        ]);

        if ($existing !== []) {
            $pageId = (int) $existing[0];
            $page = get_post($pageId);
            // Re-publish if a previous run left the page as a draft (legal pages must be
            // reachable for footer links to resolve to the policy template, not 404).
            if ($page instanceof \WP_Post && $page->post_status !== 'publish') {
                wp_update_post([
                    'ID' => $pageId,
                    'post_status' => 'publish',
                ]);
            }
            update_post_meta($pageId, '_wp_page_template', 'template-policy.php');

            return $pageId;
        }

        $created = wp_insert_post([
            'post_title' => __($title, 'culvers'),
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '',
        ], true);

        if (is_wp_error($created)) {
            return 0;
        }

        $pageId = (int) $created;
        update_post_meta($pageId, '_wp_page_template', 'template-policy.php');

        return $pageId;
    }

    /**
     * Walks the footer legal menu and converts any item whose label aliases a known policy
     * (see {@see self::TITLE_ALIASES}) into a native `post_type=page` link. Editor labels are
     * preserved (`menu-item-title` stays as authored). Unknown items (e.g. "Modern Slavery
     * Statement") are left untouched so the seeder never silently breaks bespoke entries.
     */
    private static function hydrateFooterLegalUrls(): void
    {
        $menuId = self::footerLegalMenuId();
        if ($menuId <= 0) {
            return;
        }

        $items = wp_get_nav_menu_items($menuId);
        if (! is_array($items)) {
            return;
        }

        $slugToPageId = self::policyPageIdsBySlug();

        foreach ($items as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }

            $labelKey = strtolower(trim(html_entity_decode((string) $item->post_title, ENT_QUOTES | ENT_HTML5)));
            $targetSlug = self::TITLE_ALIASES[$labelKey] ?? null;
            if ($targetSlug === null) {
                continue;
            }

            $pageId = $slugToPageId[$targetSlug] ?? 0;
            if ($pageId <= 0) {
                continue;
            }

            wp_update_nav_menu_item($menuId, (int) $item->ID, [
                'menu-item-title' => $item->post_title,
                'menu-item-status' => 'publish',
                'menu-item-type' => 'post_type',
                'menu-item-object' => 'page',
                'menu-item-object-id' => $pageId,
            ]);
        }
    }

    private static function footerLegalMenuId(): int
    {
        $locations = get_nav_menu_locations();
        if (isset($locations['footer_brand_subnav']) && (int) $locations['footer_brand_subnav'] > 0) {
            return (int) $locations['footer_brand_subnav'];
        }

        $map = get_option('culvers_figma_footer_menu_term_ids', []);
        if (is_array($map) && isset($map['footer_brand_subnav'])) {
            return (int) $map['footer_brand_subnav'];
        }

        return 0;
    }

    /**
     * @return array<string, int>
     */
    private static function policyPageIdsBySlug(): array
    {
        $map = [];
        foreach (array_keys(self::PAGES) as $slug) {
            $found = get_posts([
                'name' => $slug,
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'suppress_filters' => true,
                'fields' => 'ids',
            ]);
            if ($found !== []) {
                $map[$slug] = (int) $found[0];
            }
        }

        return $map;
    }
}
