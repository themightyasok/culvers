<?php

declare(strict_types=1);

namespace App\Directory;

use App\Helpers\PagesFlexibleSeedData;

/**
 * Canonical flexible `components` payloads for `culvers_shop` singles — same pattern as
 * {@see HomepageFlexibleSeedData}: URLs wrapped as `['url' => …]` for {@see HomepageFlexibleAcfAttach}.
 *
 * Target layout follows the shop-detail frame in Figma Developer Release (~51:6679): hero imagery,
 * static split highlight (tabs off when split is present), store details,
 * opening hours with side illustrations, centre map, then four related shops.
 *
 * @see scripts/shop-single-populate-flexible.php
 */
final class ShopSingleFlexibleSeedData
{
    /**
     * Full designer stack with populated fields
     * (hero → intro → split → details → hours → centre map → related ×4).
     *
     * @return list<array<string, mixed>>
     */
    public static function fullStackForSlug(string $shopSlug, int $currentPostId = 0): array
    {
        $retailer = self::retailerRowForSlug($shopSlug);
        if ($retailer === null) {
            throw new \InvalidArgumentException(
                sprintf(
                    'No directory seed retailer matches slug "%s". Run shops-directory-populate or choose another slug.',
                    $shopSlug
                )
            );
        }

        $title = $retailer['title'];
        $logoUrlRaw = $retailer['logo_url'] !== null ? trim((string) $retailer['logo_url']) : '';
        /*
         * Figma MCP logos expire in Local; authored seeds under `resources/images/seeds/` sideload cleanly.
         * Accessorize ships a traced wordmark (from the same MCP export wired into uploads during directory populate).
         */
        $logoUrl = '';
        if ($shopSlug === 'accessorize-london') {
            $logoUrl = PagesFlexibleSeedData::seedAssetUrl('accessorize-logo.svg');
        } elseif ($logoUrlRaw !== '' && ! str_contains($logoUrlRaw, 'figma.com/api/mcp/')) {
            $logoUrl = $logoUrlRaw;
        }
        $featuredFromDirectory = $retailer['featured_url'] !== null ? trim((string) $retailer['featured_url']) : '';

        /*
         * Per-retailer storefront stills intentionally return empty from {@see ShopDirectorySeedData::storefrontDemoPhoto}
         * until MCP exports land in git. Singles still ship with full-width photography for hero + split (51:6679 parity),
         * so reuse the archived shopping‑directory hero backdrop ({@see ShopDirectorySeedData::heroDesktopImageUrl()})
         * whenever the directory payload has nothing stronger.
         */
        $promoBackdrop = $featuredFromDirectory !== ''
            ? $featuredFromDirectory
            : ShopDirectorySeedData::heroDesktopImageUrl();

        $heroWide = self::detailHeroUrl($shopSlug);
        $heroMobile = self::detailHeroMobileUrl($shopSlug);

        if ($heroWide === '') {
            $heroWide = $promoBackdrop;
        }
        if ($heroMobile === '') {
            $heroMobile = $promoBackdrop;
        }

        $relatedIds = self::relatedShopIdsExcluding(
            $shopSlug,
            ['pandora', 'tk-maxx', 'boots', 'smiggle'],
            $currentPostId
        );
        $archive = function_exists('get_post_type_archive_link')
            ? (string) get_post_type_archive_link('culvers_shop')
            : '/shops/';

        $brandUrl = self::demoBrandWebsiteUrl($shopSlug);

        $rows = [
            [
                'acf_fc_layout' => 'image_hero',
                'hero_image' => ['url' => $heroWide],
                'hero_image_mobile' => ['url' => $heroMobile],
                'hero_logo' => $logoUrl !== '' ? ['url' => $logoUrl] : null,
                'hero_title_line' => $logoUrl !== '' ? '' : mb_strtoupper($title),
                'hero_subtitle_line' => $logoUrl !== '' ? ''
                    : ($shopSlug === 'accessorize-london' ? 'LONDON' : 'Culver Square'),
                'hero_overlay_opacity' => 52,
            ],
            [
                'acf_fc_layout' => 'shop_intro_block',
                'intro_body' => self::introWysiwyg($title, $shopSlug),
                'intro_cta_label' => $brandUrl !== ''
                    ? sprintf(__('Visit %s online', 'culvers'), $title)
                    : '',
                'intro_cta_url' => $brandUrl,
            ],
        ];

        /*
         * Two-column highlight — static mode only (Greggs parity in {@see CptSinglesFlexibleSeedData::greggs}).
         * Image column prefers directory storefront when present; otherwise the same hero panorama as banner.
         */
        $rows[] = [
            'acf_fc_layout' => 'shop_split_highlight',
            'split_use_tabs' => false,
            'split_kicker' => __('New season', 'culvers'),
            'split_headline' => __('Layers you will wear on repeat', 'culvers'),
            'split_body' => '<p>'
                . sprintf(
                    esc_html__(
                        'Discover what\'s new at %s — styled for everyday Culver Square visits.',
                        'culvers'
                    ),
                    esc_html($title)
                )
                . '</p>',
            'split_cta_label' => __('Plan your visit', 'culvers'),
            'split_cta_url' => $archive,
            'split_image' => ['url' => $promoBackdrop],
        ];

        $rows = array_merge($rows, [
            [
                'acf_fc_layout' => 'shop_store_details',
                'details_heading' => __('Store Details', 'culvers'),
                'details_show_social_column' => 1,
                'details_contact_phone' => '01452 302646',
                'details_address' => '10B Culver St W, Colchester CO1 1WF',
                'details_instagram_url' => $shopSlug === 'accessorize-london' ? 'https://www.instagram.com/accessorize/' : '',
                'details_instagram_handle' => $shopSlug === 'accessorize-london' ? '@accessorize' : '',
            ],
            /*
             * Line art + hours pattern is shared with top-level seeded pages (@see PagesFlexibleSeedData::openingHoursRow).
             * Coerce typography for the white band the same way as other shop-intro surfaces.
             */
            array_merge(PagesFlexibleSeedData::openingHoursRow(), [
                'hours_heading' => __('Opening hours', 'culvers'),
                'hours_subheading' => __('Typical centre hours — confirm before travelling.', 'culvers'),
                'hours_body' => '',
                'hours_footnote' => __('Hours may change on bank holidays.', 'culvers'),
            ]),
            PagesFlexibleSeedData::centreMapRow(),
            [
                'acf_fc_layout' => 'shop_related_shops',
                'related_heading' => __('More shops you might enjoy', 'culvers'),
                'related_shop_posts' => $relatedIds,
                'related_view_all_url' => $archive !== '' ? $archive : '/shops/',
                'related_view_all_label' => __('View all', 'culvers'),
            ],
        ]);

        return $rows;
    }

