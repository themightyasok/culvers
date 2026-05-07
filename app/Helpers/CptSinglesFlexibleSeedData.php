<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Constants\ComponentTypes;

/**
 * Canonical flexible-content rows for one representative single per non-shop
 * directory CPT — the same purpose Accessorize London serves for `culvers_shop`.
 *
 * Pages built here:
 *   - culvers_career   → "Senior Supervisor" (Figma 51:6450)
 *   - culvers_event    → "Valentine's at Hotel Chocolat" offer (Figma 51:6386)
 *   - culvers_eat_drink → "Greggs" venue page (Figma 51:6679)
 *
 * Image references are wrapped as `['url' => …]` so {@see HomepageFlexibleAcfAttach}
 * can sideload them on first run, mirroring the homepage / shop seed pattern.
 */
final class CptSinglesFlexibleSeedData
{
    /**
     * Figma developer-handover assets are exported once via the MCP plugin and
     * committed to `resources/images/seeds/` (their MCP URLs are short-lived).
     * Resolution to a live theme URL is delegated to
     * {@see PagesFlexibleSeedData::seedAssetUrl()} so we keep one source of
     * truth for where the seed library lives.
     */

    /** Senior Supervisor (Figma 51:6450) — Subway team photo, group-hug split image. */
    private const CAREER_HERO_FILE = 'career-hero.jpg';

    private const CAREER_PERKS_FILE = 'career-perks.jpg';

    /** Hotel Chocolat Valentine's offer (Figma 51:6386) — heart-box hero + offer image. */
    private const EVENT_HERO_FILE = 'event-hero.jpg';

    private const EVENT_OFFER_IMAGE_FILE = 'event-one-gift.jpg';

    /** Greggs (Figma 51:6679) — storefront hero, festive bake split image. */
    private const GREGGS_HERO_FILE = 'greggs-hero-frame.jpg';

    private const GREGGS_FESTIVE_BAKE_FILE = 'greggs-festive-bake.jpg';

    /**
     * @return array<string, mixed>
     */
    private static function base(string $layout): array
    {
        return [
            'acf_fc_layout' => $layout,
            'component_width' => 12,
            'background_type' => ComponentTypes::BACKGROUND_NONE,
            'body_text_tone' => TailwindColors::DEFAULT_BODY_TEXT_TONE,
            'visibility_mobile' => 'visible',
        ];
    }

    /* -----------------------------------------------------------------
     * Senior Supervisor career single (Figma 51:6450)
     * Stack: image_hero (Subway team) → career_detail → shop_split_highlight
     *        (Why work for us, no tabs, 50/50) → info_block (Apply CTA band)
     * --------------------------------------------------------------- */

