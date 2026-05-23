<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Canonical homepage flexible rows — same structure/copy/assets as the former runtime defaults.
 * Used only when persisting to the database (WP-CLI); the theme does not merge this at render time.
 *
 * @see https://www.figma.com/design/KoBl6rTY98YnvusBgKLx4A/Culver-Square-Website-Design--Developer-Release-
 */
final class HomepageFlexibleSeedData
{
    private const DEMO_VIDEO_MP4 = 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4';

    private const FIGMA_HERO_MAIN = 'https://www.figma.com/api/mcp/asset/7b3fa9c0-91a0-4835-809f-e76b8b57221b';

    private const FIGMA_CARD_EAT_POSTER = 'https://www.figma.com/api/mcp/asset/d48c7ff9-31ad-407e-9686-7986599ca73f';

    private const FIGMA_VIDEO_POSTER = 'https://www.figma.com/api/mcp/asset/477b40bd-356a-45e4-a1ba-e7fc2049cbd7';

    private const FIGMA_HOURS_GRAPHIC_LEFT = 'https://www.figma.com/api/mcp/asset/0fdbac2d-b291-4f77-95db-c2389a0f98c5';

    private const FIGMA_HOURS_GRAPHIC_RIGHT = 'https://www.figma.com/api/mcp/asset/76420588-4f2c-4e56-a519-5532a0f303f4';

    private static function brandLogoUrl(string $relative): string
    {
        return rtrim(get_template_directory_uri(), '/') . '/resources/images/' . ltrim($relative, '/');
    }

    /**
     * Ordered layouts: hero → three video cards → scroller → video → info grid → three posts → hours.
     *
     * @return list<array<string, mixed>>
     */
    public static function fullStack(): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = [
            self::heroSliderRow(),
            self::threeColVideoRow(),
            self::horizontalScrollerRow(),
            self::videoBlockRow(),
            self::infoBlockRow(),
            self::threeColBlogRow(),
            self::openingHoursRow(),
        ];

