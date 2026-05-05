<?php

declare(strict_types=1);

namespace App\Legal;

/**
 * Creates legal policy pages once per site and assigns the Policy template; optionally fixes footer legal URLs.
 */
final class PolicyPageInstaller
{
    private const OPTION_KEY = 'culvers_policy_pages_seed_v1';

    public static function register(): void
    {
        add_action('init', [self::class, 'maybeSeed'], 40);
    }

    public static function maybeSeed(): void
    {
        if (get_option(self::OPTION_KEY)) {
            return;
        }

        $defs = [
            'privacy-policy' => __('Privacy Policy', 'culvers'),
            'cookie-policy' => __('Cookie Policy', 'culvers'),
            'terms-and-conditions' => __('Terms & Conditions', 'culvers'),
        ];

        foreach ($defs as $slug => $title) {
            $existing = get_posts([
                'name' => $slug,
                'post_type' => 'page',
                'post_status' => 'any',
                'posts_per_page' => 1,
                'suppress_filters' => true,
                'fields' => 'ids',
            ]);

            if (is_array($existing) && $existing !== []) {
                update_post_meta((int) $existing[0], '_wp_page_template', 'template-policy.php');

                continue;
            }

            $pageId = wp_insert_post([
                'post_title' => $title,
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '',
            ], true);

            if (is_wp_error($pageId)) {
                continue;
            }

            update_post_meta((int) $pageId, '_wp_page_template', 'template-policy.php');
        }

        self::hydrateFooterLegalUrls();

        update_option(self::OPTION_KEY, '1', false);
    }

    private static function hydrateFooterLegalUrls(): void
    {
        /** @var array<string, mixed> $map */
        $map = get_option('culvers_figma_footer_menu_term_ids', []);
        if (! is_array($map) || ! isset($map['footer_brand_subnav'])) {
            return;
        }

        $menuId = (int) $map['footer_brand_subnav'];
        if ($menuId <= 0) {
            return;
        }

        $targetUrls = [
            __('Cookie Policy', 'culvers') => home_url('/cookie-policy/'),
            __('Privacy Policy', 'culvers') => home_url('/privacy-policy/'),
            __('Terms & Conditions', 'culvers') => home_url('/terms-and-conditions/'),
        ];

        $items = wp_get_nav_menu_items($menuId);
        if (! is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }

            $title = trim((string) $item->post_title);
            if ($title === '' || ! isset($targetUrls[$title])) {
                continue;
            }

            $wanted = $targetUrls[$title];
            $currentUrl = (string) get_post_meta((int) $item->ID, '_menu_item_url', true);
            if ($currentUrl === $wanted || $wanted === '') {
                continue;
            }

            wp_update_nav_menu_item($menuId, (int) $item->ID, [
                'menu-item-type' => 'custom',
                'menu-item-url' => $wanted,
                'menu-item-title' => $title,
                'menu-item-status' => 'publish',
            ]);
        }
    }
}
