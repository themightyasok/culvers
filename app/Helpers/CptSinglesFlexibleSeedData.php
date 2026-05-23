<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Canonical flexible-content rows for one representative single per non-shop
 * directory CPT — the same purpose Accessorize London serves for `culvers_shop`.
 *
 * Pages built here:
 *   - culvers_career   → "Senior Supervisor" (Figma 51:6450)
 *   - culvers_event    → "Easter Egg Hunt" (Figma 51:6386 layout — same stack as offer single)
 *   - culvers_offer    → "Valentine's at Hotel Chocolat" (Figma 51:6386)
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

    /** Subway wordmark (Figma 51:6460 hero vector), committed SVG in seeds for sideload. */
    private const CAREER_SUBWAY_LOGO_FILE = 'subway-logo-hero.svg';

    private const CAREER_PERKS_FILE = 'career-perks.jpg';

    /** Hotel Chocolat Valentine's offer (Figma 51:6386) — heart-box hero + offer image. */
    private const EVENT_HERO_FILE = 'event-hero.jpg';

    private const EVENT_OFFER_IMAGE_FILE = 'event-one-gift.jpg';

    /** Greggs (Figma 51:6679) — storefront hero, festive bake split image. */
    private const GREGGS_HERO_FILE = 'greggs-hero-frame.jpg';

    private const GREGGS_FESTIVE_BAKE_FILE = 'greggs-festive-bake.jpg';

    /**
     * Related stories strip — latest items from the matching directory CPT.
     *
     * @param  non-empty-string  $heading
     * @param  non-empty-string  $postType
     * @return array<string, mixed>
     */
    private static function relatedStoriesThreeCardBlock(string $heading, string $postType): array
    {
        $archiveUrl = get_post_type_archive_link($postType);
        $bodyCopy = match ($postType) {
            'culvers_event' => __('See what else is happening at Culver Square.', 'culvers'),
            'culvers_news' => __('Stories from across Culver Square.', 'culvers'),
            default => __('See all of the brilliant offers happening at Culver Square', 'culvers'),
        };

        return array_merge(self::base('three_card_block'), [
            'cards_source' => 'cpt',
            'cards_heading' => $heading,
            'cards_subheading' => '',
            'cards_body' => sprintf('<p>%s</p>', esc_html($bodyCopy)),
            'cards_view_all_label' => __('View all', 'culvers'),
            'cards_view_all_url' => is_string($archiveUrl) ? $archiveUrl : '',
            'cards_cpt_post_type' => [$postType],
            'cards_cpt_count' => 3,
        ]);
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
                'hero_logo' => [
                    'url' => PagesFlexibleSeedData::seedAssetUrl(self::CAREER_SUBWAY_LOGO_FILE),
                    'alt' => __('Subway', 'culvers'),
                ],
                'hero_title_line' => __('Subway', 'culvers'),
                'hero_title_tone' => 'white',
                'hero_subtitle_line' => __('Now hiring at Culver Square', 'culvers'),
                'hero_overlay_opacity' => 40,
                'hero_title_in_image' => false,
            ]),
            array_merge(self::base('career_detail'), [
                        // Hero above already carries the Subway lockup (Figma 51:6450); no sidebar logo
                        // so the job title aligns with the first role section heading.
                        'career_job_title' => __('Senior Supervisor', 'culvers'),
                        // Image hero above already supplies the page H1 (the brand name);
                        // demote the job title to H2 so we don't ship two H1s.
                        'career_meta' => [
                        ['item_label' => __('Contract Type', 'culvers'), 'item_value' => __('Full-Time', 'culvers')],
                        ['item_label' => __('Location', 'culvers'), 'item_value' => __('Culver Square Shopping Centre', 'culvers')],
                        ['item_label' => __('Pay', 'culvers'), 'item_value' => __('£12.40 per hour', 'culvers')],
                        ],
                        'career_apply_label' => __('Apply Now', 'culvers'),
                        'career_apply_url' => $applyUrl,
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
     * Hotel Chocolat Valentine's offer single (Figma 51:6386)
     * Stack: image_hero → section_header → shop_split_highlight →
     *        social_share → three_card_block (latest events + View all)
     * --------------------------------------------------------------- */

    /**
     * @return list<array<string, mixed>>
     */
    public static function hotelChocolatOffer(): array
    {
        return [
            array_merge(self::base('image_hero'), [
                'hero_image' => [
                    'url' => PagesFlexibleSeedData::seedAssetUrl(self::EVENT_HERO_FILE),
                    'alt' => __('Heart-shaped Hotel Chocolat selection box', 'culvers'),
                ],
                'hero_image_mobile' => null,
                'hero_logo' => null,
                'hero_title_line' => '',
                'hero_title_tone' => 'white',
                'hero_subtitle_line' => '',
                'hero_overlay_opacity' => 40,
                'hero_title_in_image' => false,
            ]),
            array_merge(self::base('section_header'), [
                'header_eyebrow' => '',
                'header_heading' => __('A treat for every taste', 'culvers'),
                'header_body' => __(
                    'Indulge your loved one’s sweet tooth with decadent Valentine’s chocolates from Hotel Chocolat. '
                    . 'From heart-shaped boxes to luxurious truffle collections, there’s a treat for every taste.',
                    'culvers'
                ),
                'header_align' => 'center',
                'header_max_width' => 'medium',
            ]),
            array_merge(self::base('shop_split_highlight'), [
                'split_ratio' => '50-50',
                'split_use_tabs' => false,
                'split_copy_background' => 'olive',
                'split_image' => [
                    'url' => PagesFlexibleSeedData::seedAssetUrl(self::EVENT_OFFER_IMAGE_FILE),
                    'alt' => __('Open heart-shaped chocolate box with rose petals', 'culvers'),
                ],
                'split_kicker' => '',
                'split_headline' => __('One gift, a thousand words', 'culvers'),
                'split_body' => '<p>'
                    . esc_html__(
                        'Show them what they mean to you with an imaginatively crafted gift. Special offers available in store.',
                        'culvers'
                    )
                    . '</p>'
                    . '<ul>'
                    . '<li>' . esc_html__('Gift packages available', 'culvers') . '</li>'
                    . '<li>' . esc_html__('Valentine’s Selection box', 'culvers') . '</li>'
                    . '<li>' . esc_html__('Chocolate hearts', 'culvers') . '</li>'
                    . '<li>' . esc_html__('Buy one get one free on selected chocolates*', 'culvers') . '</li>'
                    . '</ul>'
                    . '<p class="font-label text-xs font-semibold uppercase tracking-widest text-glowleaf">'
                    . esc_html__('Offer valid between: 01.02.26 - 30.06.26', 'culvers')
                    . '</p>',
                'split_cta_label' => '',
                'split_cta_url' => '',
            ]),
            array_merge(self::base('social_share'), [
                'share_heading' => __('Share with a friend', 'culvers'),
            ]),
            self::relatedStoriesThreeCardBlock(__('More Offers', 'culvers'), 'culvers_offer'),
        ];
    }

    /* -----------------------------------------------------------------
     * Greggs venue eat & drink single (Figma 51:6679)
     * Stack: image_hero (Greggs storefront) → shop_intro_block →
     *        shop_split_highlight (Festive Bake, no tabs, no CTA) →
     *        shop_store_details → opening_hours → centre_map →
     *        shop_related_eat_drink (More flavours to discover)
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
                'details_show_social_column' => 1,
                'details_contact_phone' => __('01206 562073', 'culvers'),
                'details_address' => "10B Culver St W,\nColchester CO1 1WF",
                'details_instagram_url' => 'https://www.instagram.com/greggs_official/',
                'details_instagram_handle' => '@greggs_official',
            ]),
            PagesFlexibleSeedData::openingHoursRow(),
            PagesFlexibleSeedData::centreMapRow(),
            array_merge(self::base('shop_related_eat_drink'), [
                'eat_drink_related_heading' => __('More flavours to discover', 'culvers'),
                'eat_drink_related_view_all_url' => function_exists('get_post_type_archive_link')
                    ? (string) get_post_type_archive_link('culvers_eat_drink')
                    : '/eat-drink/',
                'eat_drink_related_view_all_label' => __('View all', 'culvers'),
                'eat_drink_related_posts' => [],
            ]),
        ];
    }

    /* -----------------------------------------------------------------
     * Easter Egg Hunt — representative `culvers_event` single (Figma 51:6386).
     * Stack: image_hero → section_header (centred) → shop_split_highlight
     *        → social_share → three_card_block (Figma card art).
     * --------------------------------------------------------------- */

    /**
     * Canonical flexible stack for every single in a What's On CPT.
     *
     * @return list<array<string, mixed>>
     */
    public static function canonicalRowsForPostType(string $postType): array
    {
        return match ($postType) {
            'culvers_event' => self::easterEggHunt(),
            'culvers_offer' => self::hotelChocolatOffer(),
            'culvers_news' => self::spring2026Lineup(),
            default => throw new \InvalidArgumentException(
                sprintf('No canonical flexible seed for post type "%s".', $postType)
            ),
        };
    }

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
            array_merge(self::base('section_header'), [
                'header_eyebrow' => '',
                'header_heading' => __('Hop, skip and hunt your way around the centre', 'culvers'),
                'header_body' => __(
                    'Pick up a hunt card from Guest Services and follow the clues across our retailers '
                    . 'to claim a chocolatey reward at the finish line. Free for all ages — no booking '
                    . 'needed, just turn up over Easter weekend.',
                    'culvers'
                ),
                'header_align' => 'center',
                'header_max_width' => 'medium',
            ]),
            array_merge(self::base('shop_split_highlight'), [
                'split_ratio' => '50-50',
                'split_use_tabs' => false,
                'split_copy_background' => 'olive',
                'split_image' => [
                    'url' => PagesFlexibleSeedData::seedAssetUrl(self::EVENT_OFFER_IMAGE_FILE),
                    'alt' => __('Children following Easter hunt clues in the mall', 'culvers'),
                ],
                'split_kicker' => '',
                'split_headline' => __('One hunt, a thousand smiles', 'culvers'),
                'split_body' => '<p>'
                    . esc_html__(
                        'Collect your map from Guest Services, follow the trail past participating stores '
                        . 'and redeem a chocolate reward when you complete every clue.',
                        'culvers'
                    )
                    . '</p>'
                    . '<ul>'
                    . '<li>' . esc_html__('Free hunt cards for all ages', 'culvers') . '</li>'
                    . '<li>' . esc_html__('Clues across both mall levels', 'culvers') . '</li>'
                    . '<li>' . esc_html__('Chocolate reward at the finish line', 'culvers') . '</li>'
                    . '<li>' . esc_html__('No booking required', 'culvers') . '</li>'
                    . '</ul>'
                    . '<p class="font-label text-xs font-semibold uppercase tracking-widest text-glowleaf">'
                    . esc_html__('Running: Sat 4 – Mon 6 April 2026 · 10am – 4pm · Lower Mall', 'culvers')
                    . '</p>',
                'split_cta_label' => '',
                'split_cta_url' => '',
            ]),
            array_merge(self::base('social_share'), [
                'share_heading' => __('Share with a friend', 'culvers'),
            ]),
            self::relatedStoriesThreeCardBlock(__('More events', 'culvers'), 'culvers_event'),
        ];
    }

    /* -----------------------------------------------------------------
     * Spring 2026 line-up — representative `culvers_news` single (no event_meta
     * panel — published / reading-time rows are not shown on news singles).
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
                'header_body' => __(
                    'From Easter through to summer, Culver Square will welcome five new retailers and '
                    . 'unveil a refreshed Lower Mall. Read on for opening dates, brand line-up and what '
                    . 'this means for shoppers and the centre community.',
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
                    'alt' => __('Refreshed Lower Mall walkway at Culver Square', 'culvers'),
                ],
                'split_kicker' => '',
                'split_headline' => __('A brighter Lower Mall for spring.', 'culvers'),
                'split_body' => '<p>'
                    . esc_html__(
                        'The refreshed mall walk brings new lighting, seating and planting — '
                        . 'creating a calmer route between the atrium and the new store line-up.',
                        'culvers'
                    )
                    . '</p>',
                'split_cta_label' => '',
                'split_cta_url' => '',
            ]),
            array_merge(self::base('section_header'), [
                'header_eyebrow' => '',
                'header_heading' => __('More centre news', 'culvers'),
                'header_body' => __(
                    'Catch up on sustainability milestones, trading hours and community stories from across Culver Square.',
                    'culvers'
                ),
                'header_align' => 'center',
                'header_max_width' => 'medium',
            ]),
            self::relatedStoriesThreeCardBlock(__('Latest news', 'culvers'), 'culvers_news'),
        ];
    }
}
