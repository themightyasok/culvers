<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Constants\ComponentTypes;

/**
 * Authoritative flexible-row chrome: **`component_width`** and all **`background_*`**
 * values are merged **after** {@see Sanitizer::component()} so saved meta cannot
 * change the front end. The ACF **Background** UI was removed — surfaces are fixed
 * in code ({@see \App\ComponentRegistry} Main tab).
 *
 * **Rule:** almost every layout gets a **white** (#ffffff) full-bleed flex wrapper
 * ({@see resources/views/partials/flexible-components.blade.php}). Layouts whose
 * Blade root already paints the full band (cream, deep moss, light green, etc.)
 * stay **`background_type: none`** so we do not stack a second surface or double
 * vertical padding.
 *
 * Adjust per-context values with the **`culvers_component_layout_chrome`** filter.
 */
final class ComponentLayoutChrome
{
    /**
     * Layouts that own their outer surface in the Blade template (`bg-lighter-cream`,
     * `bg-deep-moss`, `bg-light-green` on the section/card). Flex chrome must stay "none".
     *
     * @var list<string>
     */
    private const LAYOUTS_OWN_OUTER_SURFACE = [
        'image_hero',
        'hero_slider',
        'centre_map',
        'shop_intro_block',
        'shop_store_details',
        'shop_related_shops',
        'info_block',
        'text_image_slider',
        'leasing_agent_grid',
        'contact',
        'faq',
        'travel_calculator',
    ];

    /**
     * Neutral background payload — clears image/video/gradient/overlay inputs.
     *
     * @return array<string, mixed>
     */
    public static function neutralBackgroundPayload(): array
    {
        return [
            'background_type' => ComponentTypes::BACKGROUND_NONE,
            'background_color' => '',
            'background_gradient_color_from' => '',
            'background_gradient_color_to' => '',
            'background_gradient_angle' => '90',
            'background_image' => null,
            'background_image_color' => '',
            'background_parallax' => 1,
            'background_video' => null,
            'background_video_youtube_url' => '',
            'background_overlay' => '',
            'background_overlay_opacity' => 30,
        ];
    }

    /**
     * Solid white band — default flex wrapper surface for editorial blocks that do
     * not set their own outer `bg-*` (content_section, section_header, scroller, …).
     *
     * @return array<string, mixed>
     */
    public static function whiteBackgroundPayload(): array
    {
        $payload = self::neutralBackgroundPayload();
        $payload['background_type'] = ComponentTypes::BACKGROUND_COLOR;
        $payload['background_color'] = '#ffffff';

        return $payload;
    }

    /**
     * Default chrome for a layout key before filters run.
     *
     * @return array<string, mixed>
     */
    public static function baseChromeForLayout(string $layout): array
    {
        $width = Grid::validateComponentWidth(Grid::getDefaultComponentWidth($layout));
        $background = in_array($layout, self::LAYOUTS_OWN_OUTER_SURFACE, true)
            ? self::neutralBackgroundPayload()
            : self::whiteBackgroundPayload();

        return array_merge(
            [
                'component_width' => $width,
            ],
            $background
        );
    }

    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public static function apply(array $component, string $layout): array
    {
        $chrome = self::baseChromeForLayout($layout);
        $filtered = apply_filters('culvers_component_layout_chrome', $chrome, $layout, $component);
        if (! is_array($filtered)) {
            $filtered = $chrome;
        }

        /** @var array<string, mixed> $filtered */
        return array_merge($component, $filtered);
    }
}
