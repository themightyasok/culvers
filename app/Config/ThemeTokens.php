<?php

declare(strict_types=1);

namespace App\Config;

/**
 * Reads Tailwind v4 `@theme` tokens from CSS so PHP (ACF palettes, sanitizers) stays aligned
 * with the CSS-first source of truth — no tailwind.config.js palette duplication.
 */
final class ThemeTokens
{
    private const RELATIVE_PATH = '/resources/styles/theme.tokens.css';

    /**
     * Fallback when theme CSS is unreadable (e.g. mis-deployed files).
     *
     * @var array<int, string>
     */
    private const FALLBACK_HEX = [
        '#0284c7',
        '#0369a1',
        '#0ea5e9',
        '#09090b',
        '#18181b',
        '#27272a',
        '#3f3f46',
        '#71717a',
        '#a1a1aa',
        '#fafafa',
        '#f4f4f5',
    ];

    public static function absolutePath(): string
    {
        return get_template_directory() . self::RELATIVE_PATH;
    }

    /**
     * Unique normalized `#rrggbb` values from `--color-* : #hex` declarations.
     *
     * @return array<int, string>
     */
    public static function colorHexPalette(): array
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $path = self::absolutePath();
        if (! is_readable($path)) {
            return $cached = self::sortedUnique(self::FALLBACK_HEX);
        }

        $css = file_get_contents($path);
        if (! is_string($css) || $css === '') {
            return $cached = self::sortedUnique(self::FALLBACK_HEX);
        }

        preg_match_all(
            '/--color-[a-zA-Z0-9-]+\s*:\s*(#[0-9A-Fa-f]{3}|#[0-9A-Fa-f]{6})\s*;/',
            $css,
            $matches
        );
        $hexes = [];
        foreach ($matches[1] as $raw) {
            $norm = self::normalizeHex((string) $raw);
            if ($norm !== '') {
                $hexes[] = $norm;
            }
        }

        if ($hexes === []) {
            return $cached = self::sortedUnique(self::FALLBACK_HEX);
        }

        return $cached = self::sortedUnique($hexes);
    }

    public static function paletteStringForAcf(): string
    {
        return implode(',', self::colorHexPalette());
    }

    /**
     * @param array<int, string> $hexes
     * @return array<int, string>
     */
    private static function sortedUnique(array $hexes): array
    {
        $hexes = array_map(static fn (string $h): string => self::normalizeHex($h), $hexes);
        $hexes = array_filter(array_unique($hexes));

        sort($hexes);

        return array_values($hexes);
    }

    private static function normalizeHex(string $hex): string
    {
        $hex = trim($hex);
        if ($hex === '') {
            return '';
        }

        $hex = ltrim($hex, '#');
        $len = strlen($hex);
        if ($len === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        } elseif ($len !== 6) {
            return '';
        }

        if (! preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return '';
        }

        return '#' . strtolower($hex);
    }
}