        /** @var list<array<string, mixed>> */
        return apply_filters('culvers_homepage_flexible_defaults', $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private static function base(string $layout): array
    {
        return [
            'acf_fc_layout' => $layout,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function heroSliderRow(): array
    {
        return array_merge(self::base('hero_slider'), [
            'hero_content_align' => 'center',
            'hero_slides' => [
                [
                    'slide_image' => [
                        'url' => self::FIGMA_HERO_MAIN,
                        'alt' => __('Hero — Culver Square', 'culvers'),
                    ],
                    'slide_headline' => "Discover a new side\nof the Square.",
                    'slide_kicker' => __('The place to meet and shop', 'culvers'),
                    'slide_body' => '',
                    'slide_cta_label' => __('Explore', 'culvers'),
                    'slide_cta_url' => home_url('/'),
                ],
                [
                    'slide_image' => [
                        'url' => self::FIGMA_CARD_EAT_POSTER,
                        'alt' => __('Hero — Eat & Drink', 'culvers'),
                    ],
                    'slide_headline' => "Good brews,\nGreat bites.",
                    'slide_kicker' => __('From quick bites to long lunches', 'culvers'),
                    'slide_body' => '',
                    'slide_cta_label' => __('Eat & Drink', 'culvers'),
                    'slide_cta_url' => home_url('/eat-drink/'),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function threeColVideoRow(): array
    {
        return array_merge(self::base('three_card_block'), [
            'cards_source' => 'cpt',
            'cards_media_overlay_opacity' => 25,
            'cards_heading' => __('Fun for the whole family', 'culvers'),
            'cards_subheading' => '',
            'cards_body' => sprintf(
                '<p>%s</p>',
                esc_html__(
                    'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    'culvers'
                )
            ),
            'cards_cpt_post_type' => ['culvers_shop'],
            'cards_cpt_count' => 3,
            'cards_view_all_url' => home_url('/shops/'),
            'cards_view_all_label' => __('View all', 'culvers'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function horizontalScrollerRow(): array
    {
        $logos = [
            ['alt' => __('Schuh', 'culvers'), 'file' => 'homepage-brands/schuh.svg'],
            ['alt' => __('Accessorize London', 'culvers'), 'file' => 'homepage-brands/accessorize-london.svg'],
            ['alt' => __('H&M', 'culvers'), 'file' => 'homepage-brands/hm.svg'],
            ['alt' => __('Pandora', 'culvers'), 'file' => 'homepage-brands/pandora.svg'],
            ['alt' => __('TK Maxx', 'culvers'), 'file' => 'homepage-brands/tk-maxx.svg'],
        ];

        $scrollCards = [];
        foreach ($logos as $logo) {
            $scrollCards[] = [
                'item_type' => 'image',
                'item_size' => 'medium',
                'item_vertical_offset' => 'center',
                'item_aspect_ratio' => 'landscape',
                'item_image' => ['url' => self::brandLogoUrl($logo['file']), 'alt' => $logo['alt']],
                'item_image_alt' => $logo['alt'],
            ];
        }

        return array_merge(self::base('horizontal_scroller'), [
            'scroller_preset' => 'homepage_brands',
            'scroller_header_text' => sprintf('<p>%s</p>', esc_html__('Home to great brands', 'culvers')),
            'scroller_subheading_text' => '',
            'scroller_body_text' => sprintf(
                '<p>%s</p>',
                esc_html__(
                    'From iconic high-street labels to local independents, Culver Square brings the best of Colchester together under one roof.',
                    'culvers'
                )
            ),
            'scroller_button_text' => __('View all', 'culvers'),
            'scroller_button_link' => [
                'url' => home_url('/shops/'),
                'title' => '',
                'target' => '',
            ],
            'scroller_items' => $scrollCards,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function videoBlockRow(): array
    {
        return array_merge(self::base('video_block'), [
            'video_file' => ['url' => self::DEMO_VIDEO_MP4, 'mime_type' => 'video/mp4'],
            'video_poster' => ['url' => self::FIGMA_VIDEO_POSTER, 'alt' => __('Video preview', 'culvers')],
            'video_play_label' => __('Play video', 'culvers'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function infoBlockRow(): array
    {
        return array_merge(self::base('info_block'), [
            'info_heading' => __('A glimpse of what we have to offer', 'culvers'),
            'info_subheading' => '',
            'info_body' => sprintf(
                '<p>%s</p>',
                esc_html__(
                    'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    'culvers'
                )
            ),
            'info_cta_label' => __('Plan my visit', 'culvers'),
            'info_cta_url' => home_url('/visit/'),
            'info_items' => self::infoBlockItems(),
        ]);
    }

    /**
     * Eight stat tiles for the homepage info block. Item icons are intentionally
     * left as `null` here — the editor uploads the final Figma SVGs in WP Admin
     * (Pages → Home → Info Block). Returning `null` keeps the populate script
     * idempotent (no broken assets re-imported each run) without erasing any
     * icons an editor has already attached: `update_field()` writes new rows,
     * but the picker on each row still resolves whatever attachment ID is saved
     * in post meta if the editor has set one in the admin.
     *
     * @return list<array<string, mixed>>
     */
    private static function infoBlockItems(): array
    {
        return [
            ['item_heading' => __('5 places', 'culvers'),         'item_description' => __('to Eat & Drink', 'culvers'),       'item_image' => null],
            ['item_heading' => __('Fab Fashion', 'culvers'),      'item_description' => __('find your newest look', 'culvers'),'item_image' => null],
            ['item_heading' => __('11 Min Walk', 'culvers'),      'item_description' => __('from Colchester station', 'culvers'),'item_image' => null],
            ['item_heading' => __('Fun For All', 'culvers'),      'item_description' => __('pop-up family events', 'culvers'),  'item_image' => null],
            ['item_heading' => __('36 mins', 'culvers'),          'item_description' => __('train journey from ipswich', 'culvers'),'item_image' => null],
            ['item_heading' => __('Sweet Tooth?', 'culvers'),     'item_description' => __('We’ve got you covered', 'culvers'),'item_image' => null],
            ['item_heading' => __('Pet Friendly', 'culvers'),     'item_description' => __('well behaved ones!', 'culvers'),   'item_image' => null],
            ['item_heading' => __('But First, Coffee', 'culvers'),'item_description' => __('and a catch up', 'culvers'),       'item_image' => null],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function threeColBlogRow(): array
    {
        return array_merge(self::base('three_card_block'), [
            'cards_source' => 'cpt',
            'cards_heading' => __('What are you looking for today?', 'culvers'),
            'cards_subheading' => '',
            'cards_body' => '',
            'cards_cpt_post_type' => ['culvers_news', 'culvers_event', 'culvers_offer'],
            'cards_cpt_count' => 3,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function openingHoursRow(): array
    {
        return array_merge(self::base('opening_hours'), [
            'hours_heading' => __('Opening Hours', 'culvers'),
            'hours_subheading' => '',
            'hours_body' => '',
            'hours_graphic_left' => [
                'url' => self::FIGMA_HOURS_GRAPHIC_LEFT,
                'alt' => '',
            ],
            'hours_graphic_right' => [
                'url' => self::FIGMA_HOURS_GRAPHIC_RIGHT,
                'alt' => '',
            ],
            'hours_rows' => [
                ['day_label' => __('Monday', 'culvers'), 'time_range' => __('9am - 5.30pm', 'culvers'), 'weekday_highlight' => 'mon'],
                ['day_label' => __('Tuesday', 'culvers'), 'time_range' => __('9am - 5.30pm', 'culvers'), 'weekday_highlight' => 'tue'],
                ['day_label' => __('Wednesday', 'culvers'), 'time_range' => __('9am - 5.30pm', 'culvers'), 'weekday_highlight' => 'wed'],
                ['day_label' => __('Thursday', 'culvers'), 'time_range' => __('9am - 5.30pm', 'culvers'), 'weekday_highlight' => 'thu'],
                ['day_label' => __('Friday', 'culvers'), 'time_range' => __('9am - 5.30pm', 'culvers'), 'weekday_highlight' => 'fri'],
                ['day_label' => __('Saturday', 'culvers'), 'time_range' => __('9am - 6pm', 'culvers'), 'weekday_highlight' => 'sat'],
                ['day_label' => __('Sunday', 'culvers'), 'time_range' => __('10.30am - 4.30pm', 'culvers'), 'weekday_highlight' => 'sun'],
            ],
            'hours_footnote' => __('Holiday hours may vary*', 'culvers'),
        ]);
    }
}
