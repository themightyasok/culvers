<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Config\ThemeTokens;

/**
 * CMS-facing Tailwind utility class maps. Hex palettes come from {@see ThemeTokens}
 * (parsed from `resources/styles/theme.tokens.css` — the `@theme` source of truth).
 * Content scanning and plugins live in `tailwind.config.js`.
 */
class TailwindColors
{
    public const DEFAULT_BODY_TEXT_TONE = 'text-zinc-100';

    /**
     * @return array<int, string>
     */
    public static function getColorValues(): array
    {
        return ThemeTokens::colorHexPalette();
    }

    public static function getPaletteString(): string
    {
        return ThemeTokens::paletteStringForAcf();
    }

    /**
     * @return array<string, string>
     */
    public static function getColorChoices(string $type = 'text'): array
    {
        $text = [
            'text-zinc-50' => __('Near white', 'culvers'),
            'text-zinc-100' => __('Light gray', 'culvers'),
            'text-zinc-300' => __('Soft gray', 'culvers'),
            'text-zinc-400' => __('Muted gray', 'culvers'),
            'text-zinc-600' => __('Mid gray', 'culvers'),
            'text-zinc-900' => __('Near black', 'culvers'),
            'text-white' => __('White', 'culvers'),
            'text-black' => __('Black', 'culvers'),
            'text-brand-600' => __('Brand', 'culvers'),
            'text-brand-500' => __('Brand bright', 'culvers'),
            'text-text' => __('Semantic — primary text', 'culvers'),
            'text-text-muted' => __('Semantic — muted text', 'culvers'),
        ];

        $background = [
            'bg-zinc-50' => __('Zinc 50', 'culvers'),
            'bg-zinc-900' => __('Zinc 900', 'culvers'),
            'bg-zinc-950' => __('Zinc 950', 'culvers'),
            'bg-canvas' => __('Semantic — canvas', 'culvers'),
            'bg-surface' => __('Semantic — surface', 'culvers'),
            'bg-surface-muted' => __('Semantic — muted surface', 'culvers'),
            'bg-white' => __('White', 'culvers'),
            'bg-black' => __('Black', 'culvers'),
            'bg-brand-600' => __('Brand', 'culvers'),
            'bg-brand-700' => __('Brand deep', 'culvers'),
        ];

        $prefix = $type === 'text' ? 'text-' : 'bg-';
        $source = $type === 'background' ? $background : $text;

        return array_filter(
            $source,
            static fn (string $class) => str_starts_with($class, $prefix),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @return array<string, string>
     */
    public static function bodyTextToneChoices(): array
    {
        return [
            self::DEFAULT_BODY_TEXT_TONE => __('Default (light gray)', 'culvers'),
            'text-white' => __('White', 'culvers'),
            'text-zinc-300' => __('Soft gray', 'culvers'),
            'text-text-muted' => __('Semantic muted', 'culvers'),
            'text-brand-500' => __('Brand bright', 'culvers'),
        ];
    }

    public static function sanitizeBodyTextTone(?string $value): string
    {
        $allowed = array_keys(self::bodyTextToneChoices());

        return in_array($value, $allowed, true) ? $value : self::DEFAULT_BODY_TEXT_TONE;
    }
}
