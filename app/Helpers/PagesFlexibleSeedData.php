<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Constants\ComponentTypes;

/**
 * Canonical flexible-content rows for the standard top-level pages
 * (Plan My Visit, Contact, Guest Services, Leasing Opportunities).
 *
 * Mirrors the {@see HomepageFlexibleSeedData} pattern: every row is a
 * plain array; image references are wrapped as `['url' => …]` so
 * {@see HomepageFlexibleAcfAttach} can sideload them on first run.
 *
 * Source of truth for layout + copy is the Figma developer release file
 * (`KoBl6rTY98YnvusBgKLx4A`) — node IDs are referenced inline against
 * each page method.
 */
final class PagesFlexibleSeedData
{
    /**
     * Figma developer-handover assets for these pages are exported once via
     * the MCP plugin and committed to `resources/images/seeds/` (their MCP
     * URLs are short-lived and would otherwise expire between populate runs).
     * The {@see seedAssetUrl()} helper rewrites them to a live theme URL on
     * call so {@see HomepageFlexibleAcfAttach} can sideload them.
     *
     * Filenames mirror the pages they belong to so it's obvious which
     * Figma node each one came from when refreshing the seed library.
     */

    /** Hours graphics share the homepage seed library (filenames intentional). */
    private const HOURS_GRAPHIC_LEFT_FILE = 'hours-graphic-left.png';

    private const HOURS_GRAPHIC_RIGHT_FILE = 'hours-graphic-right.png';

    /** Centre map graphic — Figma "CulverSqrMap_May2025_Green 1" (e.g. 51:6811). */
    private const CENTRE_MAP_IMAGE_FILE = 'centre-map.png';

    /** Contact page (51:9353) — green-jumper hero. */
    private const CONTACT_HERO_FILE = 'contact-hero.jpg';

    /** Plan My Visit (51:5872) — bus-laughing hero, in-car split image. */
    private const PLAN_VISIT_HERO_FILE = 'plan-my-visit-hero.jpg';

    private const PLAN_VISIT_BY_CAR_FILE = 'plan-by-car.jpg';

    /** Guest Services (51:8033) — couple shopping hero, Colchester castle split image. */
    private const GUEST_SERVICES_HERO_FILE = 'guest-services-hero.jpg';

    private const GUEST_SERVICES_HISTORY_FILE = 'guest-history.jpg';

    /** Leasing Opportunities (51:6500) — aerial roof hero, plaza-shoppers split image. */
    private const LEASING_HERO_FILE = 'leasing-hero.jpg';

    private const LEASING_PROMO_FILE = 'leasing-promo.jpg';

    /**
     * What’s On landing (51:6386) — same lifestyle band as /latest-events/ archive
     * ({@see DirectoryArchiveHeroPopulate} `archive-whats-on-hero.jpg`).
     */
    private const WHATS_ON_LANDING_HERO_FILE = 'archive-whats-on-hero.jpg';

    /**
     * Resolve a seed asset filename to a live URL on the active theme so
     * {@see HomepageFlexibleAcfAttach} can sideload it.
     *
     * Falls back to a relative path during unit-test / non-WordPress runs
     * (when `get_template_directory_uri()` is not yet defined).
     */
    public static function seedAssetUrl(string $filename): string
    {
        $relative = '/resources/images/seeds/' . ltrim($filename, '/');
        if (function_exists('get_template_directory_uri')) {
            return rtrim(get_template_directory_uri(), '/') . $relative;
        }

        return $relative;
    }

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
     * Contact page (Figma 51:9353)
     * Stack: image_hero → contact (with map) → opening_hours
     * --------------------------------------------------------------- */

