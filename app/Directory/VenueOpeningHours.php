<?php

declare(strict_types=1);

namespace App\Directory;

use App\Helpers\OpeningHoursContext;

/**
 * Parses retailer opening hours from culversquare.co.uk `/retailers/{slug}/` markup.
 */
final class VenueOpeningHours
{
    /** @var array<string, string> */
    private const DAY_TO_WEEKDAY = [
        'Monday' => 'mon',
        'Tuesday' => 'tue',
        'Wednesday' => 'wed',
        'Thursday' => 'thu',
        'Friday' => 'fri',
        'Saturday' => 'sat',
        'Sunday' => 'sun',
    ];

    /** @var list<string> */
    private const WEEKDAY_ORDER = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    /**
     * @return list<array{day_label: string, time_range: string, weekday_highlight: string}>
     */
    public static function rowsFromHtml(string $html): array
    {
        if (
            ! preg_match_all(
                '#<div class="opening_hours(?:\s+active)?">\s*(.*?)\s*<span>([^<]+)</span>\s*</div>#is',
                $html,
                $matches,
                PREG_SET_ORDER
            )
        ) {
            return [];
        }

        /** @var array<string, array{day_label: string, time_range: string, weekday_highlight: string}> $byWeekday */
        $byWeekday = [];
        /** @var list<array{day_label: string, time_range: string, weekday_highlight: string}> $specialRows */
        $specialRows = [];

        foreach ($matches as $match) {
            $dayRaw = trim(html_entity_decode(strip_tags((string) $match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $time = trim(html_entity_decode((string) $match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($dayRaw === '' || $time === '') {
                continue;
            }

            $expanded = self::expandDayLabel($dayRaw);
            if ($expanded === []) {
                $specialRows[] = [
                    'day_label' => $dayRaw,
                    'time_range' => $time,
                    'weekday_highlight' => 'none',
                ];

                continue;
            }

            foreach ($expanded as $day) {
                $weekday = self::DAY_TO_WEEKDAY[$day];
                $byWeekday[$weekday] = [
                    'day_label' => $day,
                    'time_range' => $time,
                    'weekday_highlight' => $weekday,
                ];
            }
        }

        /** @var list<array{day_label: string, time_range: string, weekday_highlight: string}> $rows */
        $rows = [];

        foreach (self::WEEKDAY_ORDER as $weekday) {
            if (isset($byWeekday[$weekday])) {
                $rows[] = $byWeekday[$weekday];
            }
        }

        return array_merge($rows, $specialRows);
    }

    /**
     * @return list<string>
     */
    private static function expandDayLabel(string $label): array
    {
        if (preg_match('/^Monday\s*[-–—]\s*Friday$/i', $label)) {
            return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        }

        if (preg_match('/^Monday\s*[-–—]\s*Saturday$/i', $label)) {
            return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        }

        if (preg_match('/^Monday\s*[-–—]\s*Sunday$/i', $label)) {
            return array_keys(self::DAY_TO_WEEKDAY);
        }

        $normalized = ucfirst(strtolower(trim($label)));
        if (isset(self::DAY_TO_WEEKDAY[$normalized])) {
            return [$normalized];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $baseRow
     * @param  list<array{day_label: string, time_range: string, weekday_highlight: string}>  $rows
     * @return array<string, mixed>
     */
    public static function mergeIntoOpeningHoursRow(array $baseRow, array $rows, bool $retailer = true): array
    {
        if ($rows === []) {
            return self::applyRetailerPresentationDefaults($baseRow, $retailer);
        }

        $baseRow['hours_rows'] = $rows;

        return self::applyRetailerPresentationDefaults($baseRow, $retailer);
    }

    /**
     * @param  array<string, mixed>  $baseRow
     * @return array<string, mixed>
     */
    public static function applyRetailerPresentationDefaults(array $baseRow, bool $retailer = true): array
    {
        if (! $retailer) {
            return $baseRow;
        }

        $baseRow['hours_context'] = OpeningHoursContext::RETAILER;

        if (trim((string) ($baseRow['hours_heading'] ?? '')) === '') {
            $baseRow['hours_heading'] = __('Opening hours', 'culvers');
        }

        return $baseRow;
    }
}
