<?php

declare(strict_types=1);

namespace App\Customizer;

/**
 * Appearance → Customize → Culver Square footer (newsletter strip, four-column block, bottom bar).
 */
final class FooterCustomizer
{
    /** Matches footer seed copy when Customizer has never been published. */
    private const DEFAULT_GETTING_HERE_ADDRESS = "Culver Square Shopping Centre\n7A Culver Square\nColchester, Essex\nCO1 1JQ";

    public const SECTION = 'culvers_footer';

    public const MOD_NEWSLETTER_IMAGE_ID = 'culvers_footer_newsletter_image_id';

    public const MOD_NEWSLETTER_HEADING = 'culvers_footer_newsletter_heading';

    public const MOD_NEWSLETTER_BODY = 'culvers_footer_newsletter_body';

    public const MOD_NEWSLETTER_PLACEHOLDER = 'culvers_footer_newsletter_placeholder';

    public const MOD_NEWSLETTER_FORM_ACTION = 'culvers_footer_newsletter_form_action';

    public const MOD_GETTING_HERE_TITLE = 'culvers_footer_getting_here_title';

    public const MOD_GETTING_HERE_ADDRESS = 'culvers_footer_getting_here_address';

    public const MOD_GETTING_HERE_MAP_URL = 'culvers_footer_getting_here_map_url';

    public const MOD_GETTING_HERE_MAP_LABEL = 'culvers_footer_getting_here_map_label';

    public const MOD_CONTACT_TITLE = 'culvers_footer_contact_title';

    public const MOD_CONTACT_PHONE = 'culvers_footer_contact_phone';

    public const MOD_CONTACT_EMAIL = 'culvers_footer_contact_email';

    public const MOD_COL_ONE_TITLE = 'culvers_footer_column_one_title';

    public const MOD_COL_TWO_TITLE = 'culvers_footer_column_two_title';

    public const MOD_SITE_CREDIT = 'culvers_footer_site_credit';

