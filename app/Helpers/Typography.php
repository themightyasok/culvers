<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Tailwind typography classes for CMS-driven components (font family + size + weight).
 *
 * Sizes are stock Tailwind utilities (`text-xs..text-9xl`) tuned to Figma in
 * `resources/styles/theme.tokens.css`. The buckets below split the same ladder by
 * editorial role (body copy vs display heading) so dropdowns stay scoped.
 *
 * @see resources/styles/theme.tokens.css
 * @see docs/TYPOGRAPHY-SCALE.md
 */
final class Typography
{
    /** @var array<string, string> */
    private static array $fontFamilyMap = [
        'heading' => 'font-heading',
        'body' => 'font-sans',
        'quote' => 'font-sans',
    ];

    /**
     * @param  string  $elementType  heading|body|quote
     */
    public static function classes(string $elementType, ?string $size, ?string $weight): string
    {
        $fontFamily = self::$fontFamilyMap[$elementType] ?? 'font-sans';
        $size = self::sanitizeSize((string) ($size ?? ''));
        $weight = self::sanitizeWeight((string) ($weight ?? ''));

        return trim("{$fontFamily} {$size} {$weight}");
    }

    public static function fontClass(string $elementType): string
    {
        return self::$fontFamilyMap[$elementType] ?? 'font-sans';
    }

    /**
     * Display tier — Canela-style headings 32px → 96px.
     *
     * @return array<string, string>
     */
    public static function getHeaderSizeChoices(): array
    {
        return [
            'text-3xl' => __('Small heading — 32px', 'culvers'),
            'text-4xl' => __('Medium heading — 40px', 'culvers'),
            'text-5xl' => __('Large heading — 48px', 'culvers'),
            'text-6xl' => __('Section title — 58px', 'culvers'),
            'text-7xl' => __('Extra large — 64px', 'culvers'),
            'text-8xl' => __('Huge — 84px (H1)', 'culvers'),
            'text-9xl' => __('Hero — 96px', 'culvers'),
        ];
    }

    /**
     * Body tier — Halyard-style copy 12px → 24px.
     *
     * @return array<string, string>
     */
    public static function getBodySizeChoices(): array
    {
        return [
            'text-xs' => __('Eyebrow — 12px', 'culvers'),
            'text-sm' => __('Caption — 14px', 'culvers'),
            'text-base' => __('Small body — 16px', 'culvers'),
            'text-lg' => __('Body — 18px', 'culvers'),
            'text-xl' => __('Large body — 20px', 'culvers'),
            'text-2xl' => __('Lead — 24px', 'culvers'),
        ];
    }

    /** @return array<string, string> */
    public static function getWeightChoices(): array
    {
        return [
            'font-light' => __('Light', 'culvers'),
            'font-normal' => __('Normal', 'culvers'),
            'font-medium' => __('Medium', 'culvers'),
            'font-semibold' => __('Semi bold', 'culvers'),
            'font-bold' => __('Bold', 'culvers'),
        ];
    }

    public static function validateSize(string $size, string $default = 'text-lg'): string
    {
        $valid = array_merge(
            array_keys(self::getHeaderSizeChoices()),
            array_keys(self::getBodySizeChoices())
        );

        return in_array($size, $valid, true) ? $size : $default;
    }

    public static function validateBodySize(mixed $size, string $default = 'text-lg'): string
    {
        $valid = array_keys(self::getBodySizeChoices());
        $s = is_string($size) ? trim($size) : '';

        if ($s === '') {
            return $default;
        }

        return in_array($s, $valid, true) ? $s : $default;
    }

    public static function validateWeight(string $weight, string $default = 'font-normal'): string
    {
        $valid = array_keys(self::getWeightChoices());

        return in_array($weight, $valid, true) ? $weight : $default;
    }

    public static function validateColor(string $color, string $default = 'text-white'): string
    {
        $valid = [
            'text-black',
            'text-white',
            'text-white/80',
            'text-brand-500',
            'text-deep-moss',
            'text-text-muted',
        ];

        return in_array($color, $valid, true) ? $color : $default;
    }

    private static function sanitizeSize(string $size): string
    {
        $valid = array_merge(
            array_keys(self::getHeaderSizeChoices()),
            array_keys(self::getBodySizeChoices())
        );

        return in_array($size, $valid, true) ? $size : 'text-lg';
    }

    private static function sanitizeWeight(string $weight): string
    {
        $valid = array_keys(self::getWeightChoices());

        return in_array($weight, $valid, true) ? $weight : 'font-normal';
    }
}
