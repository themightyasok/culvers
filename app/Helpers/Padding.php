<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Converts ACF padding choices to Tailwind spacing utilities (`pt-*`, `pb-*`, …).
 */
final class Padding
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
     * Get Tailwind padding classes for header, subheader, or body text elements.
     *
     * @param string $paddingTop    none|small|medium|large|large-xl
     * @param string $paddingBottom none|small|medium|large|large-xl
     */
    public static function getHeaderSubheaderPaddingClasses(string $paddingTop, string $paddingBottom): string
    {
        $topMap = [
            'none' => 'pt-0',
            'small' => 'pt-2',
            'medium' => 'pt-4',
            'large' => 'pt-6',
            'large-xl' => 'pt-8',
        ];
        $bottomMap = [
            'none' => 'pb-0',
            'small' => 'pb-2',
            'medium' => 'pb-4',
            'large' => 'pb-6',
            'large-xl' => 'pb-8',
        ];

        $top = $topMap[$paddingTop] ?? 'pt-0';
        $bottom = $bottomMap[$paddingBottom] ?? 'pb-0';

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
     * Bottom padding classes only (no pt-*).
     *
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
            'none' => 'pt-16 lg:pt-0',
            'flush' => 'pt-0 lg:pt-0',
            'sm' => 'pt-16 lg:pt-8',
            'md' => 'pt-16 lg:pt-16',
            'lg' => 'pt-16 lg:pt-32',
        ];

        return $map[$size] ?? 'pt-16 lg:pt-16';
    }

    /**
     * @param string $size none|flush|sm|md|lg
     */
    private static function getBottomPaddingClass(string $size): string
    {
        $map = [
            'none' => 'pb-16 lg:pb-0',
            'flush' => 'pb-0 lg:pb-0',
            'sm' => 'pb-16 lg:pb-8',
            'md' => 'pb-16 lg:pb-16',
            'lg' => 'pb-16 lg:pb-32',
        ];

        return $map[$size] ?? 'pb-16 lg:pb-16';
    }
}
