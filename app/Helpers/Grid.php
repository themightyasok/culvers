<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Twelve-column layout helpers using Tailwind grid + default spacing scale utilities.
 */
final class Grid
{
    /** @var array<int|string, string>|null */
    private static ?array $gridClassMappings = null;

    /** @var array<int|string, string>|null */
    private static ?array $columnChoices = null;

    /** @var array<int, string>|null */
    private static ?array $fullWidthComponents = null;

    /** @var array<int, string>|null */
    private static ?array $defaultFullWidthComponents = null;

    /**
     * @return array<int|string, string>
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
                'full' => 'Full width (no border)',
            ];
        }

        return self::$columnChoices;
    }

    /**
     * @return array<int|string, string>
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
                'full' => 'col-span-12 w-full min-w-0',
            ];
        }

        return self::$gridClassMappings;
    }

    /**
     * @param int|string $width Component width (6-12 or 'full')
     * @return array{column: string, padding: string}
     */
    public static function getClasses($width, bool $isFullWidthComponent = false): array
    {
        if ($isFullWidthComponent) {
            return ['column' => 'col-span-12 w-full min-w-0', 'padding' => ''];
        }

        $mappings = self::getGridClassMappings();

        if (isset($mappings[$width])) {
            $columnClass = $mappings[$width];
        } else {
            $columnClass = $mappings[12];
        }

        return ['column' => $columnClass, 'padding' => ''];
    }

    public static function isFullWidth(string $layout): bool
    {
        if (self::$fullWidthComponents === null) {
            self::$fullWidthComponents = apply_filters('culvers_full_width_components', []);
        }

        return in_array($layout, self::$fullWidthComponents, true);
    }

    public static function getMainGridContainerClasses(): string
    {
        return 'grid grid-cols-12 gap-x-6';
    }

    /**
     * Strip the responsive horizontal gutters (`px-*` / `sm:px-*` / `lg:px-*` …) that
     * {@see self::getClasses()} adds to a column class string.
     *
     * **Rule:** every component that renders its own inner shell (`LayoutShell::INNER_*`,
     * a custom `mx-auto max-w-* px-*` wrapper, or a full-bleed media element) MUST call
     * this helper on `_grid_classes` to avoid double-padding (grid gutter + inner gutter).
     * Components without an inner shell (e.g. `content-section`) leave the grid gutters
     * intact — they ARE the gutters that frame the section content.
     */
    public static function stripHorizontalInsetPadding(string $gridClasses): string
    {
        if ($gridClasses === '') {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', preg_replace('/\b(?:sm:|md:|lg:|xl:)?px-[^\s]+\s*/', '', $gridClasses)));
    }

    /**
     * @return int|string
     */
    public static function getDefaultComponentWidth(string $layout)
    {
        if (self::$defaultFullWidthComponents === null) {
            self::$defaultFullWidthComponents = apply_filters('culvers_default_full_width_components', []);
        }

        return in_array($layout, self::$defaultFullWidthComponents, true) ? 'full' : 12;
    }

    /**
     * @param mixed $width Raw width value
     * @return int|string
     */
    public static function validateComponentWidth($width)
    {
        if ($width === 'full' || $width === '') {
            return 'full';
        }

        $width = (int) $width;

        if ($width < 6 || $width > 12) {
            return 12;
        }

        return $width;
    }

    public static function getInternalGridClasses(int $componentWidth, int $columns): string
    {
        /** @var array<int, string> $columnMappings */
        static $columnMappings = [
            2 => 'grid-cols-1 lg:grid-cols-2',
            3 => 'grid-cols-1 lg:grid-cols-3',
            4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
        ];

        $columnClass = $columnMappings[$columns] ?? 'grid-cols-1';

        return "grid gap-6 {$columnClass}";
    }

    /**
     * @param list<int|float> $splits
     * @return list<int>
     */
    public static function calculateInternalColumnSpans(int $parentWidth, array $splits): array
    {
        $spans = [];
        foreach ($splits as $split) {
            $span = (int) round(($split / 100) * $parentWidth);
            $spans[] = max(1, min(12, $span));
        }

        return $spans;
    }
}
