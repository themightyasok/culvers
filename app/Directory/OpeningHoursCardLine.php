<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Directory card subtitle: "Open Today …" from the post's `opening_hours` flexible row.
 */
final class OpeningHoursCardLine
{
    /** @var array<string, int> */
    private const WEEKDAY_TO_PHP = [
        'sun' => 0,
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6,
    ];

    /**
     * Card line for today using site timezone, or null when no matching hours row exists.
     */
    public static function forPost(int $postId): ?string
    {
        $component = self::openingHoursComponentForPost($postId);
        if ($component === null) {
            return null;
        }

        $rowsRaw = $component['hours_rows'] ?? [];
        if (! is_array($rowsRaw) || $rowsRaw === []) {
            return null;
        }

        return self::lineFromHoursRows($rowsRaw);
    }

    /**
     * @param  list<mixed> $hoursRows
     */
    public static function lineFromHoursRows(array $hoursRows): ?string
    {
        $todayNum = (int) wp_date('w', (int) current_time('timestamp'));

        foreach ($hoursRows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $timeRange = trim((string) ($row['time_range'] ?? ''));
            if ($timeRange === '') {
                continue;
            }

            $wk = (string) ($row['weekday_highlight'] ?? 'none');
            if ($wk === 'none' || ! isset(self::WEEKDAY_TO_PHP[$wk])) {
                continue;
            }

            if (self::WEEKDAY_TO_PHP[$wk] !== $todayNum) {
                continue;
            }

            return self::formatTodayLine($timeRange);
        }

        return null;
    }

    private static function formatTodayLine(string $timeRange): string
    {
        if (preg_match('/\bclosed\b/i', $timeRange) === 1) {
            return __('Closed today', 'culvers');
        }

        return sprintf(
            /* translators: %s: opening hours for the current day, e.g. 9am - 5.30pm */
            __('Open Today %s', 'culvers'),
            $timeRange
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function openingHoursComponentForPost(int $postId): ?array
    {
        if (! function_exists('get_field')) {
            return null;
        }

        $components = get_field('components', $postId);
        if (! is_array($components)) {
            return null;
        }

        foreach ($components as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (($row['acf_fc_layout'] ?? '') === 'opening_hours') {
                return $row;
            }
        }

        return null;
    }
}
