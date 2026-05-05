<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Tailwind typography classes for CMS-driven components (font family + size + weight).
 */
class Typography
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

    /** @return array<string, string> */
    public static function getHeaderSizeChoices(): array
    {
        return [
            'text-2xl' => __('Small (text-2xl)', 'culvers'),
            'text-3xl' => __('Medium (text-3xl)', 'culvers'),
            'text-4xl' => __('Large (text-4xl)', 'culvers'),
            'text-5xl' => __('Extra large (text-5xl)', 'culvers'),
            'text-6xl' => __('Huge (text-6xl)', 'culvers'),
            'text-7xl' => __('Extra huge (text-7xl)', 'culvers'),
            'text-8xl' => __('Massive (text-8xl)', 'culvers'),
            'text-9xl' => __('Ultra (text-9xl)', 'culvers'),
            'text-xxl' => __('XXL', 'culvers'),
            'text-xxxl' => __('XXXL', 'culvers'),
            'text-xxxxl' => __('XXXXL', 'culvers'),
        ];
    }

    /** @return array<string, string> */
    public static function getBodySizeChoices(): array
    {
        return [
            'text-xs' => __('Extra small (text-xs)', 'culvers'),
            'text-sm' => __('Small (text-sm)', 'culvers'),
            'text-base' => __('Base (text-base)', 'culvers'),
            'text-lg' => __('Large (text-lg)', 'culvers'),
            'text-xl' => __('Extra large (text-xl)', 'culvers'),
            'text-2xl' => __('Huge (text-2xl)', 'culvers'),
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

    public static function validateSize(string $size, string $default = 'text-base'): string
    {
        $valid = array_merge(
            array_keys(self::getHeaderSizeChoices()),
            array_keys(self::getBodySizeChoices())
        );

        return in_array($size, $valid, true) ? $size : $default;
    }

    public static function validateBodySize(mixed $size, string $default = 'text-base'): string
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

        return in_array($size, $valid, true) ? $size : 'text-base';
    }

    private static function sanitizeWeight(string $weight): string
    {
        $valid = array_keys(self::getWeightChoices());

        return in_array($weight, $valid, true) ? $weight : 'font-normal';
    }
}
