<?php

namespace App\Helpers;

/**
 * Converts ACF padding choices to Tailwind classes backed by `@theme` `--spacing-*` tokens
 * in resources/styles/theme.tokens.css.
 */
class Padding
{
    /**
     * ACF choices for header/subheader padding (above and below).
     * Values: none, small, medium, large, large-xl
     *
     * @return array<string, string>
     */
    public static function getHeaderSubheaderPaddingChoices(): array
    {
        return [
            'none' => 'None',
            'small' => 'Small',
            'medium' => 'Medium',
            'large' => 'Large',
            'large-xl' => 'Large XL',
        ];
    }

    /**
     * @param string $paddingTop    none|small|medium|large|large-xl
     * @param string $paddingBottom none|small|medium|large|large-xl
     */
    public static function getHeaderSubheaderPaddingClasses(string $paddingTop, string $paddingBottom): string
    {
        $topMap = [
            'none' => 'pt-zero',
            'small' => 'pt-heading-xs',
            'medium' => 'pt-heading-sm',
            'large' => 'pt-heading-md',
            'large-xl' => 'pt-heading-xl',
        ];
        $bottomMap = [
            'none' => 'pb-zero',
            'small' => 'pb-heading-xs',
            'medium' => 'pb-heading-sm',
            'large' => 'pb-heading-md',
            'large-xl' => 'pb-heading-xl',
        ];

        $top = $topMap[$paddingTop] ?? 'pt-zero';
        $bottom = $bottomMap[$paddingBottom] ?? 'pb-zero';

        return trim("{$top} {$bottom}");
    }

    /**
     * @param array<string, mixed>|null $component Component array
     */
    public static function getClasses(?array $component = null): string
    {
        $top = $component['top_padding'] ?? 'md';
        $bottom = $component['bottom_padding'] ?? 'md';

        $classes = [];

        if ($top) {
            $classes[] = self::getTopPaddingClass($top);
        }

        if ($bottom) {
            $classes[] = self::getBottomPaddingClass($bottom);
        }

        return implode(' ', $classes);
    }

    /**
     * @param array<string, mixed>|null $component
     */
    public static function getBottomPaddingClassesOnly(?array $component = null): string
    {
        $bottom = $component['bottom_padding'] ?? 'md';

        return self::getBottomPaddingClass($bottom);
    }

    /**
     * @param string $size none|flush|sm|md|lg
     */
    private static function getTopPaddingClass(string $size): string
    {
        $map = [
            'none' => 'pt-section-mobile lg:pt-zero',
            'flush' => 'pt-zero lg:pt-zero',
            'sm' => 'pt-section-mobile lg:pt-section-inner-sm',
            'md' => 'pt-section-mobile lg:pt-section-inner-md',
            'lg' => 'pt-section-mobile lg:pt-section-inner-lg',
        ];

        return $map[$size] ?? 'pt-section-mobile lg:pt-section-inner-md';
    }

    /**
     * @param string $size none|flush|sm|md|lg
     */
    private static function getBottomPaddingClass(string $size): string
    {
        $map = [
            'none' => 'pb-section-mobile lg:pb-zero',
            'flush' => 'pb-zero lg:pb-zero',
            'sm' => 'pb-section-mobile lg:pb-section-inner-sm',
            'md' => 'pb-section-mobile lg:pb-section-inner-md',
            'lg' => 'pb-section-mobile lg:pb-section-inner-lg',
        ];

        return $map[$size] ?? 'pb-section-mobile lg:pb-section-inner-md';
    }
}
