<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Style presets for the horizontal_scroller layout — replaces the former
 * Typography tab (colours, sizes, weights, and common layout defaults).
 */
final class HorizontalScrollerPreset
{
    public const PRESET_DEFAULT = 'default';

    public const PRESET_HOMEPAGE_BRANDS = 'homepage_brands';

    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public static function apply(array $component): array
    {
        $preset = self::resolvePreset($component);
        $styling = self::valuesForPreset($preset);
        $explicit = isset($component['scroller_preset'])
            && is_string($component['scroller_preset'])
            && $component['scroller_preset'] !== '';

        if ($explicit) {
            return array_merge($component, $styling);
        }

        foreach ($styling as $key => $value) {
            if (! array_key_exists($key, $component) || $component[$key] === '' || $component[$key] === null) {
                $component[$key] = $value;
            }
        }

        return $component;
    }

    /**
     * @param array<string, mixed> $component
     */
    private static function resolvePreset(array $component): string
    {
        $raw = $component['scroller_preset'] ?? '';
        if (is_string($raw) && $raw !== '') {
            return $raw === self::PRESET_HOMEPAGE_BRANDS
                ? self::PRESET_HOMEPAGE_BRANDS
                : self::PRESET_DEFAULT;
        }

        return self::inferLegacyPreset($component);
    }

    /**
     * @param array<string, mixed> $component
     */
    private static function inferLegacyPreset(array $component): string
    {
        if (
            ($component['scroller_header_text_color'] ?? '') === 'text-faded-olive'
            && ($component['scroller_header_text_alignment'] ?? '') === 'center'
        ) {
            return self::PRESET_HOMEPAGE_BRANDS;
        }

        return self::PRESET_DEFAULT;
    }

    /**
     * @return array<string, mixed>
     */
    private static function valuesForPreset(string $preset): array
    {
        $typography = match ($preset) {
            self::PRESET_HOMEPAGE_BRANDS => [
                'scroller_header_text_color' => 'text-faded-olive',
                'scroller_header_text_size' => 'text-7xl',
                'scroller_header_text_weight' => 'font-normal',
                'scroller_subheading_text_color' => 'text-deep-moss',
                'scroller_subheading_text_size' => 'text-xl',
                'scroller_subheading_text_weight' => 'font-medium',
                'scroller_body_text_color' => 'text-deep-moss',
                'scroller_body_text_size' => 'text-xl',
                'scroller_body_text_weight' => 'font-light',
                'scroller_item_kicker_size' => 'text-xs',
                'scroller_item_kicker_weight' => 'font-semibold',
                'scroller_item_heading_size' => 'text-2xl',
                'scroller_item_heading_weight' => 'font-normal',
                'scroller_item_body_size' => 'text-lg',
                'scroller_item_body_weight' => 'font-normal',
                'scroller_header_text_alignment' => 'center',
                'scroller_header_alignment' => 'middle',
                'scroller_intro_flush' => 1,
                'scroller_item_spacing' => 80,
                'scroller_button_variant' => 'primary',
                'scroller_button_size' => 'md',
                'scroller_button_show_arrow' => 1,
            ],
            default => [
                'scroller_header_text_color' => 'text-white',
                'scroller_header_text_size' => 'text-8xl',
                'scroller_header_text_weight' => 'font-normal',
                'scroller_subheading_text_color' => 'text-white',
                'scroller_subheading_text_size' => 'text-xl',
                'scroller_subheading_text_weight' => 'font-medium',
                'scroller_body_text_color' => 'text-white',
                'scroller_body_text_size' => 'text-xl',
                'scroller_body_text_weight' => 'font-medium',
                'scroller_item_kicker_size' => 'text-xs',
                'scroller_item_kicker_weight' => 'font-semibold',
                'scroller_item_heading_size' => 'text-2xl',
                'scroller_item_heading_weight' => 'font-normal',
                'scroller_item_body_size' => 'text-lg',
                'scroller_item_body_weight' => 'font-normal',
                'scroller_header_text_alignment' => 'left',
                'scroller_header_alignment' => 'top',
                'scroller_intro_flush' => 0,
                'scroller_item_spacing' => 240,
                'scroller_button_variant' => 'primary',
                'scroller_button_size' => 'md',
                'scroller_button_show_arrow' => 1,
            ],
        };

        return array_merge(['scroller_preset' => $preset], $typography);
    }
}
