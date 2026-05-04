<?php

namespace App\Helpers;

/**
 * Grid System Helper
 *
 * Twelve-column layout helpers aligned with Tailwind CSS utility classes.
 *
 * Optimized with static caching for performance
 */
class Grid
{
    /**
     * Cached grid class mappings
     * @var array|null
     */
    private static ?array $gridClassMappings = null;

    /**
     * Cached column choices
     * @var array|null
     */
    private static ?array $columnChoices = null;

    /**
     * Cached full-width components list
     * @var array|null
     */
    private static ?array $fullWidthComponents = null;

    /**
     * Cached default full-width components list
     * @var array|null
     */
    private static ?array $defaultFullWidthComponents = null;

    /**
     * Get available grid column choices
     * Cached for performance
     */
    public static function getColumnChoices(): array
    {
        if (self::$columnChoices === null) {
            self::$columnChoices = [
                6 => '6 Columns (50%)',
                7 => '7 Columns (~58%)',
                8 => '8 Columns (~67%)',
                9 => '9 Columns (75%)',
                10 => '10 Columns (~83%)',
                11 => '11 Columns (~92%)',
                12 => '12 Columns (100% with border)',
                'full' => 'Full width (no border)'
            ];
        }

        return self::$columnChoices;
    }

    /**
     * Grid class mappings for component widths
     * Uses Tailwind CSS grid classes with responsive centering
     * Cached for performance
     */
    private static function getGridClassMappings(): array
    {
        if (self::$gridClassMappings === null) {
            self::$gridClassMappings = [
                6 => 'col-span-12 px-5 sm:px-6 lg:col-span-6 lg:col-start-4 lg:px-16',
                7 => 'col-span-12 px-5 sm:px-6 lg:col-span-7 lg:col-start-3 lg:px-16',
                8 => 'col-span-12 px-5 sm:px-6 lg:col-span-8 lg:col-start-3 lg:px-16',
                9 => 'col-span-12 px-5 sm:px-6 lg:col-span-9 lg:col-start-2 lg:px-16',
                10 => 'col-span-12 px-5 sm:px-6 lg:col-span-10 lg:col-start-2 lg:px-16',
                11 => 'col-span-12 px-5 sm:px-6 lg:col-span-11 lg:col-start-1 lg:px-16',
                12 => 'col-span-12 px-5 sm:px-6 lg:px-16',
                'full' => 'col-span-12 w-full min-w-0'
            ];
        }

        return self::$gridClassMappings;
    }

    /**
     * Convert component width to CSS grid classes
     *
     * @param int|string $width Component width (6-12 or 'full')
     * @param bool $isFullWidthComponent Whether this is a full-width component type
     * @return array Array with 'column' and 'padding' keys
     */
    public static function getClasses($width, bool $isFullWidthComponent = false): array
    {
        // Early return for full-width components (span full row so next component clears)
        if ($isFullWidthComponent) {
            return ['column' => 'col-span-12 w-full min-w-0', 'padding' => ''];
        }

        // Fast array lookup with cached mappings
        $mappings = self::getGridClassMappings();

        // Direct array access is faster than ?? operator when key exists
        if (isset($mappings[$width])) {
            $columnClass = $mappings[$width];
        } else {
            // Fallback to 12 columns if invalid width
            $columnClass = $mappings[12];
        }

        return ['column' => $columnClass, 'padding' => ''];
    }

    /**
     * Check if a component layout is a full-width type
     * Cached per request for performance
     *
     * @param string $layout Component layout name
     * @return bool
     */
    public static function isFullWidth(string $layout): bool
    {
        if (self::$fullWidthComponents === null) {
            self::$fullWidthComponents = apply_filters('culvers_full_width_components', []);
        }

        // Use isset for faster lookup (convert to associative array check)
        return in_array($layout, self::$fullWidthComponents, true);
    }

    /**
     * Get the main grid container classes
     *
     * @return string Tailwind CSS classes for the main grid container
     */
    public static function getMainGridContainerClasses(): string
    {
        return 'grid grid-cols-12 gap-x-6';
    }

    /**
     * Get default component width for specific component types
     * Cached per request for performance
     *
     * @param string $layout Component layout name
     * @return int|string Default width (6-12 or 'full')
     */
    public static function getDefaultComponentWidth(string $layout)
    {
        if (self::$defaultFullWidthComponents === null) {
            self::$defaultFullWidthComponents = apply_filters('culvers_default_full_width_components', []);
        }

        return in_array($layout, self::$defaultFullWidthComponents, true) ? 'full' : 12;
    }

    /**
     * Validate component width value
     * Optimized with early returns and type checking
     *
     * @param mixed $width Raw width value
     * @return int|string Validated width (6-12 or 'full')
     */
    public static function validateComponentWidth($width)
    {
        // Early return for 'full' string
        if ($width === 'full' || $width === '') {
            return 'full';
        }

        // Type coercion and validation in one step
        $width = (int) $width;

        // Range check with early return
        if ($width < 6 || $width > 12) {
            return 12;
        }

        return $width;
    }

    /**
     * Get internal grid classes for content within components
     * Optimized with cached mappings
     *
     * @param int $componentWidth Parent component width (6-12)
     * @param int $columns Number of columns for internal grid
     * @return string Tailwind CSS classes
     */
    public static function getInternalGridClasses(int $componentWidth, int $columns): string
    {
        // Static mapping for common cases (cached at class level)
        static $columnMappings = [
            2 => 'grid-cols-1 lg:grid-cols-2',
            3 => 'grid-cols-1 lg:grid-cols-3',
            4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4'
        ];

        // Fast lookup with isset
        $columnClass = $columnMappings[$columns] ?? 'grid-cols-1';

        return "grid gap-6 {$columnClass}";
    }

    /**
     * Calculate internal column spans based on parent component width
     *
     * @param int $parentWidth Parent component width (6-12)
     * @param array $splits Array of percentage splits (e.g., [50, 50])
     * @return array Array of column spans
     */
    public static function calculateInternalColumnSpans(int $parentWidth, array $splits): array
    {
        $spans = [];
        foreach ($splits as $split) {
            $span = round(($split / 100) * $parentWidth);
            $spans[] = max(1, min(12, $span));
        }
        return $spans;
    }
}