    /**
     * @return list<array<string, mixed>>
     */
    public static function contactPage(): array
    {
        return [
            array_merge(self::base('image_hero'), [
                'hero_image' => [
                    'url' => self::seedAssetUrl(self::CONTACT_HERO_FILE),
                    'alt' => __('Visitor on her phone outside Culver Square', 'culvers'),
                ],
                'hero_image_mobile' => null,
                'hero_logo' => null,
                'hero_title_line' => __('Get In Touch.', 'culvers'),
                'hero_title_tone' => 'glowleaf',
                'hero_subtitle_line' => __('Fill in the form below to get in touch!', 'culvers'),
                'hero_overlay_opacity' => 35,
                'hero_title_in_image' => false,
            ]),
            array_merge(self::base('contact'), [
                'contact_heading' => '',
                'contact_show_panel' => true,
                'contact_show_map' => true,
                'contact_form_first_name_label' => __('First name*', 'culvers'),
                'contact_form_first_name_placeholder' => __('Name', 'culvers'),
                'contact_form_last_name_label' => __('Last name*', 'culvers'),
                'contact_form_last_name_placeholder' => __('Last name', 'culvers'),
                'contact_form_email_label' => __('Email*', 'culvers'),
                'contact_form_email_placeholder' => __('Email address', 'culvers'),
                'contact_form_reason_label' => __('Reason for enquiry*', 'culvers'),
                'contact_form_reason_placeholder' => __('Select', 'culvers'),
                'contact_form_message_label' => __('Message', 'culvers'),
                'contact_form_message_placeholder' => __('Type message here', 'culvers'),
                'contact_form_submit_label' => __('Submit', 'culvers'),
                'contact_form_success_message' => __('Thanks — your message is on its way.', 'culvers'),
                'contact_form_reasons' => [
                    ['item_reason' => __('General enquiry', 'culvers')],
                    ['item_reason' => __('Lost property', 'culvers')],
                    ['item_reason' => __('Accessibility', 'culvers')],
                    ['item_reason' => __('Feedback', 'culvers')],
                    ['item_reason' => __('Press / media', 'culvers')],
                    ['item_reason' => __('Leasing & commercial', 'culvers')],
                ],
            ]),
            self::openingHoursRow(),
        ];
    }

    /* -----------------------------------------------------------------
     * Plan My Visit page (Figma 51:5872)
     * Stack: image_hero → section_header (Getting Here) →
     *        shop_split_highlight (tabs: Car/Bus/Train/Bicycle) →
     *        travel_calculator → centre_map →
     *        section_header (Accessible Guide CTA) → opening_hours
     * --------------------------------------------------------------- */

