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

    /**
     * Optional sub-string of {@see self::MOD_NEWSLETTER_HEADING} that should render in glowleaf
     * (the rest renders white). Empty value = render the whole heading in white. Matched by the
     * first occurrence of the accent text inside the full heading.
     */
    public const MOD_NEWSLETTER_HEADING_ACCENT = 'culvers_footer_newsletter_heading_accent';

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

    public const MOD_INSTAGRAM_URL = 'culvers_instagram_url';

    public const MOD_FACEBOOK_URL = 'culvers_facebook_url';

    public const MOD_CONTACT_FORM_RECIPIENT = 'culvers_contact_form_recipient';

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
            'sanitize_callback' => Sanitize::text(...),
        ]);
        $wp_customize->add_control(self::MOD_NEWSLETTER_HEADING, [
            'label' => __('Newsletter — headline', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_NEWSLETTER_HEADING_ACCENT, [
            'default' => __('Get the latest news, offers & events', 'culvers'),
            'sanitize_callback' => Sanitize::text(...),
        ]);
        $wp_customize->add_control(self::MOD_NEWSLETTER_HEADING_ACCENT, [
            'label' => __('Newsletter — accent words (glowleaf)', 'culvers'),
            'description' => __(
                'Words inside the headline above that should render in glowleaf yellow. Leave blank to render the whole headline in white.',
                'culvers'
            ),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_NEWSLETTER_BODY, [
            'default' => '',
            'sanitize_callback' => Sanitize::textarea(...),
        ]);
        $wp_customize->add_control(self::MOD_NEWSLETTER_BODY, [
            'label' => __('Newsletter — supporting text (optional)', 'culvers'),
            'section' => self::SECTION,
            'type' => 'textarea',
        ]);

        $wp_customize->add_setting(self::MOD_NEWSLETTER_PLACEHOLDER, [
            'default' => __('Email address', 'culvers'),
            'sanitize_callback' => Sanitize::text(...),
        ]);
        $wp_customize->add_control(self::MOD_NEWSLETTER_PLACEHOLDER, [
            'label' => __('Newsletter — email placeholder', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_NEWSLETTER_FORM_ACTION, [
            'default' => '',
            'sanitize_callback' => Sanitize::url(...),
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
            'sanitize_callback' => Sanitize::text(...),
        ]);
        $wp_customize->add_control(self::MOD_GETTING_HERE_TITLE, [
            'label' => __('Footer — “Getting here” title', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_GETTING_HERE_ADDRESS, [
            'default' => self::DEFAULT_GETTING_HERE_ADDRESS,
            'sanitize_callback' => Sanitize::textarea(...),
        ]);
        $wp_customize->add_control(self::MOD_GETTING_HERE_ADDRESS, [
            'label' => __('Footer — address', 'culvers'),
            'section' => self::SECTION,
            'type' => 'textarea',
        ]);

        $wp_customize->add_setting(self::MOD_GETTING_HERE_MAP_URL, [
            'default' => '',
            'sanitize_callback' => Sanitize::url(...),
        ]);
        $wp_customize->add_control(self::MOD_GETTING_HERE_MAP_URL, [
            'label' => __('Footer — map link URL', 'culvers'),
            'section' => self::SECTION,
            'type' => 'url',
        ]);

        $wp_customize->add_setting(self::MOD_GETTING_HERE_MAP_LABEL, [
            'default' => __('View on map', 'culvers'),
            'sanitize_callback' => Sanitize::text(...),
        ]);
        $wp_customize->add_control(self::MOD_GETTING_HERE_MAP_LABEL, [
            'label' => __('Footer — map link label', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_CONTACT_TITLE, [
            'default' => __('Contact Us', 'culvers'),
            'sanitize_callback' => Sanitize::text(...),
        ]);
        $wp_customize->add_control(self::MOD_CONTACT_TITLE, [
            'label' => __('Footer — contact column title', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_CONTACT_PHONE, [
            'default' => '01206 578830',
            'sanitize_callback' => Sanitize::text(...),
        ]);
        $wp_customize->add_control(self::MOD_CONTACT_PHONE, [
            'label' => __('Footer — phone', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_CONTACT_EMAIL, [
            'default' => 'info@culversquare.co.uk',
            'sanitize_callback' => static function (mixed $v): string {
                $raw = Sanitize::toString($v);
                $email = sanitize_email($raw);

                return $email !== '' ? $email : sanitize_text_field($raw);
            },
        ]);
        $wp_customize->add_control(self::MOD_CONTACT_EMAIL, [
            'label' => __('Footer — email', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_INSTAGRAM_URL, [
            'default' => '',
            'sanitize_callback' => Sanitize::url(...),
        ]);
        $wp_customize->add_control(self::MOD_INSTAGRAM_URL, [
            'label' => __('Social — Instagram URL', 'culvers'),
            'description' => __('Used by both the footer social icons and the Contact component.', 'culvers'),
            'section' => self::SECTION,
            'type' => 'url',
        ]);

        $wp_customize->add_setting(self::MOD_FACEBOOK_URL, [
            'default' => '',
            'sanitize_callback' => Sanitize::url(...),
        ]);
        $wp_customize->add_control(self::MOD_FACEBOOK_URL, [
            'label' => __('Social — Facebook URL', 'culvers'),
            'section' => self::SECTION,
            'type' => 'url',
        ]);

        $wp_customize->add_setting(self::MOD_CONTACT_FORM_RECIPIENT, [
            'default' => '',
            'sanitize_callback' => static function (mixed $v): string {
                $email = sanitize_email(Sanitize::toString($v));

                return $email !== '' ? $email : '';
            },
        ]);
        $wp_customize->add_control(self::MOD_CONTACT_FORM_RECIPIENT, [
            'label' => __('Contact form — recipient email', 'culvers'),
            'description' => __(
                'Where the Contact component sends form submissions. Falls back to the WordPress admin email.',
                'culvers'
            ),
            'section' => self::SECTION,
            'type' => 'email',
        ]);

        $wp_customize->add_setting(self::MOD_COL_ONE_TITLE, [
            'default' => __("What's Here", 'culvers'),
            'sanitize_callback' => Sanitize::text(...),
        ]);
        $wp_customize->add_control(self::MOD_COL_ONE_TITLE, [
            'label' => __('Footer — menu column 1 title', 'culvers'),
            'description' => __('Shown above the first footer menu (e.g. What’s Here).', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_COL_TWO_TITLE, [
            'default' => __('Useful Links', 'culvers'),
            'sanitize_callback' => Sanitize::text(...),
        ]);
        $wp_customize->add_control(self::MOD_COL_TWO_TITLE, [
            'label' => __('Footer — menu column 2 title', 'culvers'),
            'description' => __('Shown above the second footer menu (e.g. Useful Links).', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_SITE_CREDIT, [
            'default' => __('Site by Society Studios', 'culvers'),
            'sanitize_callback' => Sanitize::text(...),
        ]);
        $wp_customize->add_control(self::MOD_SITE_CREDIT, [
            'label' => __('Footer — site credit (right)', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);
    }

    public static function newsletterHeading(): string
    {
        return Sanitize::toString(get_theme_mod(
            self::MOD_NEWSLETTER_HEADING,
            __('Get the latest news, offers & events delivered directly to your inbox', 'culvers')
        ));
    }

    public static function newsletterHeadingAccent(): string
    {
        return Sanitize::toString(get_theme_mod(
            self::MOD_NEWSLETTER_HEADING_ACCENT,
            __('Get the latest news, offers & events', 'culvers')
        ));
    }

    /**
     * Splits the heading into glowleaf accent + white remainder using {@see self::newsletterHeadingAccent()}
     * as a literal sub-string match. Single source of truth so the Blade view doesn’t reimplement it.
     *
     * @return array{accent: string, rest: string}
     */
    public static function newsletterHeadingParts(): array
    {
        $heading = self::newsletterHeading();
        $accent = trim(self::newsletterHeadingAccent());

        if ($accent === '' || $heading === '') {
            return ['accent' => '', 'rest' => $heading];
        }

        $position = stripos($heading, $accent);
        if ($position === false) {
            return ['accent' => '', 'rest' => $heading];
        }

        return [
            'accent' => substr($heading, $position, strlen($accent)),
            'rest' => substr($heading, $position + strlen($accent)),
        ];
    }

    public static function newsletterBody(): string
    {
        return Sanitize::toString(get_theme_mod(self::MOD_NEWSLETTER_BODY, ''));
    }

    public static function newsletterPlaceholder(): string
    {
        return Sanitize::toString(get_theme_mod(self::MOD_NEWSLETTER_PLACEHOLDER, __('Email address', 'culvers')));
    }

    public static function newsletterFormAction(): ?string
    {
        $url = Sanitize::toString(get_theme_mod(self::MOD_NEWSLETTER_FORM_ACTION, ''));
        if ($url === '') {
            return null;
        }

        return $url;
    }

    public static function gettingHereTitle(): string
    {
        return Sanitize::toString(get_theme_mod(self::MOD_GETTING_HERE_TITLE, __('Getting here', 'culvers')));
    }

    public static function gettingHereAddress(): string
    {
        return Sanitize::toString(get_theme_mod(self::MOD_GETTING_HERE_ADDRESS, self::DEFAULT_GETTING_HERE_ADDRESS));
    }

    public static function gettingHereMapUrl(): string
    {
        return Sanitize::toString(get_theme_mod(
            self::MOD_GETTING_HERE_MAP_URL,
            'https://www.google.com/maps/search/?api=1&query=Culver+Square+Colchester+CO1+1JQ'
        ));
    }

    public static function gettingHereMapLabel(): string
    {
        return Sanitize::toString(get_theme_mod(self::MOD_GETTING_HERE_MAP_LABEL, __('View on map', 'culvers')));
    }

    public static function contactTitle(): string
    {
        return Sanitize::toString(get_theme_mod(self::MOD_CONTACT_TITLE, __('Contact Us', 'culvers')));
    }

    public static function contactPhone(): string
    {
        return Sanitize::toString(get_theme_mod(self::MOD_CONTACT_PHONE, '01206 578830'));
    }

    public static function contactEmail(): string
    {
        return Sanitize::toString(get_theme_mod(self::MOD_CONTACT_EMAIL, 'info@culversquare.co.uk'));
    }

    public static function instagramUrl(): string
    {
        $url = Sanitize::toString(get_theme_mod(self::MOD_INSTAGRAM_URL, ''));

        return ($url === '' || $url === '#') ? '' : $url;
    }

    public static function facebookUrl(): string
    {
        $url = Sanitize::toString(get_theme_mod(self::MOD_FACEBOOK_URL, ''));

        return ($url === '' || $url === '#') ? '' : $url;
    }

    /**
     * Recipient email for the contact form. Falls back to the WordPress admin email so the form
     * never silently no-ops; admins can override via Customizer.
     */
    public static function contactFormRecipient(): string
    {
        $configured = sanitize_email(Sanitize::toString(get_theme_mod(self::MOD_CONTACT_FORM_RECIPIENT, '')));
        if ($configured !== '') {
            return $configured;
        }

        $admin = sanitize_email(Sanitize::toString(get_option('admin_email', '')));

        return $admin !== '' ? $admin : '';
    }

    public static function columnOneTitle(): string
    {
        return Sanitize::toString(get_theme_mod(self::MOD_COL_ONE_TITLE, __("What's Here", 'culvers')));
    }

    public static function columnTwoTitle(): string
    {
        return Sanitize::toString(get_theme_mod(self::MOD_COL_TWO_TITLE, __('Useful Links', 'culvers')));
    }

    public static function siteCredit(): string
    {
        return Sanitize::toString(get_theme_mod(self::MOD_SITE_CREDIT, __('Site by Society Studios', 'culvers')));
    }
}
