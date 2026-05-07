<?php

declare(strict_types=1);

namespace App\Customizer;

/**
 * Appearance → Customize → Google Maps. Holds the API key + canonical
 * destination used by the Travel Calculator component (Distance Matrix +
 * Maps Embed). Lock the key down with HTTP-referrer + API restrictions in
 * Google Cloud Console — see `docs/TRAVEL-CALCULATOR.md`.
 */
final class GoogleMapsCustomizer
{
    public const SECTION = 'culvers_google_maps';

    public const MOD_API_KEY = 'culvers_google_maps_api_key';

    public const MOD_DESTINATION_ADDRESS = 'culvers_google_maps_destination_address';

    public const MOD_DESTINATION_PLACE_ID = 'culvers_google_maps_destination_place_id';

    public const MOD_DESTINATION_LABEL = 'culvers_google_maps_destination_label';

    private const DEFAULT_DESTINATION_ADDRESS = 'Culver Square, Colchester CO1 1JG, United Kingdom';

    private const DEFAULT_DESTINATION_LABEL = 'Culver Square';

    public static function register(\WP_Customize_Manager $wp_customize): void
    {
        $wp_customize->add_section(self::SECTION, [
            'title' => __('Google Maps', 'culvers'),
            'description' => __(
                'API key and destination used by the Travel Calculator component (Distance Matrix + Maps Embed). ' .
                'See docs/TRAVEL-CALCULATOR.md for Google Cloud setup and key restriction guidance.',
                'culvers'
            ),
            'priority' => 130,
        ]);

        $wp_customize->add_setting(self::MOD_API_KEY, [
            'default' => '',
            'sanitize_callback' => Sanitize::text(...),
            'transport' => 'refresh',
        ]);
        $wp_customize->add_control(self::MOD_API_KEY, [
            'label' => __('Google Maps API key', 'culvers'),
            'description' => __(
                'Restrict in Google Cloud to: APIs = Distance Matrix + Maps Embed; HTTP referrers = your live + staging hosts.',
                'culvers'
            ),
            'section' => self::SECTION,
            'type' => 'text',
            'input_attrs' => [
                'autocomplete' => 'off',
                'spellcheck' => 'false',
            ],
        ]);

        $wp_customize->add_setting(self::MOD_DESTINATION_ADDRESS, [
            'default' => self::DEFAULT_DESTINATION_ADDRESS,
            'sanitize_callback' => Sanitize::text(...),
        ]);
        $wp_customize->add_control(self::MOD_DESTINATION_ADDRESS, [
            'label' => __('Destination address', 'culvers'),
            'description' => __(
                'Plain-text destination (used as fallback if no place ID is set).',
                'culvers'
            ),
            'section' => self::SECTION,
            'type' => 'text',
        ]);

        $wp_customize->add_setting(self::MOD_DESTINATION_PLACE_ID, [
            'default' => '',
            'sanitize_callback' => Sanitize::text(...),
        ]);
        $wp_customize->add_control(self::MOD_DESTINATION_PLACE_ID, [
            'label' => __('Destination Google Place ID', 'culvers'),
            'description' => __(
                'Optional but preferred — gives Distance Matrix an exact target. ' .
                'Find it via the Place ID Finder in Google Maps Platform.',
                'culvers'
            ),
            'section' => self::SECTION,
            'type' => 'text',
            'input_attrs' => ['placeholder' => 'ChIJ...'],
        ]);

        $wp_customize->add_setting(self::MOD_DESTINATION_LABEL, [
            'default' => self::DEFAULT_DESTINATION_LABEL,
            'sanitize_callback' => Sanitize::text(...),
        ]);
        $wp_customize->add_control(self::MOD_DESTINATION_LABEL, [
            'label' => __('Destination short label', 'culvers'),
            'description' => __('Short human-friendly name (used in the result strip).', 'culvers'),
            'section' => self::SECTION,
            'type' => 'text',
        ]);
    }

    public static function apiKey(): string
    {
        $value = get_theme_mod(self::MOD_API_KEY, '');

        return is_string($value) ? trim($value) : '';
    }

    public static function destinationAddress(): string
    {
        $value = get_theme_mod(self::MOD_DESTINATION_ADDRESS, self::DEFAULT_DESTINATION_ADDRESS);

        return is_string($value) && trim($value) !== '' ? trim($value) : self::DEFAULT_DESTINATION_ADDRESS;
    }

    public static function destinationPlaceId(): string
    {
        $value = get_theme_mod(self::MOD_DESTINATION_PLACE_ID, '');

        return is_string($value) ? trim($value) : '';
    }

    public static function destinationLabel(): string
    {
        $value = get_theme_mod(self::MOD_DESTINATION_LABEL, self::DEFAULT_DESTINATION_LABEL);

        return is_string($value) && trim($value) !== '' ? trim($value) : self::DEFAULT_DESTINATION_LABEL;
    }
}
