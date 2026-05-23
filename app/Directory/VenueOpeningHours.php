<?php

declare(strict_types=1);

namespace App\Directory;

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

    /**
     * @return list<array{day_label: string, time_range: string, weekday_highlight: string}>
     */
    public static function rowsFromHtml(string $html): array
    {
        if (
            ! preg_match_all(
                '#<div class="opening_hours(?:\s+active)?">\s*([A-Za-z]+)\s*<span>([^<]+)</span>\s*</div>#is',
                $html,
                $matches,
                PREG_SET_ORDER
            )
        ) {
            return [];
        }

        /** @var list<array{day_label: string, time_range: string, weekday_highlight: string}> $rows */
        $rows = [];

        foreach ($matches as $match) {
            $day = trim((string) $match[1]);
            $time = trim(html_entity_decode((string) $match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($day === '' || $time === '') {
                continue;
            }

            $rows[] = [
                'day_label' => $day,
                'time_range' => $time,
                'weekday_highlight' => self::DAY_TO_WEEKDAY[$day] ?? 'none',
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $baseRow
     * @param  list<array{day_label: string, time_range: string, weekday_highlight: string}>  $rows
     * @return array<string, mixed>
     */
    public static function mergeIntoOpeningHoursRow(array $baseRow, array $rows): array
    {
        if ($rows === []) {
            return $baseRow;
        }

        $baseRow['hours_rows'] = $rows;

        return $baseRow;
    }
}
