<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Config\ThemeTokens;

/**
 * CMS-facing Tailwind utility class maps driven by {@see ThemeTokens} (`theme.tokens.css` `@theme`).
 * Content scanning and plugins live in `tailwind.config.js`.
 */
class TailwindColors
{
    public const DEFAULT_BODY_TEXT_TONE = 'text-zinc-100';

    /** Prose on light bands (e.g. info grid intro): editors can override via Body text colour. */
    public const DEFAULT_LIGHT_BAND_BODY_TEXT_TONE = 'text-deep-moss';

    /**
     * Body / prose tone presets — subset of slugs that must exist in `@theme`.
     *
     * @var array<int, string>
     */
    private const BODY_TEXT_SLUGS = ['zinc-100', 'white', 'zinc-300', 'text-muted', 'brand-500', 'deep-moss'];

    /**
     * Fallback tone when the layout omits `body_text_tone` (ACF default + sanitized payloads).
     */
    public static function defaultBodyTextToneForLayout(string $layout): string
    {
        return match ($layout) {
            'info_block' => self::DEFAULT_LIGHT_BAND_BODY_TEXT_TONE,
            default => self::DEFAULT_BODY_TEXT_TONE,
        };
    }

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
     * Text / background utility choices derived from `--color-*` in theme.tokens.css.
     *
     * @return array<string, string>
     */
    public static function getColorChoices(string $type = 'text'): array
    {
        $prefix = $type === 'text' ? 'text-' : 'bg-';
        $slugHex = ThemeTokens::colorSlugHexMap();
        $choices = [];
        foreach ($slugHex as $slug => $_hex) {
            $choices[$prefix . $slug] = self::colorChoiceLabel($slug);
        }

        return $choices;
    }

    /**
     * @return array<string, string>
     */
    public static function bodyTextToneChoices(): array
    {
        $slugHex = ThemeTokens::colorSlugHexMap();
        $choices = [];
        foreach (self::BODY_TEXT_SLUGS as $slug) {
            if (isset($slugHex[$slug])) {
                $choices['text-' . $slug] = self::bodyToneLabel($slug);
            }
        }

        return $choices;
    }

    public static function sanitizeBodyTextTone(?string $value): string
    {
        $choices = self::bodyTextToneChoices();
        $keys = array_keys($choices);
        if ($keys !== [] && in_array($value, $keys, true)) {
            return $value;
        }
        if (isset($choices[self::DEFAULT_BODY_TEXT_TONE])) {
            return self::DEFAULT_BODY_TEXT_TONE;
        }

        return $keys[0] ?? self::DEFAULT_BODY_TEXT_TONE;
    }

    private static function colorChoiceLabel(string $slug): string
    {
        return match ($slug) {
            'canvas' => __('Semantic — canvas', 'culvers'),
            'surface' => __('Semantic — surface', 'culvers'),
            'surface-muted' => __('Semantic — muted surface', 'culvers'),
            'border-subtle' => __('Semantic — border subtle', 'culvers'),
            'text' => __('Semantic — primary text', 'culvers'),
            'text-muted' => __('Semantic — muted text', 'culvers'),
            'glowleaf' => __('Glowleaf', 'culvers'),
            'deep-moss' => __('Deep moss', 'culvers'),
            'faded-olive' => __('Faded olive', 'culvers'),
            'dustleaf' => __('Dustleaf', 'culvers'),
            'light-brown' => __('Light brown', 'culvers'),
            'light-cream' => __('Light cream', 'culvers'),
            'off-white' => __('Off white', 'culvers'),
            'lighter-cream' => __('Lighter cream', 'culvers'),
            'brand-500' => __('Brand bright', 'culvers'),
            'brand-600' => __('Brand', 'culvers'),
            'brand-700' => __('Brand deep', 'culvers'),
            'white' => __('White', 'culvers'),
            'black' => __('Black', 'culvers'),
            default => ucwords(str_replace('-', ' ', $slug)),
        };
    }

    private static function bodyToneLabel(string $slug): string
    {
        return match ($slug) {
            'zinc-100' => __('Default (light gray)', 'culvers'),
            'white' => __('White', 'culvers'),
            'zinc-300' => __('Soft gray', 'culvers'),
            'text-muted' => __('Semantic muted', 'culvers'),
            'brand-500' => __('Brand bright', 'culvers'),
            'deep-moss' => __('Deep moss', 'culvers'),
            default => ucwords(str_replace('-', ' ', $slug)),
        };
    }
}