    public static function register(\WP_Customize_Manager $wp_customize): void
    {
        $wp_customize->add_section(self::SECTION, [
            'title' => __('Culver Square footer', 'culvers'),
            'priority' => 130,
        ]);

        $wp_customize->add_setting(self::MOD_NEWSLETTER_IMAGE_ID, [
            'default' => '',
            'sanitize_callback' => static fn ($v): int => absint($v),
        ]);

        $wp_customize->add_control(new \WP_Customize_Media_Control($wp_customize, self::MOD_NEWSLETTER_IMAGE_ID, [
            'label' => __('Newsletter — background image', 'culvers'),
            'description' => __('Large photo behind the inset rounded newsletter panel.', 'culvers'),
            'section' => self::SECTION,
            'mime_type' => 'image',
        ]));

        $wp_customize->add_setting(self::MOD_NEWSLETTER_HEADING, [
            'default' => __('Get the latest news, offers & events delivered directly to your inbox', 'culvers'),
            'sanitize_callback' => static fn ($v): string => sanitize_text_field((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_NEWSLETTER_HEADING, [
            'label' => __('Newsletter — headline', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_NEWSLETTER_BODY, [
            'default' => '',
            'sanitize_callback' => static fn ($v): string => sanitize_textarea_field((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_NEWSLETTER_BODY, [
            'label' => __('Newsletter — supporting text (optional)', 'culvers'),
            'section' => self::SECTION,
            'type' => 'textarea',
        ]);

        $wp_customize->add_setting(self::MOD_NEWSLETTER_PLACEHOLDER, [
            'default' => __('Email address', 'culvers'),
            'sanitize_callback' => static fn ($v): string => sanitize_text_field((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_NEWSLETTER_PLACEHOLDER, [
            'label' => __('Newsletter — email placeholder', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_NEWSLETTER_FORM_ACTION, [
            'default' => '',
            'sanitize_callback' => static fn ($value): string => $value !== null && $value !== ''
                ? esc_url_raw((string) $value)
                : '',
        ]);
        $wp_customize->add_control(self::MOD_NEWSLETTER_FORM_ACTION, [
            'label' => __('Newsletter — form action URL', 'culvers'),
            'description' => __(
                'Your ESP subscribe endpoint. Leave empty to disable submit until configured.',
                'culvers'
            ),
            'section' => self::SECTION,
            'type' => 'url',
        ]);

        $wp_customize->add_setting(self::MOD_GETTING_HERE_TITLE, [
            'default' => __('Getting here', 'culvers'),
            'sanitize_callback' => static fn ($v): string => sanitize_text_field((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_GETTING_HERE_TITLE, [
            'label' => __('Footer — “Getting here” title', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_GETTING_HERE_ADDRESS, [
            'default' => self::DEFAULT_GETTING_HERE_ADDRESS,
            'sanitize_callback' => static fn ($v): string => sanitize_textarea_field((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_GETTING_HERE_ADDRESS, [
            'label' => __('Footer — address', 'culvers'),
            'section' => self::SECTION,
            'type' => 'textarea',
        ]);

        $wp_customize->add_setting(self::MOD_GETTING_HERE_MAP_URL, [
            'default' => '',
            'sanitize_callback' => static fn ($v): string => esc_url_raw((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_GETTING_HERE_MAP_URL, [
            'label' => __('Footer — map link URL', 'culvers'),
            'section' => self::SECTION,
            'type' => 'url',
        ]);

        $wp_customize->add_setting(self::MOD_GETTING_HERE_MAP_LABEL, [
            'default' => __('View on map', 'culvers'),
            'sanitize_callback' => static fn ($v): string => sanitize_text_field((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_GETTING_HERE_MAP_LABEL, [
            'label' => __('Footer — map link label', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_CONTACT_TITLE, [
            'default' => __('Contact Us', 'culvers'),
            'sanitize_callback' => static fn ($v): string => sanitize_text_field((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_CONTACT_TITLE, [
            'label' => __('Footer — contact column title', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_CONTACT_PHONE, [
            'default' => '01206 578830',
            'sanitize_callback' => static fn ($v): string => sanitize_text_field((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_CONTACT_PHONE, [
            'label' => __('Footer — phone', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_CONTACT_EMAIL, [
            'default' => 'info@culversquare.co.uk',
            'sanitize_callback' => static function ($v): string {
                $raw = (string) $v;
                $email = sanitize_email($raw);

                return $email !== '' ? $email : sanitize_text_field($raw);
            },
        ]);
        $wp_customize->add_control(self::MOD_CONTACT_EMAIL, [
            'label' => __('Footer — email', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_COL_ONE_TITLE, [
            'default' => __("What's Here", 'culvers'),
            'sanitize_callback' => static fn ($v): string => sanitize_text_field((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_COL_ONE_TITLE, [
            'label' => __('Footer — menu column 1 title', 'culvers'),
            'description' => __('Shown above the first footer menu (e.g. What’s Here).', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_COL_TWO_TITLE, [
            'default' => __('Useful Links', 'culvers'),
            'sanitize_callback' => static fn ($v): string => sanitize_text_field((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_COL_TWO_TITLE, [
            'label' => __('Footer — menu column 2 title', 'culvers'),
            'description' => __('Shown above the second footer menu (e.g. Useful Links).', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_SITE_CREDIT, [
            'default' => __('Site by Society Studios', 'culvers'),
            'sanitize_callback' => static fn ($v): string => sanitize_text_field((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_SITE_CREDIT, [
            'label' => __('Footer — site credit (right)', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);
    }

    public static function newsletterHeading(): string
    {
        return (string) get_theme_mod(
            self::MOD_NEWSLETTER_HEADING,
            __('Get the latest news, offers & events delivered directly to your inbox', 'culvers')
        );
    }

    public static function newsletterBody(): string
    {
        return (string) get_theme_mod(self::MOD_NEWSLETTER_BODY, '');
    }

    public static function newsletterPlaceholder(): string
    {
        return (string) get_theme_mod(self::MOD_NEWSLETTER_PLACEHOLDER, __('Email address', 'culvers'));
    }

    public static function newsletterFormAction(): ?string
    {
        $url = (string) get_theme_mod(self::MOD_NEWSLETTER_FORM_ACTION, '');
        if ($url === '') {
            return null;
        }

        return $url;
    }

    public static function gettingHereTitle(): string
    {
        return (string) get_theme_mod(self::MOD_GETTING_HERE_TITLE, __('Getting here', 'culvers'));
    }

    public static function gettingHereAddress(): string
    {
        return (string) get_theme_mod(self::MOD_GETTING_HERE_ADDRESS, self::DEFAULT_GETTING_HERE_ADDRESS);
    }

    public static function gettingHereMapUrl(): string
    {
        return (string) get_theme_mod(
            self::MOD_GETTING_HERE_MAP_URL,
            'https://www.google.com/maps/search/?api=1&query=Culver+Square+Colchester+CO1+1JQ'
        );
    }

    public static function gettingHereMapLabel(): string
    {
        return (string) get_theme_mod(self::MOD_GETTING_HERE_MAP_LABEL, __('View on map', 'culvers'));
    }

    public static function contactTitle(): string
    {
        return (string) get_theme_mod(self::MOD_CONTACT_TITLE, __('Contact Us', 'culvers'));
    }

    public static function contactPhone(): string
    {
        return (string) get_theme_mod(self::MOD_CONTACT_PHONE, '01206 578830');
    }

    public static function contactEmail(): string
    {
        return (string) get_theme_mod(self::MOD_CONTACT_EMAIL, 'info@culversquare.co.uk');
    }

    public static function columnOneTitle(): string
    {
        return (string) get_theme_mod(self::MOD_COL_ONE_TITLE, __("What's Here", 'culvers'));
    }

    public static function columnTwoTitle(): string
    {
        return (string) get_theme_mod(self::MOD_COL_TWO_TITLE, __('Useful Links', 'culvers'));
    }

    public static function siteCredit(): string
    {
        return (string) get_theme_mod(self::MOD_SITE_CREDIT, __('Site by Society Studios', 'culvers'));
    }
}