    /**
     * @return list<array<string, mixed>>
     */
    public static function seniorSupervisor(): array
    {
        $applyUrl = 'https://www.subway.com/en-gb/careers';

        return [
            array_merge(self::base('image_hero'), [
                'hero_image' => [
                    'url' => PagesFlexibleSeedData::seedAssetUrl(self::CAREER_HERO_FILE),
                    'alt' => __('Subway team at the Culver Square store', 'culvers'),
                ],
                'hero_image_mobile' => null,
                'hero_logo' => null,
                'hero_title_line' => __('Subway', 'culvers'),
                'hero_title_tone' => 'white',
                'hero_subtitle_line' => __('Now hiring at Culver Square', 'culvers'),
                'hero_overlay_opacity' => 40,
                'hero_title_in_image' => false,
            ]),
            array_merge(self::base('career_detail'), [
                        'career_job_title' => __('Senior Supervisor', 'culvers'),
                        // Image hero above already supplies the page H1 (the brand name);
                        // demote the job title to H2 so we don't ship two H1s.
                        'career_job_title_level' => '2',
                'career_meta' => [
                    ['item_label' => __('Contract Type', 'culvers'), 'item_value' => __('Full-Time', 'culvers')],
                    ['item_label' => __('Location', 'culvers'), 'item_value' => __('Culver Square Shopping Centre', 'culvers')],
                    ['item_label' => __('Pay', 'culvers'), 'item_value' => __('£12.40 per hour', 'culvers')],
                        ],
                        'career_apply_label' => __('Apply Now', 'culvers'),
                        'career_apply_url' => $applyUrl,
                        'career_section_heading_level' => '2',
                        'career_sections' => [
                        [
                        'item_heading' => __('About the role', 'culvers'),
                        'item_body' => '<p>'
                            . esc_html__(
                                'We are looking for a reliable and enthusiastic Assistant Store Manager '
                                . 'to support the Store Manager in leading our retail team and driving '
                                . 'daily operations. This role is a blend of leadership, customer service, '
                                . 'and operational management. The ideal candidate is a hands-on leader '
                                . 'who can step in wherever needed to ensure the store runs smoothly and '
                                . 'meets performance goals.',
                                'culvers'
                            )
                            . '</p>',
                        ],
                        [
                        'item_heading' => __('Work Schedule', 'culvers'),
                        'item_body' => '<ul>'
                            . '<li>' . esc_html__('39 hours weekly', 'culvers') . '</li>'
                            . '<li>' . esc_html__('Must have full/open availability, including weekends and holidays', 'culvers') . '</li>'
                            . '</ul>',
                        ],
                        [
                        'item_heading' => __('Key Responsibilities', 'culvers'),
                        'item_body' => '<ul>'
                            . '<li>' . esc_html__(
                                'Assist the Store Manager in overseeing daily store operations, '
                                . 'including opening/closing procedures, merchandising, and cash handling',
                                'culvers'
                            ) . '</li>'
                            . '<li>' . esc_html__('Support the Store Manager in achieving sales targets, KPIs, and profitability goals', 'culvers') . '</li>'
                            . '<li>' . esc_html__('Supervise, coach, and motivate staff to deliver exceptional customer service', 'culvers') . '</li>'
                            . '<li>' . esc_html__('Help manage employee scheduling, training, and performance evaluations', 'culvers') . '</li>'
                            . '<li>' . esc_html__('Ensure compliance with company policies, health and safety standards, and loss-prevention practices', 'culvers') . '</li>'
                            . '<li>' . esc_html__('Handle customer inquiries and complaints professionally to ensure a positive shopping experience', 'culvers') . '</li>'
                            . '<li>' . esc_html__('Monitor inventory levels, assist with stock replenishment, and maintain visual merchandising standards', 'culvers') . '</li>'
                            . '<li>' . esc_html__('Step in as acting Store Manager when required', 'culvers') . '</li>'
                            . '</ul>',
                        ],
                        [
                        'item_heading' => __('Qualifications', 'culvers'),
                        'item_body' => '<ul>'
                            . '<li>' . esc_html__('Previous experience in retail leadership, supervisory, or keyholder roles preferred', 'culvers') . '</li>'
                            . '<li>' . esc_html__('Strong interpersonal and communication skills with the ability to lead and inspire a team', 'culvers') . '</li>'
                            . '<li>' . esc_html__('Solid organisational and problem-solving abilities', 'culvers') . '</li>'
                            . '<li>' . esc_html__('Comfortable working in a fast-paced retail environment', 'culvers') . '</li>'
                            . '<li>' . esc_html__('Basic knowledge of POS systems, inventory management, and Microsoft Office / Google Workspace tools', 'culvers') . '</li>'
                            . '<li>' . esc_html__('Flexible availability, including evenings, weekends, and holidays', 'culvers') . '</li>'
                            . '</ul>',
                        ],
                        ],
            ]),
            array_merge(self::base('shop_split_highlight'), [
                'split_ratio' => '50-50',
                'split_use_tabs' => false,
                'split_image' => [
                    'url' => PagesFlexibleSeedData::seedAssetUrl(self::CAREER_PERKS_FILE),
                    'alt' => __('Subway team in a happy group hug', 'culvers'),
                ],
                'split_kicker' => '',
                'split_headline' => __('Why work for us?', 'culvers'),
                'split_body' => '<ul>'
                    . '<li>' . esc_html__('Competitive pay', 'culvers') . '</li>'
                    . '<li>' . esc_html__('Exclusive discount card for Culver Square employees', 'culvers') . '</li>'
                    . '<li>' . esc_html__('Employee discounts (gym membership)', 'culvers') . '</li>'
                    . '<li>' . esc_html__('Opportunities for growth and career development', 'culvers') . '</li>'
                    . '<li>' . esc_html__('A supportive and team-oriented work environment', 'culvers') . '</li>'
                    . '</ul>',
                'split_cta_label' => '',
                'split_cta_url' => '',
            ]),
            array_merge(self::base('info_block'), [
                'info_heading' => __('Think you’re a good fit?', 'culvers'),
                'info_heading_level' => '2',
                'info_subheading' => '',
                'info_body' => sprintf(
                    '<p>%s</p>',
                    esc_html__('Apply via the Subway website using the button below.', 'culvers')
                ),
                'info_cta_label' => __('Apply Now', 'culvers'),
                'info_cta_url' => $applyUrl,
                'info_items' => [],
            ]),
        ];
    }

