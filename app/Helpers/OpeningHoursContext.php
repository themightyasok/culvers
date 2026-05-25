<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Display context for the shared {@see opening_hours} flexible layout.
 *
 * Content (`hours_rows`) is always saved per post — centre pages hold site-wide
 * hours; shop / eat & drink singles hold that venue's week. This enum only
 * controls presentation (colours, width, dividers).
 */
final class OpeningHoursContext
{
    public const CENTRE = 'centre';

    public const RETAILER = 'retailer';

    /**
     * @param array<string, mixed> $component
     */
    public static function resolve(array $component): string
    {
        $raw = is_string($component['hours_context'] ?? null)
            ? trim($component['hours_context'])
            : '';

        if ($raw === self::RETAILER || $raw === self::CENTRE) {
            return $raw;
        }

        return self::CENTRE;
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function isRetailer(array $component): bool
    {
        return self::resolve($component) === self::RETAILER;
    }

    /**
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return [
            self::CENTRE => __('Centre — site-wide hours', 'culvers'),
            self::RETAILER => __('Retailer — this venue\'s hours', 'culvers'),
        ];
    }
}
