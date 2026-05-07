<?php

declare(strict_types=1);

namespace App\Config;

/**
 * Reads Tailwind v4 `@theme` tokens from CSS so PHP (ACF palettes, sanitizers, Background)
 * stays aligned with the canonical token file. Scan paths and plugins belong in `tailwind.config.js`.
 */
final class ThemeTokens
{
    private const RELATIVE_PATH = '/resources/styles/theme.tokens.css';

    /**
     * Prefer these slugs when multiple `--color-*` entries share the same hex (e.g. canvas vs zinc-950).
     *
     * @var array<int, string>
     */
    private const COLOR_SLUG_PRIORITY = [
        'glowleaf',
        'deep-moss',
        'faded-olive',
        'dustleaf',
        'light-brown',
        'light-cream',
        'off-white',
        'lighter-cream',
        'canvas',
        'surface',
        'surface-muted',
        'border-subtle',
        'text',
        'text-muted',
        'brand-500',
        'brand-600',
        'brand-700',
        'white',
        'black',
        'zinc-50',
        'zinc-100',
        'zinc-200',
        'zinc-300',
        'zinc-400',
        'zinc-500',
        'zinc-600',
        'zinc-700',
        'zinc-800',
        'zinc-900',
        'zinc-950',
    ];

    /**
     * Fallback when theme CSS is unreadable (e.g. mis-deployed files).
     *
     * @var array<int, string>
     */
    private const FALLBACK_HEX = [
        '#d4ff50',
        '#2e301e',
        '#4f5438',
        '#8b8c67',
        '#beb6aa',
        '#fdfcf3',
        '#ededeb',
        '#fffefa',
        '#09090b',
        '#18181b',
        '#27272a',
        '#3f3f46',
        '#71717a',
        '#a1a1aa',
        '#fafafa',
        '#f4f4f5',
    ];

    /** @var array{slugHex: array<string, string>, hexPalette: array<int, string>}|null */
    private static ?array $colorCache = null;

    public static function absolutePath(): string
    {
        return get_template_directory() . self::RELATIVE_PATH;
    }

    /**
     * Normalize `#rgb` / `#rrggbb` (any case) to lowercase `#rrggbb`, or '' if invalid.
     */
    public static function normalizeColorHex(string $hex): string
    {
        return self::normalizeHex($hex);
    }

    /**
     * Each `--color-{slug}` from theme.tokens.css → normalized hex. Order follows file order;
     * first declaration wins if the same slug appears twice.
     *
     * @return array<string, string>
     */
    public static function colorSlugHexMap(): array
    {
        return self::parsedColors()['slugHex'];
    }

    /**
     * Map a normalized `#rrggbb` to a theme colour slug for utilities like `bg-{slug}`.
     */
    public static function slugForNormalizedHex(string $normalizedHex): ?string
    {
        $normalizedHex = self::normalizeHex($normalizedHex);
        if ($normalizedHex === '') {
            return null;
        }

        $slugHex = self::colorSlugHexMap();
        $matches = [];
        foreach ($slugHex as $slug => $hex) {
            if ($hex === $normalizedHex) {
                $matches[] = $slug;
            }
        }

        if ($matches === []) {
            return null;
        }

        foreach (self::COLOR_SLUG_PRIORITY as $preferred) {
            if (in_array($preferred, $matches, true)) {
                return $preferred;
            }
        }

        return $matches[0];
    }

    /**
     * Unique normalized `#rrggbb` values from `--color-* : #hex` declarations.
     *
     * @return array<int, string>
     */
    public static function colorHexPalette(): array
    {
        return self::parsedColors()['hexPalette'];
    }

    public static function paletteStringForAcf(): string
    {
        return implode(',', self::colorHexPalette());
    }

    /**
     * @return array{slugHex: array<string, string>, hexPalette: array<int, string>}
     */
    private static function parsedColors(): array
    {
        if (self::$colorCache !== null) {
            return self::$colorCache;
        }

        $path = self::absolutePath();
        if (! is_readable($path)) {
            return self::$colorCache = [
                'slugHex' => [],
                'hexPalette' => self::sortedUnique(self::FALLBACK_HEX),
            ];
        }

        $css = file_get_contents($path);
        if (! is_string($css) || $css === '') {
            return self::$colorCache = [
                'slugHex' => [],
                'hexPalette' => self::sortedUnique(self::FALLBACK_HEX),
            ];
        }

        preg_match_all(
            '/--color-([a-zA-Z0-9-]+)\s*:\s*(#[0-9A-Fa-f]{3}|#[0-9A-Fa-f]{6})\s*;/',
            $css,
            $set,
            PREG_SET_ORDER
        );

        $slugHex = [];
        foreach ($set as $row) {
            $slug = $row[1];
            $norm = self::normalizeHex((string) $row[2]);
            if ($norm === '') {
                continue;
            }
            if (! isset($slugHex[$slug])) {
                $slugHex[$slug] = $norm;
            }
        }

        $hexes = array_values($slugHex);
        if ($hexes === []) {
            return self::$colorCache = [
                'slugHex' => [],
                'hexPalette' => self::sortedUnique(self::FALLBACK_HEX),
            ];
        }

        return self::$colorCache = [
            'slugHex' => $slugHex,
            'hexPalette' => self::sortedUnique($hexes),
        ];
    }

    /**
     * @param list<string> $hexes
     * @return list<string>
     */
    private static function sortedUnique(array $hexes): array
    {
        $hexes = array_map(static fn (string $h): string => self::normalizeHex($h), $hexes);
        $hexes = array_values(array_filter(array_unique($hexes)));

        sort($hexes);

        return $hexes;
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