    /* -----------------------------------------------------------------
     * Hotel Chocolat Valentine's offer event single (Figma 51:6386)
     * Stack: image_hero (heart chocolate box) → event_meta (offer dates) →
     *        section_header (A treat for every taste) →
     *        shop_split_highlight (One gift, a thousand words, no tabs) →
     *        section_header (Share with a friend) →
     *        three_card_block (More Offers)
     * --------------------------------------------------------------- */

    /**
     * @return list<array<string, mixed>>
     */
    public static function hotelChocolatOffer(): array
    {
        $whatsOnUrl = function_exists('home_url') ? home_url('/whats-on/') : '/whats-on/';

        return [
            array_merge(self::base('image_hero'), [
                'hero_image' => [
                    'url' => PagesFlexibleSeedData::seedAssetUrl(self::EVENT_HERO_FILE),
                    'alt' => __('Heart-shaped Hotel Chocolat selection box', 'culvers'),
                ],
                'hero_image_mobile' => null,
                'hero_logo' => null,
                'hero_title_line' => __('Hotel Chocolat', 'culvers'),
                'hero_title_tone' => 'white',
                'hero_subtitle_line' => __('Valentine’s at Culver Square', 'culvers'),
                'hero_overlay_opacity' => 25,
                // Photo is product-only (no baked-in title) — render title via component.
                'hero_title_in_image' => false,
            ]),
            array_merge(self::base('event_meta'), [
                'event_meta_date_label' => __('Offer valid', 'culvers'),
                'event_meta_date_value' => __('1 May – 30 June 2026', 'culvers'),
                'event_meta_time_label' => __('Open today', 'culvers'),
                'event_meta_time_value' => __('9:00 – 17:30', 'culvers'),
                'event_meta_location_label' => __('Where', 'culvers'),
                'event_meta_location_value' => __('Hotel Chocolat — lower level, Culver Square', 'culvers'),
                'event_meta_accessibility_note' => __(
                    'Step-free access throughout the store. Staff are happy to bring product samples '
                    . 'to you if browsing the displays is difficult.',
                    'culvers'
                ),
                'event_meta_cta_label' => __('Shop Hotel Chocolat', 'culvers'),
                'event_meta_cta_url' => 'https://www.hotelchocolat.com',
            ]),
            array_merge(self::base('section_header'), [
                'header_eyebrow' => '',
                'header_heading' => __('A treat for every taste', 'culvers'),
                'header_heading_level' => '2',
                'header_body' => __(
                    'Indulge your loved one’s sweet tooth with chocolate Valentine’s favourites from '
                    . 'Hotel Chocolat. From heart-shaped boxes to luxurious truffle collections, '
                    . 'there’s a treat for every taste.',
                    'culvers'
                ),
                'header_align' => 'center',
                'header_max_width' => 'medium',
            ]),
            array_merge(self::base('shop_split_highlight'), [
                'split_ratio' => '50-50',
                'split_use_tabs' => false,
                'split_image' => [
                    'url' => PagesFlexibleSeedData::seedAssetUrl(self::EVENT_OFFER_IMAGE_FILE),
                    'alt' => __('Open heart-shaped chocolate box with rose petals', 'culvers'),
                ],
                'split_kicker' => '',
                'split_headline' => __('One gift, a thousand words', 'culvers'),
                'split_body' => '<p>'
                    . esc_html__(
                        'Show them what they mean to you with an imaginatively crafted gift. Special '
                        . 'offers available in store.',
                        'culvers'
                    )
                    . '</p>'
                    . '<ul>'
                    . '<li>' . esc_html__('Gift packages available', 'culvers') . '</li>'
                    . '<li>' . esc_html__('Valentine’s Selection box', 'culvers') . '</li>'
                    . '<li>' . esc_html__('Chocolate hearts', 'culvers') . '</li>'
                    . '<li>' . esc_html__('Buy one get one free on selected chocolates', 'culvers') . '</li>'
                    . '</ul>'
                    . '<p><strong>'
                    . esc_html__('Offer valid between: 01.05.26 – 30.06.26', 'culvers')
                    . '</strong></p>',
                'split_cta_label' => '',
                'split_cta_url' => '',
            ]),
            array_merge(self::base('section_header'), [
                'header_eyebrow' => '',
                'header_heading' => __('Share with a friend', 'culvers'),
                'header_heading_level' => '2',
                'header_body' => __(
                    'Spread the love — share this offer on Instagram, Facebook or WhatsApp '
                    . 'so a sweet-toothed friend doesn’t miss out.',
                    'culvers'
                ),
                'header_align' => 'center',
                'header_max_width' => 'medium',
            ]),
            array_merge(self::base('three_card_block'), [
                'cards_source' => 'manual',
                'cards_heading' => __('More Offers', 'culvers'),
                'cards_subheading' => '',
                'cards_heading_level' => '2',
                'cards_body' => sprintf(
                    '<p>%s</p>',
                    esc_html__('See all of the brilliant offers happening at Culver Square.', 'culvers')
                ),
                'cards_items' => [
                    [
                        'card_title' => __('Valentine’s at Hotel Chocolat', 'culvers'),
                        'card_url' => $whatsOnUrl,
                        'card_media_type' => 'image',
                        'card_image' => [
                            'url' => PagesFlexibleSeedData::seedAssetUrl(self::EVENT_HERO_FILE),
                            'alt' => __('Hotel Chocolat heart selection box', 'culvers'),
                        ],
                        'card_image_alt' => __('Hotel Chocolat heart selection box', 'culvers'),
                        'card_video' => null,
                        'card_video_poster' => null,
                    ],
                    [
                        'card_title' => __('Mother’s Day at Culver Square', 'culvers'),
                        'card_url' => $whatsOnUrl,
                        'card_media_type' => 'image',
                        'card_image' => null,
                        'card_image_alt' => '',
                        'card_video' => null,
                        'card_video_poster' => null,
                    ],
                    [
                        'card_title' => __('Raise a glass to dry January', 'culvers'),
                        'card_url' => $whatsOnUrl,
                        'card_media_type' => 'image',
                        'card_image' => null,
                        'card_image_alt' => '',
                        'card_video' => null,
                        'card_video_poster' => null,
                    ],
                ],
            ]),
        ];
    }

