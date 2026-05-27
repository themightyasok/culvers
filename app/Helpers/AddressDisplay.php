<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Formats plain-text addresses for two-line display (store details, etc.).
 */
final class AddressDisplay
{
    /**
     * @return list<string> One or two non-empty lines.
     */
    public static function balancedLines(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $explicit = preg_split('/\r\n|\r|\n|<br\s*\/?>/i', wp_strip_all_tags($raw));
        if (! is_array($explicit)) {
            $explicit = [];
        }

        $explicit = array_values(array_filter(array_map(
            static fn (string $part): string => trim(preg_replace('/\s+/u', ' ', $part) ?? ''),
            $explicit
        ), static fn (string $part): bool => $part !== ''));

        if (count($explicit) >= 2) {
            return self::joinExplicitLines($explicit);
        }

        $plain = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($raw)) ?? '');
        if ($plain === '') {
            return [];
        }

        $parts = array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            explode(',', $plain)
        ), static fn (string $part): bool => $part !== ''));

        if (count($parts) >= 4) {
            return [
                implode(', ', array_slice($parts, 0, 2)),
                implode(', ', array_slice($parts, 2)),
            ];
        }

        if (count($parts) === 3) {
            return [
                $parts[0],
                implode(', ', array_slice($parts, 1)),
            ];
        }

        if (count($parts) === 2) {
            return $parts;
        }

        return self::balancedWordSplit($plain);
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private static function joinExplicitLines(array $lines): array
    {
        if (count($lines) === 2) {
            return $lines;
        }

        $mid = (int) ceil(count($lines) / 2);

        return [
            implode(', ', array_slice($lines, 0, $mid)),
            implode(', ', array_slice($lines, $mid)),
        ];
    }

    /**
     * @return list<string>
     */
    private static function balancedWordSplit(string $text): array
    {
        $words = preg_split('/\s+/u', $text);
        if (! is_array($words) || $words === []) {
            return [$text];
        }

        if (count($words) === 1) {
            return [$text];
        }

        $mid = (int) ceil(count($words) / 2);

        return [
            implode(' ', array_slice($words, 0, $mid)),
            implode(' ', array_slice($words, $mid)),
        ];
    }
}
