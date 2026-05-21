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

    /** Figma `51:5005` — intro body copy width (natural wrap, not manual `<p>` breaks). */
    public const HOMEPAGE_BRANDS_INTRO_BODY_MAX_W_PX = 588;

    /** Figma `51:5007` uses `gap-[133px]` on a ~1494px frame; tightened in code for the live strip. */
    public const HOMEPAGE_BRANDS_ITEM_GAP_PX = 48;

    /** Generic horizontal scroller rows (mixed cards, not the homepage logo strip). */
    public const DEFAULT_ITEM_GAP_PX = 80;

    public static function itemGapPx(string $preset): int
    {
        return match ($preset) {
            self::PRESET_HOMEPAGE_BRANDS => self::HOMEPAGE_BRANDS_ITEM_GAP_PX,
            default => self::DEFAULT_ITEM_GAP_PX,
        };
    }

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
            if ($raw === self::PRESET_HOMEPAGE_BRANDS) {
                return self::PRESET_HOMEPAGE_BRANDS;
            }

            // Rows migrated when the preset field was added kept ACF's "default"
            // value but no longer had per-field colours — that applied 240px logo
            // gaps and white-on-white header copy on the homepage brand strip.
            if ($raw === self::PRESET_DEFAULT && self::looksLikeHomepageBrandStrip($component)) {
                return self::PRESET_HOMEPAGE_BRANDS;
            }

            return self::PRESET_DEFAULT;
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
     * Detect homepage logo strips saved before editors chose a preset (field
     * defaulted to "default" while legacy colour fields were removed from ACF).
     *
     * @param array<string, mixed> $component
     */
    private static function looksLikeHomepageBrandStrip(array $component): bool
    {
        if (self::inferLegacyPreset($component) === self::PRESET_HOMEPAGE_BRANDS) {
            return true;
        }

        $color = (string) ($component['scroller_header_text_color'] ?? '');
        $align = (string) ($component['scroller_header_text_alignment'] ?? '');
        if ($color !== '' || $align !== '') {
            return false;
        }

        $items = $component['scroller_items'] ?? [];
        if (! is_array($items) || count($items) < 3) {
            return false;
        }

        foreach ($items as $item) {
            if (! is_array($item)) {
                return false;
            }

            $type = (string) ($item['item_type'] ?? 'image');
            if ($type !== '' && $type !== 'image') {
                return false;
            }
        }

        return true;
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
                'scroller_button_variant' => 'primary',
                'scroller_button_size' => 'md',
                'scroller_button_show_arrow' => 0,
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
                'scroller_button_variant' => 'primary',
                'scroller_button_size' => 'md',
                'scroller_button_show_arrow' => 0,
            ],
        };

        return array_merge(['scroller_preset' => $preset], $typography);
    }
}
