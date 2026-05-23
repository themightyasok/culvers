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
 * **Rule:** default flex chrome is **transparent** so Figma page diamonds
 * ({@see resources/styles/patterns/site-page-pattern.css} viewport layer) show on the
 * off-white `#app` surface. Layouts whose Blade root already paints the full
 * band (hero, travel calculator, split highlight card, …) stay **`none`**.
 * Only layouts that need a true white band (e.g. horizontal scroller with
 * `text-white` header copy) opt in via filter or {@see whiteBackgroundPayload()}.
 *
 * Adjust per-context values with the **`culvers_component_layout_chrome`** filter.
 */
final class ComponentLayoutChrome
{
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
     * Solid white band — for layouts that need an explicit white surface
     * (e.g. horizontal scroller with `text-white` header copy).
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
        $background = self::neutralBackgroundPayload();

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
