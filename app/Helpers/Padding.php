<?php

namespace App\Helpers;

/**
 * Padding helpers
 *
 * Converts ACF padding choices (none, sm, md, lg) to Tailwind classes.
 * Used for component top/bottom padding and header/subheader element spacing.
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
     * Get Tailwind padding classes for header, subheader, or body text elements.
     * Same padding scale applied consistently to all three.
     *
     * @param string $paddingTop    none|small|medium|large|large-xl
     * @param string $paddingBottom none|small|medium|large|large-xl
     * @return string Space-separated Tailwind classes
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
     * Get padding classes
     *
     * @param array<string, mixed>|null $component Component array
     * @return string Padding classes
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
     * Bottom padding classes only (no pt-*). Used when top spacing is handled elsewhere
     * (e.g. pricing: pricing-calculator.css wrapper offset) so ACF "flush" top
     * cannot emit pt-0 / lg:pt-0 and wipe that spacing.
     *
     * @param array<string, mixed>|null $component
     */
    public static function getBottomPaddingClassesOnly(?array $component = null): string
    {
        $bottom = $component['bottom_padding'] ?? 'md';

        return self::getBottomPaddingClass($bottom);
    }

    /**
     * Get top padding class from size key.
     *
     * @param string $size none|sm|md|lg
     * @return string Tailwind pt-* class string
     */
    private static function getTopPaddingClass(string $size): string
    {
        $map = [
            // Mobile baseline: always 64px top spacing. Desktop follows ACF setting.
            'none' => 'pt-16 lg:pt-0',
            'flush' => 'pt-0 lg:pt-0',
            'sm' => 'pt-16 lg:pt-8',
            'md' => 'pt-16 lg:pt-16',
            'lg' => 'pt-16 lg:pt-32',
        ];

        return $map[$size] ?? 'pt-16 lg:pt-16';
    }

    /**
     * Get bottom padding class from size key.
     *
     * @param string $size none|sm|md|lg
     * @return string Tailwind pb-* class string
     */
    private static function getBottomPaddingClass(string $size): string
    {
        $map = [
            // Mobile baseline: always 64px bottom spacing. Desktop follows ACF setting.
            'none' => 'pb-16 lg:pb-0',
            'flush' => 'pb-0 lg:pb-0',
            'sm' => 'pb-16 lg:pb-8',
            'md' => 'pb-16 lg:pb-16',
            'lg' => 'pb-16 lg:pb-32',
        ];

        return $map[$size] ?? 'pb-16 lg:pb-16';
    }
}