    /* -----------------------------------------------------------------
     * Greggs venue eat & drink single (Figma 51:6679)
     * Stack: image_hero (Greggs storefront) → shop_intro_block →
     *        shop_split_highlight (Festive Bake, no tabs, no CTA) →
     *        shop_store_details → opening_hours →
     *        section_header (More flavours to discover)
     * --------------------------------------------------------------- */

    /**
     * @return list<array<string, mixed>>
     */
    public static function greggs(): array
    {
        return [
            array_merge(self::base('image_hero'), [
                'hero_image' => [
                    'url' => PagesFlexibleSeedData::seedAssetUrl(self::GREGGS_HERO_FILE),
                    'alt' => __('Greggs storefront at Culver Square', 'culvers'),
                ],
                'hero_image_mobile' => null,
                'hero_logo' => null,
                'hero_title_line' => __('Greggs', 'culvers'),
                'hero_title_tone' => 'white',
                'hero_subtitle_line' => __('Always fresh. Always tasty.', 'culvers'),
                'hero_overlay_opacity' => 25,
                // Photo is the Greggs storefront only (brand sign is part of the
                // shopfront, not a baked-in title overlay) — render a normal
                // visible h1 + subtitle on top.
                'hero_title_in_image' => false,
            ]),
            array_merge(self::base('shop_intro_block'), [
                'intro_body' => '<p>'
                    . esc_html__(
                        'Greggs, the home of fresh baking, is the leading bakery retailer in the UK. '
                        . 'Expert bakes the Greggs’ way for nearly 75 years, we serve delicious, freshly '
                        . 'baked, quality food at great value prices to a million customers each day in '
                        . 'over 1,500 shops around the UK.',
                        'culvers'
                    )
                    . '</p>'
                    . '<p>'
                    . esc_html__(
                        'Our customers’ time is too precious to Greggs to waste on inferior service or '
                        . 'food. So we’re constantly reviewing our product range to ensure the bakery '
                        . 'quality, taste and outstanding value for money is at the heart of what we offer.',
                        'culvers'
                    )
                    . '</p>',
                'intro_cta_label' => __('Visit Greggs online', 'culvers'),
                'intro_cta_url' => 'https://www.greggs.co.uk',
            ]),
            array_merge(self::base('shop_split_highlight'), [
                'split_ratio' => '50-50',
                'split_use_tabs' => false,
                'split_image' => [
                    'url' => PagesFlexibleSeedData::seedAssetUrl(self::GREGGS_FESTIVE_BAKE_FILE),
                    'alt' => __('Greggs Festive Bake on a wooden board with seasonal trim', 'culvers'),
                ],
                'split_kicker' => '',
                'split_headline' => __('The Festive Bake Is Back!', 'culvers'),
                'split_body' => '<p>'
                    . esc_html__(
                        'Our highly anticipated limited edition pastry is back for the season — featuring '
                        . 'our pillowy soft chicken sweet-cured bacon, crispy stuffing, and creamy '
                        . 'sage-and-onion mayonnaise inside golden, crisp puff pastry. Pop in for a '
                        . 'comforting bite while it lasts.',
                        'culvers'
                    )
                    . '</p>'
                    . '<p><strong>'
                    . esc_html__('Festive Bake — only £2.00 from 1 November to 31 December.', 'culvers')
                    . '</strong></p>',
                'split_cta_label' => '',
                'split_cta_url' => '',
            ]),
            array_merge(self::base('shop_store_details'), [
                'details_heading' => __('Store Details', 'culvers'),
                'details_heading_level' => '2',
                'details_contact_label' => __('Contact Number', 'culvers'),
                'details_contact_phone' => __('01206 562073', 'culvers'),
                'details_address_label' => __('Address', 'culvers'),
                'details_address' => "10B Culver St W,\nColchester CO1 1WF",
                'details_social_label' => __('Social Media', 'culvers'),
                'details_instagram_url' => 'https://www.instagram.com/greggs_official/',
                'details_instagram_handle' => '@greggs_official',
            ]),
            PagesFlexibleSeedData::openingHoursRow(),
            PagesFlexibleSeedData::centreMapRow(),
            array_merge(self::base('section_header'), [
                'header_eyebrow' => '',
                'header_heading' => __('More flavours to discover', 'culvers'),
                'header_heading_level' => '2',
                'header_body' => '',
                'header_align' => 'center',
                'header_max_width' => 'medium',
            ]),
            array_merge(self::base('three_card_block'), [
                'cards_source' => 'manual',
                'cards_heading' => '',
                'cards_subheading' => '',
                'cards_heading_level' => '2',
                'cards_body' => '',
                'cards_items' => [
                    [
                        'card_title' => __('Toast Coffee', 'culvers'),
                        'card_url' => function_exists('home_url') ? home_url('/eat-drink/') : '/eat-drink/',
                        'card_media_type' => 'image',
                        'card_image' => null,
                        'card_image_alt' => '',
                        'card_video' => null,
                        'card_video_poster' => null,
                    ],
                    [
                        'card_title' => __('Subway', 'culvers'),
                        'card_url' => function_exists('home_url') ? home_url('/eat-drink/') : '/eat-drink/',
                        'card_media_type' => 'image',
                        'card_image' => null,
                        'card_image_alt' => '',
                        'card_video' => null,
                        'card_video_poster' => null,
                    ],
                    [
                        'card_title' => __('Juicy Bar Vitality', 'culvers'),
                        'card_url' => function_exists('home_url') ? home_url('/eat-drink/') : '/eat-drink/',
                        'card_media_type' => 'image',
                        'card_image' => null,
                        'card_image_alt' => '',
                        'card_video' => null,
                        'card_video_poster' => null,
                    ],
                ],
            ]),
        ];
    }

