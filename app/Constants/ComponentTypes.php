<?php

namespace App\Constants;

/**
 * Component Type Constants
 *
 * Defines valid component types and statuses
 */
class ComponentTypes
{
    public const DISPLAY_BLOCK = 'block';
    public const DISPLAY_ROW = 'row';
    public const DISPLAY_TABLE = 'table';

    public const WIDTH_FULL = 'full';
    public const WIDTH_HALF = '1/2';
    public const WIDTH_THIRD = '1/3';
    public const WIDTH_TWO_THIRDS = '2/3';
    public const WIDTH_QUARTER = '1/4';
    public const WIDTH_THREE_QUARTERS = '3/4';

    public const PADDING_NONE = 'none';
    /** No vertical padding at any breakpoint (use when a block should sit flush against the next). */
    public const PADDING_FLUSH = 'flush';
    public const PADDING_SMALL = 'sm';
    public const PADDING_MEDIUM = 'md';
    public const PADDING_LARGE = 'lg';
    public const PADDING_EXTRA_LARGE = 'xl';

    public const BACKGROUND_NONE = 'none';
    public const BACKGROUND_COLOR = 'color';
    public const BACKGROUND_GRADIENT = 'gradient';
    public const BACKGROUND_IMAGE = 'image';
    public const BACKGROUND_IMAGE_CENTERED = 'image_centered';
    public const BACKGROUND_VIDEO = 'video';

    /**
     * Get valid display types.
     *
     * @return list<string>
     */
    public static function getDisplayTypes(): array
    {
        return [
            self::DISPLAY_BLOCK,
            self::DISPLAY_ROW,
            self::DISPLAY_TABLE,
        ];
    }

    /**
     * Get valid width options.
     *
     * @return list<string>
     */
    public static function getWidthOptions(): array
    {
        return [
            self::WIDTH_FULL,
            self::WIDTH_HALF,
            self::WIDTH_THIRD,
            self::WIDTH_TWO_THIRDS,
            self::WIDTH_QUARTER,
            self::WIDTH_THREE_QUARTERS,
        ];
    }

    /**
     * Get valid padding options.
     *
     * @return list<string>
     */
    public static function getPaddingOptions(): array
    {
        return [
            self::PADDING_NONE,
            self::PADDING_FLUSH,
            self::PADDING_SMALL,
            self::PADDING_MEDIUM,
            self::PADDING_LARGE,
            self::PADDING_EXTRA_LARGE,
        ];
    }

    /**
     * Get valid background types.
     *
     * @return list<string>
     */
    public static function getBackgroundTypes(): array
    {
        return [
            self::BACKGROUND_NONE,
            self::BACKGROUND_COLOR,
            self::BACKGROUND_GRADIENT,
            self::BACKGROUND_IMAGE,
            self::BACKGROUND_IMAGE_CENTERED,
            self::BACKGROUND_VIDEO,
        ];
    }
}