    /**
     * @return array{
     *     title: string,
     *     logo_url: string|null,
     *     featured_url: string|null,
     *     category_slug: string,
     *     type_slug: string
     * }|null
     */
    private static function retailerRowForSlug(string $slug): ?array
    {
        foreach (ShopDirectorySeedData::retailers() as $row) {
            $title = $row['title'];
            if ($title === '') {
                continue;
            }
            if (sanitize_title($title) === $slug) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Per-shop hero photo URL reserved for bespoke exports (currently unused — hero resolves via {@see ShopDirectorySeedData::heroDesktopImageUrl()}).
     */
    private static function detailHeroUrl(string $slug): string
    {
        unset($slug);

        return '';
    }

    private static function detailHeroMobileUrl(string $slug): string
    {
        unset($slug);

        return '';
    }

    private static function introWysiwyg(string $shopTitle, string $shopSlug): string
    {
        if ($shopSlug === 'accessorize-london') {
            $lead = sprintf(
                esc_html__(
                    '%s brings playful accessories and thoughtful gifting to Culver Square — perfect for finishing an outfit or picking up a treat.',
                    'culvers'
                ),
                esc_html($shopTitle)
            );

            return '<p>' . $lead . '</p>';
        }

        $lead = sprintf(
            esc_html__(
                '%s is part of the Culver Square line-up — plan your visit to explore stores, cafés and more in Colchester.',
                'culvers'
            ),
            esc_html($shopTitle)
        );

        return '<p>' . $lead . '</p>';
    }

    private static function demoBrandWebsiteUrl(string $shopSlug): string
    {
        return match ($shopSlug) {
            'accessorize-london' => 'https://www.accessorize.com/',
            default => '',
        };
    }

    /**
     * @param  list<string>  $preferredSlugs  Resolved in order; skips missing or the current shop.
     * @return list<int>
     */
    private static function relatedShopIdsExcluding(string $currentSlug, array $preferredSlugs, int $currentPostId): array
    {
        $ids = [];
        foreach ($preferredSlugs as $slug) {
            if ($slug === $currentSlug) {
                continue;
            }
            $found = get_posts([
                'post_type' => 'culvers_shop',
                'name' => $slug,
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'suppress_filters' => true,
                'no_found_rows' => true,
            ]);
            if (isset($found[0])) {
                $ids[] = (int) $found[0];
            }
            if (count($ids) >= 4) {
                break;
            }
        }

        if (count($ids) >= 4 || $currentPostId <= 0) {
            return $ids;
        }

        $more = get_posts([
            'post_type' => 'culvers_shop',
            'post_status' => 'publish',
            'posts_per_page' => 4,
            'post__not_in' => array_values(array_unique(array_merge([$currentPostId], $ids))),
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'suppress_filters' => true,
            'no_found_rows' => true,
        ]);

        foreach ($more as $mid) {
            $mid = (int) $mid;
            if ($mid > 0 && ! in_array($mid, $ids, true)) {
                $ids[] = $mid;
            }
            if (count($ids) >= 4) {
                break;
            }
        }

        return $ids;
    }
}