    /* -----------------------------------------------------------------
     * Easter Egg Hunt — representative `culvers_event` single (Figma "Latest
     * Events" three-card on the What's On landing). Stack mirrors the
     * Hotel Chocolat offer single so the two CPTs read as siblings.
     * --------------------------------------------------------------- */

    /**
     * @return list<array<string, mixed>>
     */
    public static function easterEggHunt(): array
    {
        return [
            array_merge(self::base('image_hero'), [
                'hero_image' => [
                    'url' => PagesFlexibleSeedData::seedAssetUrl(self::EVENT_HERO_FILE),
                    'alt' => __('Family taking part in an Easter Egg Hunt', 'culvers'),
                ],
                'hero_image_mobile' => null,
                'hero_logo' => null,
                'hero_title_line' => __('Culver Square Easter Egg Hunt', 'culvers'),
                'hero_title_tone' => 'glowleaf',
                'hero_subtitle_line' => __('Easter weekend at the centre', 'culvers'),
                'hero_overlay_opacity' => 35,
                'hero_title_in_image' => false,
            ]),
            array_merge(self::base('event_meta'), [
                'event_meta_date_label' => __('When', 'culvers'),
                'event_meta_date_value' => __('Easter weekend, Sat 4 – Mon 6 April 2026', 'culvers'),
                'event_meta_time_label' => __('Open today', 'culvers'),
                'event_meta_time_value' => __('10am – 4pm', 'culvers'),
                'event_meta_location_label' => __('Where', 'culvers'),
                'event_meta_location_value' => __('Lower Mall, Culver Square', 'culvers'),
                'event_meta_accessibility_note' => __(
                    'Step-free route between every hunt clue. The Guest Services desk has spare hunt cards '
                    . 'and accessible pencils on request.',
                    'culvers'
                ),
                'event_meta_cta_label' => __('Add to calendar', 'culvers'),
                'event_meta_cta_url' => '#',
            ]),
            array_merge(self::base('section_header'), [
                'header_eyebrow' => '',
                'header_heading' => __('Hop, skip and hunt your way around the centre.', 'culvers'),
                'header_heading_level' => '2',
                'header_body' => __(
                    'Pick up a hunt card from Guest Services and follow the clues across our retailers '
                    . 'to claim a chocolatey reward at the finish line. Free for all ages — no booking '
                    . 'needed, just turn up over Easter weekend.',
                    'culvers'
                ),
                'header_align' => 'center',
                'header_max_width' => 'medium',
            ]),
        ];
    }

