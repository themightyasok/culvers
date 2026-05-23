<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Wraps the first case-insensitive match in a query with Halyard Medium
 * (Figma header search row `51:8146`).
 */
final class SearchHighlight
{
    public static function mark(string $text, string $query): string
    {
        $query = trim($query);
        if ($query === '') {
            return esc_html($text);
        }

        $lower = mb_strtolower($text);
        $needle = mb_strtolower($query);
        $idx = mb_strpos($lower, $needle);
        if ($idx === false) {
            return esc_html($text);
        }

        $len = mb_strlen($query);
        $before = mb_substr($text, 0, $idx);
        $match = mb_substr($text, $idx, $len);
        $after = mb_substr($text, $idx + $len);

        return esc_html($before)
            . '<strong class="font-medium">' . esc_html($match) . '</strong>'
            . esc_html($after);
    }
}