    /**
     * @return list<array<string, mixed>>
     */
    public static function planMyVisitPage(): array
    {
        return [
            array_merge(self::base('image_hero'), [
                'hero_image' => [
                    'url' => self::seedAssetUrl(self::PLAN_VISIT_HERO_FILE),
                    'alt' => __('Friends sharing a laugh on a visit to Culver Square', 'culvers'),
                ],
                'hero_image_mobile' => null,
                'hero_logo' => null,
                'hero_title_line' => __('Plan my visit.', 'culvers'),
                'hero_title_tone' => 'glowleaf',
                'hero_subtitle_line' => __('Let’s take the stress out of shopping', 'culvers'),
                'hero_overlay_opacity' => 35,
                'hero_title_in_image' => false,
            ]),
            array_merge(self::base('section_header'), [
                'header_eyebrow' => '',
                'header_heading' => __('Getting Here', 'culvers'),
                'header_heading_level' => '2',
                'header_body' => __(
                    'Retail therapy is always a pleasure when visiting Culver Square. '
                    . 'We’ve got everything you need for a smooth and successful shopping experience, '
                    . 'all in one place. From public transport to parking information, '
                    . 'here are the finer details to help you plan your visit.',
                    'culvers'
                ),
                'header_align' => 'center',
                'header_max_width' => 'medium',
            ]),
            array_merge(self::base('shop_split_highlight'), [
                'split_ratio' => '50-50',
                'split_use_tabs' => true,
                'split_image' => [
                    'url' => self::seedAssetUrl(self::PLAN_VISIT_BY_CAR_FILE),
                    'alt' => __('Family enjoying a road trip to Culver Square', 'culvers'),
                ],
                'split_tabs' => [
                    [
                        'tab_label' => __('By Car', 'culvers'),
                        'tab_kicker' => '',
                        'tab_headline' => __('By Car', 'culvers'),
                        'tab_body' => '<p>'
                            . esc_html__(
                                'Culver Square sits in the heart of Colchester with on-site parking '
                                . '24 hours a day, seven days a week. Follow signs for the town centre '
                                . 'and look for our wayfinding into Osborne Street car park (CO1 1RJ).',
                                'culvers'
                            )
                            . '</p><p>'
                            . esc_html__(
                                'Blue badge bays are available on every level. Charges follow the '
                                . 'standard pay-on-foot tariff displayed at the entry barriers.',
                                'culvers'
                            )
                            . '</p>',
                        'tab_cta_label' => __('Parking information', 'culvers'),
                        'tab_cta_url' => 'https://www.culversquare.co.uk/visit/parking',
                    ],
                    [
                        'tab_label' => __('By Bus', 'culvers'),
                        'tab_kicker' => '',
                        'tab_headline' => __('By Bus', 'culvers'),
                        'tab_body' => '<p>'
                            . esc_html__(
                                'Colchester’s Osborne Street and Queen Street bus stops are a two-minute '
                                . 'walk from the centre. First Essex routes 61, 64, 64A, 65, 66, 67, 70 '
                                . 'and 75 all stop within the town loop.',
                                'culvers'
                            )
                            . '</p>',
                        'tab_cta_label' => __('Plan your route', 'culvers'),
                        'tab_cta_url' => 'https://www.firstbus.co.uk/essex',
                    ],
                    [
                        'tab_label' => __('By Train', 'culvers'),
                        'tab_kicker' => '',
                        'tab_headline' => __('By Train', 'culvers'),
                        'tab_body' => '<p>'
                            . esc_html__(
                                'Colchester Town station is a five-minute level walk to Culver Square; '
                                . 'Colchester North is around 15 minutes by foot or a short taxi. Greater '
                                . 'Anglia run direct services from London Liverpool Street (around 50 minutes).',
                                'culvers'
                            )
                            . '</p>',
                        'tab_cta_label' => __('Live train times', 'culvers'),
                        'tab_cta_url' => 'https://www.greateranglia.co.uk',
                    ],
                    [
                        'tab_label' => __('By Bicycle', 'culvers'),
                        'tab_kicker' => '',
                        'tab_headline' => __('By Bicycle', 'culvers'),
                        'tab_body' => '<p>'
                            . esc_html__(
                                'Free covered cycle racks are positioned at every entrance. The Roman '
                                . 'River and St Botolph’s cycle routes both connect into Osborne Street, '
                                . 'so you can ride straight to the centre and lock up under cover.',
                                'culvers'
                            )
                            . '</p>',
                        'tab_cta_label' => __('See cycle routes', 'culvers'),
                        'tab_cta_url' => 'https://www.colchester.gov.uk/cycling',
                    ],
                ],
            ]),
            array_merge(self::base('travel_calculator'), [
                'background_type' => ComponentTypes::BACKGROUND_NONE,
                'tc_heading' => __('Travel Calculator', 'culvers'),
                'tc_intro' => __(
                    'Find out how close Culver is to your work or any point of interest.',
                    'culvers'
                ),
                'tc_destination_label' => __('Your destination', 'culvers'),
                'tc_destination_placeholder' => __('Type your destination here', 'culvers'),
                'tc_mode_label' => __('Travel by', 'culvers'),
                'tc_mode_placeholder' => __('Select', 'culvers'),
                'tc_modes' => [
                    ['item_mode' => 'driving', 'item_label' => __('Car (driving)', 'culvers')],
                    ['item_mode' => 'transit', 'item_label' => __('Public transport', 'culvers')],
                    ['item_mode' => 'walking', 'item_label' => __('Walking', 'culvers')],
                    ['item_mode' => 'bicycling', 'item_label' => __('Cycling', 'culvers')],
                ],
                'tc_button_label' => __('Search', 'culvers'),
                'tc_show_map' => true,
                'tc_map_initial_image' => null,
            ]),
            self::centreMapRow(),
            array_merge(self::base('info_block'), [
                'info_heading' => __('Accessible Guide', 'culvers'),
                'info_heading_level' => '2',
                'info_subheading' => '',
                'info_body' => sprintf(
                    '<p>%s</p>',
                    esc_html__(
                        'Shopping and days out in town shouldn’t be tricky. At Culver Square, we strive to '
                        . 'provide a warm welcome and comfortable visits for all our visitors. For more '
                        . 'information please visit our Accessibility guide via the link below.',
                        'culvers'
                    )
                ),
                'info_cta_label' => __('Accessible guide', 'culvers'),
                'info_cta_url' => function_exists('home_url') ? home_url('/accessible-guide/') : '/accessible-guide/',
                'info_items' => [],
            ]),
            self::openingHoursRow(),
        ];
    }

