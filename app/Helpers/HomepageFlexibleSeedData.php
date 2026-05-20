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

    private const FIGMA_SCROLL_LOGO_1 = 'https://www.figma.com/api/mcp/asset/2e9aa2d9-812b-454e-a10a-5c4c9e96bdee';

    private const FIGMA_SCROLL_LOGO_2 = 'https://www.figma.com/api/mcp/asset/02822726-b0f9-456d-9042-44dd97100251';

    private const FIGMA_SCROLL_LOGO_3 = 'https://www.figma.com/api/mcp/asset/83fa5c88-3a16-4abe-8574-17eea9d8fa05';

    private const FIGMA_SCROLL_LOGO_4 = 'https://www.figma.com/api/mcp/asset/7b8adf7b-c320-42fe-806d-7753616c4f38';

    private const FIGMA_SCROLL_LOGO_5 = 'https://www.figma.com/api/mcp/asset/3db7345a-0a33-4931-a12a-b330530c9cf6';

    private const FIGMA_SCROLL_LOGO_6 = 'https://www.figma.com/api/mcp/asset/8ba40165-250b-4f43-8f8a-d4a4cdbc4fb6';

    private const FIGMA_POST_EASTER = 'https://www.figma.com/api/mcp/asset/8732e173-c4a0-40a4-9132-a0e855137225';

    private const FIGMA_POST_SANTA = 'https://www.figma.com/api/mcp/asset/b2c711f4-f628-4baf-8d52-fe5c860c3f0f';

    private const FIGMA_POST_MOTHERS = 'https://www.figma.com/api/mcp/asset/ead4bdb3-8fc5-4c5d-9a54-4e97e72dfb13';

    private const FIGMA_HOURS_GRAPHIC_LEFT = 'https://www.figma.com/api/mcp/asset/0fdbac2d-b291-4f77-95db-c2389a0f98c5';

    private const FIGMA_HOURS_GRAPHIC_RIGHT = 'https://www.figma.com/api/mcp/asset/76420588-4f2c-4e56-a519-5532a0f303f4';

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
        $demoUrl = self::DEMO_VIDEO_MP4;

        return array_merge(self::base('three_card_block'), [
            'cards_source' => 'manual',
            'cards_heading' => __('Fun for the whole family', 'culvers'),
            'cards_subheading' => '',
            'cards_body' => sprintf(
                '<p>%s</p>',
                esc_html__(
                    'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    'culvers'
                )
            ),
            'cards_items' => [
                [
                    'card_title' => __('Shop', 'culvers'),
                    'card_url' => home_url('/shopping/'),
                    'card_media_type' => 'video',
                    'card_video' => ['url' => $demoUrl, 'mime_type' => 'video/mp4'],
                    'card_image' => null,
                    'card_image_alt' => '',
                ],
                [
                    'card_title' => __('Eat & Drink', 'culvers'),
                    'card_url' => home_url('/dining/'),
                    'card_media_type' => 'video',
                    'card_video' => ['url' => $demoUrl, 'mime_type' => 'video/mp4'],
                    'card_image' => null,
                    'card_image_alt' => '',
                ],
                [
                    'card_title' => __('Plan My Visit', 'culvers'),
                    'card_url' => home_url('/visit/'),
                    'card_media_type' => 'video',
                    'card_video' => ['url' => $demoUrl, 'mime_type' => 'video/mp4'],
                    'card_image' => null,
                    'card_image_alt' => '',
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function horizontalScrollerRow(): array
    {
        $logos = [
            ['alt' => __('Schuh', 'culvers'), 'url' => self::FIGMA_SCROLL_LOGO_1],
            ['alt' => __('Accessorize London', 'culvers'), 'url' => self::FIGMA_SCROLL_LOGO_2],
            ['alt' => __('H&M', 'culvers'), 'url' => self::FIGMA_SCROLL_LOGO_3],
            ['alt' => __('Pandora', 'culvers'), 'url' => self::FIGMA_SCROLL_LOGO_4],
            ['alt' => __('Brand', 'culvers'), 'url' => self::FIGMA_SCROLL_LOGO_5],
            ['alt' => __('TK Maxx', 'culvers'), 'url' => self::FIGMA_SCROLL_LOGO_6],
        ];

        $scrollCards = [];
        foreach ($logos as $logo) {
            $scrollCards[] = [
                'item_type' => 'image',
                'item_size' => 'medium',
                'item_vertical_offset' => 'center',
                'item_aspect_ratio' => 'landscape',
                'item_image' => ['url' => $logo['url'], 'alt' => $logo['alt']],
                'item_image_alt' => $logo['alt'],
            ];
        }

        return array_merge(self::base('horizontal_scroller'), [
            'scroller_preset' => 'homepage_brands',
            'scroller_header_text' => sprintf('<p>%s</p>', esc_html__('Home to great brands', 'culvers')),
            'scroller_subheading_text' => '',
            'scroller_body_text' => sprintf(
                '<p>%s</p><p>%s</p>',
                esc_html__(
                    'From iconic high-street labels to local independents,',
                    'culvers'
                ),
                esc_html__(
                    'Culver Square brings the best of Colchester together under one roof.',
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
        $blogArchive = get_post_type_archive_link('post');
        $fallbackUrl = is_string($blogArchive) && $blogArchive !== ''
            ? $blogArchive
            : home_url('/');

        return array_merge(self::base('three_card_block'), [
            'cards_source' => 'manual',
            'cards_heading' => __('What are you looking for today?', 'culvers'),
            'cards_subheading' => '',
            'cards_body' => '',
            'cards_items' => [
                [
                    'card_title' => __('Culver Square Easter Egg hunt', 'culvers'),
                    'card_url' => $fallbackUrl,
                    'card_media_type' => 'image',
                    'card_image' => ['url' => self::FIGMA_POST_EASTER, 'alt' => __('Culver Square Easter Egg hunt', 'culvers')],
                    'card_image_alt' => __('Culver Square Easter Egg hunt', 'culvers'),
                    'card_video' => null,
                ],
                [
                    'card_title' => __('Santa’s Grotto at Culver Square', 'culvers'),
                    'card_url' => $fallbackUrl,
                    'card_media_type' => 'image',
                    'card_image' => ['url' => self::FIGMA_POST_SANTA, 'alt' => __('Santa’s Grotto at Culver Square', 'culvers')],
                    'card_image_alt' => __('Santa’s Grotto at Culver Square', 'culvers'),
                    'card_video' => null,
                ],
                [
                    'card_title' => __('Mothers Day at Culver Square', 'culvers'),
                    'card_url' => $fallbackUrl,
                    'card_media_type' => 'image',
                    'card_image' => ['url' => self::FIGMA_POST_MOTHERS, 'alt' => __('Mothers Day at Culver Square', 'culvers')],
                    'card_image_alt' => __('Mothers Day at Culver Square', 'culvers'),
                    'card_video' => null,
                ],
            ],
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
