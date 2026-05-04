<?php

namespace App\Helpers;

/**
 * Maps CMS-facing colour choices to Tailwind utility classes.
 *
 * Palette keys should stay aligned with tokens in resources/styles/app.css (@theme).
 */
class TailwindColors
{
    public const DEFAULT_BODY_TEXT_TONE = 'text-zinc-100';

    /**
     * @return array<int, string>
     */
    public static function getColorValues(): array
    {
        return [
            '#09090b',
            '#18181b',
            '#27272a',
            '#3f3f46',
            '#71717a',
            '#fafafa',
            '#f4f4f5',
            '#0ea5e9',
            '#0369a1',
        ];
    }

    public static function getPaletteString(): string
    {
        return implode(',', self::getColorValues());
    }

    /**
     * @return array<string, string>
     */
    public static function getColorChoices(string $type = 'text'): array
    {
        $text = [
            'text-zinc-50' => __('Near white', 'culvers'),
            'text-zinc-100' => __('Light gray', 'culvers'),
            'text-zinc-600' => __('Muted', 'culvers'),
            'text-zinc-900' => __('Near black', 'culvers'),
            'text-white' => __('White', 'culvers'),
            'text-black' => __('Black', 'culvers'),
            'text-brand-600' => __('Brand', 'culvers'),
        ];

        $background = [
            'bg-zinc-50' => __('Light surface', 'culvers'),
            'bg-zinc-900' => __('Dark surface', 'culvers'),
            'bg-zinc-950' => __('Darker surface', 'culvers'),
            'bg-white' => __('White', 'culvers'),
            'bg-black' => __('Black', 'culvers'),
            'bg-brand-600' => __('Brand', 'culvers'),
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
        ];
    }

    public static function sanitizeBodyTextTone(?string $value): string
    {
        $allowed = array_keys(self::bodyTextToneChoices());

        return in_array($value, $allowed, true) ? $value : self::DEFAULT_BODY_TEXT_TONE;
    }
}