    /* -----------------------------------------------------------------
     * Guest Services page (Figma 51:8033)
     * Stack: image_hero → section_header (About Colchester) →
     *        shop_split_highlight (tabs: History / Art & Culture / Open Spaces) →
     *        text_image_slider (services list) → faq
     * --------------------------------------------------------------- */

    /**
     * @return list<array<string, mixed>>
     */
    public static function guestServicesPage(): array
    {
        return [
            array_merge(self::base('image_hero'), [
                'hero_image' => [
                    'url' => self::seedAssetUrl(self::GUEST_SERVICES_HERO_FILE),
                    'alt' => __('Couple shopping at Culver Square', 'culvers'),
                ],
                'hero_image_mobile' => null,
                'hero_logo' => null,
                'hero_title_line' => __('Guest Services.', 'culvers'),
                'hero_title_tone' => 'glowleaf',
                'hero_subtitle_line' => __('If there’s anything you can’t find, just drop us a line.', 'culvers'),
                'hero_overlay_opacity' => 35,
                'hero_title_in_image' => false,
            ]),
            array_merge(self::base('section_header'), [
                'header_eyebrow' => '',
                'header_heading' => __('About Colchester', 'culvers'),
                'header_heading_level' => '2',
                'header_body' => __(
                    'Colchester is a historic Essex town, it was Britain’s first city and former capital '
                    . 'of Roman Britain and has recently been awarded city status as part of the '
                    . 'Platinum Jubilee celebrations. Its rich history dates back over 2000 years and '
                    . 'is ripe for exploring.',
                    'culvers'
                ),
                'header_align' => 'center',
                'header_max_width' => 'medium',
            ]),
            array_merge(self::base('shop_split_highlight'), [
                'split_ratio' => '50-50',
                'split_use_tabs' => true,
                'split_image' => [
                    'url' => self::seedAssetUrl(self::GUEST_SERVICES_HISTORY_FILE),
                    'alt' => __('Colchester Castle in golden light', 'culvers'),
                ],
                'split_tabs' => [
                    [
                        'tab_label' => __('History', 'culvers'),
                        'tab_kicker' => '',
                        'tab_headline' => __('History', 'culvers'),
                        'tab_body' => '<p>'
                            . esc_html__(
                                'Walk five minutes from Culver Square and you can stand inside the largest '
                                . 'Norman keep in Europe. Colchester Castle, the Roman walls and the '
                                . 'Dutch Quarter make this one of Britain’s most layered city centres.',
                                'culvers'
                            )
                            . '</p>',
                        'tab_cta_label' => __('Visit Colchester', 'culvers'),
                        'tab_cta_url' => 'https://www.visitcolchester.com',
                    ],
                    [
                        'tab_label' => __('Art & Culture', 'culvers'),
                        'tab_kicker' => '',
                        'tab_headline' => __('Art & Culture', 'culvers'),
                        'tab_body' => '<p>'
                            . esc_html__(
                                'The Mercury Theatre, Firstsite gallery and Colchester Arts Centre are all '
                                . 'within a ten-minute walk. Look out for our regular pop-up programme '
                                . 'across Culver Square, from live music to maker markets.',
                                'culvers'
                            )
                            . '</p>',
                        'tab_cta_label' => __('What’s on this season', 'culvers'),
                        'tab_cta_url' => function_exists('home_url') ? home_url('/whats-on/') : '/whats-on/',
                    ],
                    [
                        'tab_label' => __('Open Spaces', 'culvers'),
                        'tab_kicker' => '',
                        'tab_headline' => __('Open Spaces', 'culvers'),
                        'tab_body' => '<p>'
                            . esc_html__(
                                'Castle Park, Lower Castle Park and the Dutch Quarter sit on the doorstep, '
                                . 'with the river walk and Hilly Fields a short stroll further out. Pack a '
                                . 'picnic from one of our cafés and make a day of it.',
                                'culvers'
                            )
                            . '</p>',
                        'tab_cta_label' => '',
                        'tab_cta_url' => '',
                    ],
                ],
            ]),
            array_merge(self::base('text_image_slider'), [
                'tis_heading' => '',
                'tis_heading_level' => '2',
                'tis_open_mode' => 'single',
                'tis_initial_open_index' => 0,
                'tis_items' => [
                    self::tisRow(
                        __('Click & Collect', 'culvers'),
                        'Pick up online orders from our concierge desk on the lower level. Open during '
                        . 'centre hours, no need to book in advance.',
                    ),
                    self::tisRow(
                        __('First Aid', 'culvers'),
                        'Trained first responders are on duty across opening hours. Approach any security '
                        . 'team member or speak to staff at the management suite for help.',
                    ),
                    self::tisRow(
                        __('Security', 'culvers'),
                        'Our 24/7 security team patrols the centre, the car park and the Osborne Street '
                        . 'frontage. CCTV monitors every walkway and entrance.',
                    ),
                    self::tisRow(
                        __('Parent & Child Facilities', 'culvers'),
                        'Baby-change facilities are available on every level along with a dedicated '
                        . 'feeding room near the management suite.',
                    ),
                    self::tisRow(
                        __('Lost Property', 'culvers'),
                        'If you’ve mislaid something during your visit, drop us a line and we will check '
                        . 'against the centre’s lost property log. Most items can be collected within 28 days.',
                    ),
                    self::tisRow(
                        __('Access & Mobility', 'culvers'),
                        'Step-free access throughout the centre, accessible toilets on every level and '
                        . 'mobility scooter loan via the management suite (please call ahead).',
                    ),
                    self::tisRow(
                        __('Facilities', 'culvers'),
                        'Public toilets, free Wi-Fi, payphones, ATMs and water-bottle fill stations are '
                        . 'all available across both levels of Culver Square.',
                    ),
                ],
            ]),
            array_merge(self::base('faq'), [
                'faq_heading' => __('Frequently Asked Questions', 'culvers'),
                'faq_heading_level' => '2',
                'faq_show_keyline' => true,
                'faq_open_mode' => 'single',
                'faq_items' => [
                    [
                        'item_question' => __('Is the shopping centre wheelchair accessible?', 'culvers'),
                        'item_answer' => '<p>'
                            . esc_html__(
                                'Yes — every level of Culver Square is step-free, with lifts at each end '
                                . 'of the mall and accessible toilets near both the lower and upper '
                                . 'concourses. Mobility scooters can be borrowed from the management suite '
                                . '(please call ahead so we can have one ready).',
                                'culvers'
                            )
                            . '</p>',
                        'item_open_default' => true,
                    ],
                    [
                        'item_question' => __('Is parking available at Culver Square?', 'culvers'),
                        'item_answer' => '<p>'
                            . esc_html__(
                                'Osborne Street car park sits directly under the centre and is open '
                                . '24 hours a day, seven days a week. Blue badge bays are available on '
                                . 'every level and our parent & child bays are next to each lift core.',
                                'culvers'
                            )
                            . '</p>',
                        'item_open_default' => false,
                    ],
                    [
                        'item_question' => __('Are dogs allowed in Culver Square?', 'culvers'),
                        'item_answer' => '<p>'
                            . esc_html__(
                                'Assistance dogs are welcome everywhere in the centre. Well-behaved pet '
                                . 'dogs on a short lead are welcome in the public mall and many of our '
                                . 'retailers — please check in-store before you enter.',
                                'culvers'
                            )
                            . '</p>',
                        'item_open_default' => false,
                    ],
                    [
                        'item_question' => __('Are there accessible toilet facilities available at Culver Square?', 'culvers'),
                        'item_answer' => '<p>'
                            . esc_html__(
                                'Yes — fully accessible toilets are available on each level and a '
                                . 'Changing Places facility is located on the lower level near the '
                                . 'management suite. Both can be opened with a RADAR key.',
                                'culvers'
                            )
                            . '</p>',
                        'item_open_default' => false,
                    ],
                ],
                // Decorative line-art is optional; the FAQ component renders fine without it.
                'faq_decorations_left' => [],
                'faq_decorations_right' => [],
            ]),
        ];
    }