    /* -----------------------------------------------------------------
     * Spring 2026 line-up — representative `culvers_news` single. Mirrors
     * the offer single shape so news / offers / events all share a layout.
     * --------------------------------------------------------------- */

    /**
     * @return list<array<string, mixed>>
     */
    public static function spring2026Lineup(): array
    {
        return [
            array_merge(self::base('image_hero'), [
                'hero_image' => [
                    'url' => PagesFlexibleSeedData::seedAssetUrl(self::EVENT_OFFER_IMAGE_FILE),
                    'alt' => __('Editorial photo of new spring storefront line-up', 'culvers'),
                ],
                'hero_image_mobile' => null,
                'hero_logo' => null,
                'hero_title_line' => __('Spring 2026 line-up at Culver Square.', 'culvers'),
                'hero_title_tone' => 'glowleaf',
                'hero_subtitle_line' => __('Centre news', 'culvers'),
                'hero_overlay_opacity' => 35,
                'hero_title_in_image' => false,
            ]),
            array_merge(self::base('section_header'), [
                'header_eyebrow' => __('Centre news', 'culvers'),
                'header_heading' => __('Five new openings, one refreshed mall.', 'culvers'),
                'header_heading_level' => '2',
                'header_body' => __(
                    'From Easter through to summer, Culver Square will welcome five new retailers and '
                    . 'unveil a refreshed Lower Mall. Read on for opening dates, brand line-up and what '
                    . 'this means for shoppers and the centre community.',
                    'culvers'
                ),
                'header_align' => 'center',
                'header_max_width' => 'medium',
            ]),
        ];
    }
}
