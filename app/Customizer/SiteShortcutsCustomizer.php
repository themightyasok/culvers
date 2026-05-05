<?php

declare(strict_types=1);

namespace App\Customizer;

/**
 * Appearance → Customize → Culver Square shortcuts (header utility links).
 */
final class SiteShortcutsCustomizer
{
    public const SECTION = 'culvers_site_shortcuts';

    public const MOD_CENTRE_MAP_URL = 'culvers_centre_map_url';

    public const MOD_GETTING_HERE_URL = 'culvers_getting_here_url';

    public const MOD_GUEST_SERVICES_URL = 'culvers_guest_services_url';

    public static function register(\WP_Customize_Manager $wp_customize): void
    {
        $wp_customize->add_section(self::SECTION, [
            'title' => __('Culver Square shortcuts', 'culvers'),
            'description' => __('Used by the header (“Centre Map”, “Getting Here”).', 'culvers'),
            'priority' => 125,
        ]);

        $wp_customize->add_setting(self::MOD_CENTRE_MAP_URL, [
            'default' => '',
            'sanitize_callback' => static fn ($v): string => esc_url_raw((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_CENTRE_MAP_URL, [
            'label' => __('Centre map page URL', 'culvers'),
            'section' => self::SECTION,
            'type' => 'url',
        ]);

        $wp_customize->add_setting(self::MOD_GETTING_HERE_URL, [
            'default' => '',
            'sanitize_callback' => static fn ($v): string => esc_url_raw((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_GETTING_HERE_URL, [
            'label' => __('Getting here page URL', 'culvers'),
            'section' => self::SECTION,
            'type' => 'url',
        ]);

        $wp_customize->add_setting(self::MOD_GUEST_SERVICES_URL, [
            'default' => '',
            'sanitize_callback' => static fn ($v): string => esc_url_raw((string) $v),
        ]);
        $wp_customize->add_control(self::MOD_GUEST_SERVICES_URL, [
            'label' => __('Guest services page URL', 'culvers'),
            'description' => __('Optional URL for Guest services CTAs and menus.', 'culvers'),
            'section' => self::SECTION,
            'type' => 'url',
        ]);
    }
}