    /* -----------------------------------------------------------------
     * Leasing Opportunities page (Figma 51:6500)
     * Stack: image_hero → section_header (Commercialisation) →
     *        shop_split_highlight (no tabs: Promotional Space) →
     *        section_header (Lettings) →
     *        three_card_block (manual: 3 agent contacts) →
     *        info_block (Can’t see what you’re looking for? + CTA)
     * --------------------------------------------------------------- */

    /**
     * @return list<array<string, mixed>>
     */
    public static function leasingPage(): array
    {
        $contactUrl = function_exists('home_url') ? home_url('/contact/') : '/contact/';

        return [
            array_merge(self::base('image_hero'), [
                'hero_image' => [
                    'url' => self::seedAssetUrl(self::LEASING_HERO_FILE),
                    'alt' => __('Aerial view across Colchester rooftops to Culver Square', 'culvers'),
                ],
                'hero_image_mobile' => null,
                'hero_logo' => null,
                'hero_title_line' => __('Leasing Opportunities', 'culvers'),
                'hero_title_tone' => 'glowleaf',
                'hero_subtitle_line' => __('A place to make your mark', 'culvers'),
                'hero_overlay_opacity' => 35,
                'hero_title_in_image' => false,
            ]),
            array_merge(self::base('section_header'), [
                'header_eyebrow' => '',
                'header_heading' => __('Commercialisation Opportunities', 'culvers'),
                'header_heading_level' => '2',
                'header_body' => __(
                    'If you wish to enquire about advertising or have any other commercial '
                    . 'enquiries please contact us via email.',
                    'culvers'
                ),
                'header_align' => 'center',
                'header_max_width' => 'medium',
            ]),
            array_merge(self::base('shop_split_highlight'), [
                'split_ratio' => '50-50',
                'split_use_tabs' => false,
                'split_image' => [
                    'url' => self::seedAssetUrl(self::LEASING_PROMO_FILE),
                    'alt' => __('Visitors enjoying a sunny afternoon at Culver Square', 'culvers'),
                ],
                'split_kicker' => '',
                'split_headline' => __('Promotional Space', 'culvers'),
                'split_body' => '<p>'
                    . esc_html__(
                        'SpaceandPeople PLC manages promotional and pop-up retail space within venues '
                        . 'throughout the UK. Founded in 2000, the company has a wealth of experience '
                        . 'matching brands, promoters and retailers with the right location. The activity '
                        . 'SpaceandPeople places into venues adds vitality and enhances the customer '
                        . 'experience while raising brand awareness and delivering success to the '
                        . 'companies occupying the space.',
                        'culvers'
                    )
                    . '</p><p>'
                    . esc_html__(
                        'For more information on current opportunities, please contact SpaceandPeople by '
                        . 'emailing help@spaceandpeople.co.uk or calling 033 33 401 500.',
                        'culvers'
                    )
                    . '</p>',
                'split_cta_label' => __('Visit website', 'culvers'),
                'split_cta_url' => 'https://www.spaceandpeople.co.uk',
            ]),
            array_merge(self::base('section_header'), [
                'header_eyebrow' => '',
                'header_heading' => __('Lettings', 'culvers'),
                'header_heading_level' => '2',
                'header_body' => __(
                    'If you would like to know more about letting a unit at Culver Square, '
                    . 'please contact the following agents.',
                    'culvers'
                ),
                'header_align' => 'center',
                'header_max_width' => 'medium',
            ]),
            // Three lettings agent cards. Card titles double up as the agent name; the
            // body copy carries phone + URL because three_card_block doesn't expose
            // sub-fields for those today (kept inline so editors can edit them directly).
            array_merge(self::base('three_card_block'), [
                'cards_source' => 'manual',
                'cards_heading' => '',
                'cards_subheading' => '',
                'cards_heading_level' => '2',
                'cards_body' => '',
                'cards_items' => [
                    [
                        'card_title' => __('Green & Partners', 'culvers'),
                        'card_url' => 'https://www.greenandpartners.co.uk',
                        'card_media_type' => 'image',
                        'card_image' => null,
                        'card_image_alt' => '',
                        'card_video' => null,
                        'card_video_poster' => null,
                    ],
                    [
                        'card_title' => __('Whybrow', 'culvers'),
                        'card_url' => 'https://www.whybrow.net',
                        'card_media_type' => 'image',
                        'card_image' => null,
                        'card_image_alt' => '',
                        'card_video' => null,
                        'card_video_poster' => null,
                    ],
                    [
                        'card_title' => __('Cushman & Wakefield', 'culvers'),
                        'card_url' => 'https://www.cushmanwakefield.com',
                        'card_media_type' => 'image',
                        'card_image' => null,
                        'card_image_alt' => '',
                        'card_video' => null,
                        'card_video_poster' => null,
                    ],
                ],
            ]),
            array_merge(self::base('info_block'), [
                'info_heading' => __('Can’t see what you’re looking for?', 'culvers'),
                'info_heading_level' => '2',
                'info_subheading' => '',
                'info_body' => sprintf(
                    '<p>%s</p>',
                    esc_html__(
                        'Get in touch with us via the link below and we’ll do our best to '
                        . 'help answer your questions.',
                        'culvers'
                    )
                ),
                'info_cta_label' => __('Contact us', 'culvers'),
                'info_cta_url' => $contactUrl,
                'info_items' => [],
            ]),
        ];
    }

