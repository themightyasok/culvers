<?php

declare(strict_types=1);

namespace App\Directory;

use App\Constants\ComponentTypes;

/**
 * Canonical flexible `components` payloads for `culvers_shop` singles — same pattern as
 * {@see HomepageFlexibleSeedData}: URLs wrapped as `['url' => …]` for {@see HomepageFlexibleAcfAttach}.
 *
 * @see scripts/shop-single-populate-flexible.php
 */
final class ShopSingleFlexibleSeedData
{
    /**
     * Full designer stack with populated fields (hero → intro → split → details → hours → related).
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

        $title = (string) ($retailer['title'] ?? '');
        $logoUrl = isset($retailer['logo_url']) && is_string($retailer['logo_url']) ? trim($retailer['logo_url']) : '';
        $featuredUrl = isset($retailer['featured_url']) && is_string($retailer['featured_url'])
            ? trim($retailer['featured_url'])
            : '';

        $heroWide = self::detailHeroUrl($shopSlug);
        $heroMobile = self::detailHeroMobileUrl($shopSlug);

        $relatedIds = self::relatedShopIdsExcluding(
            $shopSlug,
            ['pandora', 'tk-maxx', 'boots', 'smiggle'],
            $currentPostId
        );
        $archive = function_exists('get_post_type_archive_link')
            ? (string) get_post_type_archive_link('culvers_shop')
            : '/shops/';

        $brandUrl = self::demoBrandWebsiteUrl($shopSlug);

        return [
            [
                'acf_fc_layout' => 'shop_image_hero',
                'hero_image' => ['url' => $heroWide],
                'hero_image_mobile' => ['url' => $heroMobile],
                'hero_logo' => $logoUrl !== '' ? ['url' => $logoUrl] : null,
                'hero_title_line' => $logoUrl !== '' ? '' : mb_strtoupper($title),
                'hero_subtitle_line' => $logoUrl !== '' ? '' : 'Culver Square',
                'hero_overlay_opacity' => 52,
            ],
            [
                'acf_fc_layout' => 'shop_intro_block',
                'intro_body' => self::introWysiwyg($title),
                'intro_cta_label' => $brandUrl !== ''
                    ? sprintf(__('Visit %s online', 'culvers'), $title)
                    : '',
                'intro_cta_url' => $brandUrl,
            ],
            [
                'acf_fc_layout' => 'shop_split_highlight',
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
                'split_cta_url' => $archive !== '' ? $archive : '/shops/',
                'split_image' => $featuredUrl !== '' ? ['url' => $featuredUrl] : ['url' => $heroWide],
            ],
            [
                'acf_fc_layout' => 'shop_store_details',
                'details_heading' => __('Store Details', 'culvers'),
                'details_heading_level' => '2',
                'contact_label' => __('Contact Number', 'culvers'),
                'contact_phone' => '01452 302646',
                'address_label' => __('Address', 'culvers'),
                'address_text' => $title . "\n" . __('Culver Square', 'culvers') . "\n" . __('Gloucester GL1 2LG', 'culvers'),
                'social_label' => __('Social Media', 'culvers'),
                'social_instagram_url' => $shopSlug === 'accessorize-london' ? 'https://www.instagram.com/accessorize/' : '',
                'social_instagram_handle' => $shopSlug === 'accessorize-london' ? '@accessorize' : '',
            ],
            [
                'acf_fc_layout' => 'opening_hours',
                'heading' => __('Opening hours', 'culvers'),
                'heading_semantic_level' => '2',
                'subheading' => __('Typical centre hours — confirm before travelling.', 'culvers'),
                'body' => '',
                'hours_rows' => self::defaultHoursRepeater(),
                'hours_footnote' => __('Hours may change on bank holidays.', 'culvers'),
                'background_type' => ComponentTypes::BACKGROUND_COLOR,
                'background_color' => '#fdfcf3',
                'component_width' => 'full',
            ],
            [
                'acf_fc_layout' => 'shop_related_shops',
                'related_heading' => __('More shops you might enjoy', 'culvers'),
                'related_heading_level' => '2',
                'related_shop_posts' => $relatedIds,
                'related_view_all_url' => $archive !== '' ? $archive : '/shops/',
                'related_view_all_label' => __('View all', 'culvers'),
            ],
        ];
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
            $title = isset($row['title']) ? (string) $row['title'] : '';
            if ($title === '') {
                continue;
            }
            if (sanitize_title($title) === $slug) {
                return $row;
            }
        }

        return null;
    }

    private static function detailHeroUrl(string $slug): string
    {
        return 'https://picsum.photos/seed/' . rawurlencode('cs-shop-hero-' . $slug) . '/1440/646';
    }

    private static function detailHeroMobileUrl(string $slug): string
    {
        return 'https://picsum.photos/seed/' . rawurlencode('cs-shop-hero-m-' . $slug) . '/800/960';
    }

    /**
     * @return list<array{day_label: string, time_range: string, weekday_highlight: string}>
     */
    private static function defaultHoursRepeater(): array
    {
        return [
            ['day_label' => __('Monday', 'culvers'), 'time_range' => '9:00am – 5:30pm', 'weekday_highlight' => 'mon'],
            ['day_label' => __('Tuesday', 'culvers'), 'time_range' => '9:00am – 5:30pm', 'weekday_highlight' => 'tue'],
            ['day_label' => __('Wednesday', 'culvers'), 'time_range' => '9:00am – 5:30pm', 'weekday_highlight' => 'wed'],
            ['day_label' => __('Thursday', 'culvers'), 'time_range' => '9:00am – 5:30pm', 'weekday_highlight' => 'thu'],
            ['day_label' => __('Friday', 'culvers'), 'time_range' => '9:00am – 5:30pm', 'weekday_highlight' => 'fri'],
            ['day_label' => __('Saturday', 'culvers'), 'time_range' => '9:00am – 6:00pm', 'weekday_highlight' => 'sat'],
            ['day_label' => __('Sunday', 'culvers'), 'time_range' => '10:30am – 4:30pm', 'weekday_highlight' => 'sun'],
        ];
    }

    private static function introWysiwyg(string $shopTitle): string
    {
        $lead = sprintf(
            esc_html__(
                '%s brings playful accessories and thoughtful gifting to Culver Square — perfect for finishing an outfit or picking up a treat.',
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
            if (is_array($found) && isset($found[0])) {
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

        if (! is_array($more)) {
            return $ids;
        }

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