    /* -----------------------------------------------------------------
     * What's On landing (Figma — `/whats-on/`)
     * Stack: image_hero → three_card_block (Latest Events, CPT) →
     *        three_card_block (Latest News, CPT) →
     *        three_card_block (Latest Offers, CPT) →
     *        opening_hours
     *
     * Each three_card_block uses the new `cpt` source to pull its three
     * latest items straight from the matching directory CPT. The View all
     * URL is left blank — `ThreeCardBlock::viewAllUrl()` resolves it to the
     * CPT's archive (`/latest-{events,news,offers}/`) automatically.
     * --------------------------------------------------------------- */

    /**
     * @return list<array<string, mixed>>
     */
    public static function whatsOnLandingPage(): array
    {
        return [
            array_merge(self::base('image_hero'), [
                'hero_image' => [
                    'url' => self::seedAssetUrl(self::WHATS_ON_LANDING_HERO_FILE),
                    'alt' => __('Visitors enjoying an event at Culver Square', 'culvers'),
                ],
                'hero_image_mobile' => null,
                'hero_logo' => null,
                'hero_title_line' => __('What’s on.', 'culvers'),
                'hero_title_tone' => 'glowleaf',
                'hero_subtitle_line' => __(
                    'The latest events, news and offers — all in one place.',
                    'culvers'
                ),
                'hero_overlay_opacity' => 35,
                'hero_title_in_image' => false,
            ]),
            self::whatsOnStrip(
                'culvers_event',
                __('Latest Events', 'culvers'),
                __('All the latest happenings and events from Culver Square', 'culvers'),
                __('View all', 'culvers')
            ),
            self::whatsOnStrip(
                'culvers_news',
                __('Latest News', 'culvers'),
                __('Centre updates, retailer announcements and editorial', 'culvers'),
                __('View all', 'culvers')
            ),
            self::whatsOnStrip(
                'culvers_offer',
                __('Latest Offers', 'culvers'),
                __('Promotions and brand campaigns from across the centre', 'culvers'),
                __('View all', 'culvers')
            ),
            self::openingHoursRow(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function whatsOnStrip(
        string $postType,
        string $heading,
        string $body,
        string $viewAllLabel
    ): array {
        return array_merge(self::base('three_card_block'), [
            'cards_source' => 'cpt',
            'cards_heading' => $heading,
            'cards_subheading' => '',
            'cards_heading_level' => '2',
            'cards_body' => sprintf('<p>%s</p>', esc_html($body)),
            'cards_items' => [],
            'cards_blog_categories' => [],
            'cards_blog_per_category' => 3,
            'cards_cpt_post_type' => $postType,
            'cards_cpt_count' => 3,
            'cards_view_all_url' => '',
            'cards_view_all_label' => $viewAllLabel,
        ]);
    }

    /* -----------------------------------------------------------------
     * Shared row builders re-used across pages
     * --------------------------------------------------------------- */

    /**
     * @return array<string, mixed>
     */
    public static function openingHoursRow(): array
    {
        return array_merge(self::base('opening_hours'), [
            'hours_heading' => __('Opening Hours', 'culvers'),
            'hours_heading_level' => '2',
            'hours_subheading' => '',
            'hours_body' => '',
            // Hours line-art is shipped via the homepage seed library (hours-graphic-*).
            // Falls through gracefully if the file isn't present yet.
            'hours_graphic_left' => ['url' => self::seedAssetUrl(self::HOURS_GRAPHIC_LEFT_FILE), 'alt' => ''],
            'hours_graphic_right' => ['url' => self::seedAssetUrl(self::HOURS_GRAPHIC_RIGHT_FILE), 'alt' => ''],
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

    /**
     * Centre map row used on Plan My Visit and the single eat & drink page.
     * Pin coordinates are illustrative — they sit on the placeholder map at
     * positions that match the Figma annotations.
     *
     * @return array<string, mixed>
     */
    public static function centreMapRow(): array
    {
        // Build category rows from a compact spec so individual lines stay
        // under the project's 220-char hard limit (and 180-char warning).
        // Labels are translated at literal call sites here so `wp i18n make-pot`
        // can extract them; the foreach below only stitches the row arrays.
        // Group labels mirror the Figma accordion sections (Shop / Eat & Drink /
        // Guest Services) — the centre_map blade collapses them into one
        // accordion per group.
        $shopGroup = __('Shop', 'culvers');
        $eatDrinkGroup = __('Eat and drink', 'culvers');
        $guestServicesGroup = __('Guest Services', 'culvers');

        $categorySpecs = [
            [$shopGroup, 'shop-all', __('All', 'culvers'), '/shops/'],
            [$shopGroup, 'beauty-wellbeing', __('Beauty & Wellbeing', 'culvers'), '/shops/?category=beauty-wellbeing'],
            [$shopGroup, 'fashion', __('Fashion', 'culvers'), '/shops/?category=fashion'],
            [$shopGroup, 'jewellery', __('Jewellery', 'culvers'), '/shops/?category=jewellery'],
            [$shopGroup, 'toys-gifts', __('Toys & Gifts', 'culvers'), '/shops/?category=toys-gifts'],
            [$shopGroup, 'technology', __('Technology', 'culvers'), '/shops/?category=technology'],
            [$shopGroup, 'services', __('Services', 'culvers'), '/shops/?category=services'],
            [$shopGroup, 'home', __('Home', 'culvers'), '/shops/?category=home'],
            [$eatDrinkGroup, 'eat-drink-all', __('All', 'culvers'), '/eat-drink/'],
            [$eatDrinkGroup, 'eat-drink-cafes', __('Cafés & Coffee', 'culvers'), '/eat-drink/?type=cafes'],
            [$eatDrinkGroup, 'eat-drink-takeaway', __('Takeaway', 'culvers'), '/eat-drink/?type=takeaway'],
            [$eatDrinkGroup, 'eat-drink-restaurants', __('Restaurants', 'culvers'), '/eat-drink/?type=restaurants'],
            [$guestServicesGroup, 'guest-services', __('Guest Services Desk', 'culvers'), '/guest-services/'],
            [$guestServicesGroup, 'click-collect', __('Click & Collect', 'culvers'), '/guest-services/#click-collect'],
            [$guestServicesGroup, 'parent-child', __('Parent & Child Facilities', 'culvers'), '/guest-services/#parent-child'],
            [$guestServicesGroup, 'lost-property', __('Lost Property', 'culvers'), '/guest-services/#lost-property'],
        ];

        $categories = [];
        foreach ($categorySpecs as [$group, $slug, $label, $path]) {
            $categories[] = [
                'category_group' => $group,
                'category_label' => $label,
                'category_slug' => $slug,
                'category_url' => function_exists('home_url') ? home_url($path) : $path,
            ];
        }

        return array_merge(self::base('centre_map'), [
            'background_type' => ComponentTypes::BACKGROUND_NONE,
            'centre_map_eyebrow' => '',
            'centre_map_heading' => __('Centre Map', 'culvers'),
            'centre_map_heading_level' => '2',
            'centre_map_body' => '',
            'centre_map_image' => [
                'url' => self::seedAssetUrl(self::CENTRE_MAP_IMAGE_FILE),
                'alt' => __('Stylised floor plan of Culver Square', 'culvers'),
            ],
            'centre_map_panel_position' => 'left',
            'centre_map_filter_button_label' => __('Hide filter', 'culvers'),
            'centre_map_show_zoom_controls' => true,
            'centre_map_categories' => $categories,
            // Pins intentionally empty — Figma references show the centre map as a flat graphic.
            // The ACF repeater is preserved for future use, but seeded with no rows.
            'centre_map_pins' => [],
        ]);
    }

    /**
     * Body is a literal English seed string supplied by callers above; it
     * is not run through `__()` so translators don't have to track every
     * one — translate at the page in WP-Admin if you need an alternate locale.
     *
     * @return array<string, mixed>
     */
    private static function tisRow(string $label, string $body): array
    {
        return [
            'item_label' => $label,
            'item_body' => sprintf('<p>%s</p>', esc_html($body)),
            'item_image_left' => null,
            'item_image_right' => null,
            'item_image_left_tilt' => -8,
            'item_image_right_tilt' => 6,
        ];
    }
}
