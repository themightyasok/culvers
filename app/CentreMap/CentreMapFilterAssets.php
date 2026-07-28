<?php

declare(strict_types=1);

namespace App\CentreMap;

/**
 * Pre-rendered centre-map artworks — one file per filter category (July 2026 V7 set).
 *
 * Files live under {@see self::DIR} with filenames matching category slugs
 * (see Plan my visit centre_map categories + {@see DirectoryFilterDefinitions}).
 * PNG is preferred when present; SVG remains a fallback for legacy files.
 */
final class CentreMapFilterAssets
{
    private const DIR = 'resources/images/centre-map/filters/';

    /** @var list<string> */
    private const EXTS = ['png', 'svg'];

    /** @var array<string, string> canonical slug => filename stem */
    private const FILES = [
        'standard' => 'standard',
        'shop-all' => 'shop-all',
        'beauty-wellbeing' => 'beauty-wellbeing',
        'fashion' => 'fashion',
        'jewellery' => 'jewellery',
        'toys-gifts' => 'toys-gifts',
        'technology' => 'technology',
        'speciality' => 'speciality',
        'home' => 'home',
        'eat-drink-all' => 'eat-drink-all',
        'grab-go' => 'grab-go',
        'healthy-options' => 'healthy-options',
        'cafes' => 'cafes',
        'parent-child' => 'parent-child',
        'lost-property' => 'lost-property',
        'toilets' => 'toilets',
    ];

    /** Alternate slugs that map onto a canonical artwork file. */
    private const SLUG_ALIASES = [
        'all' => 'standard',
        'eat-drink' => 'eat-drink-all',
        'eat-drink-cafes' => 'cafes',
        'eat-drink-takeaway' => 'grab-go',
        'guest-services' => 'lost-property',
        'guest-services-toilets' => 'toilets',
        'guest-services-lost-property' => 'lost-property',
        'guest-services-parent-child' => 'parent-child',
    ];

    public static function hasFilterMaps(): bool
    {
        return self::resolvePath('standard') !== '';
    }

    public static function defaultUrl(): string
    {
        return self::urlForCanonicalSlug('standard');
    }

    /**
     * @return array<string, string> slug (incl. aliases) => public URL
     */
    public static function urlsBySlug(): array
    {
        $out = [];

        foreach (self::FILES as $slug => $stem) {
            if ($slug === 'standard') {
                continue;
            }

            $url = self::fileUrl($stem);
            if ($url === '') {
                continue;
            }

            $out[$slug] = $url;
        }

        foreach (self::SLUG_ALIASES as $alias => $canonical) {
            if ($canonical === 'standard') {
                continue;
            }

            $url = self::urlForCanonicalSlug($canonical);
            if ($url !== '') {
                $out[$alias] = $url;
            }
        }

        return $out;
    }

    public static function urlForSlug(string $slug): string
    {
        $normalized = sanitize_title($slug);
        if ($normalized === '' || $normalized === 'all') {
            return self::defaultUrl();
        }

        if (str_ends_with($normalized, '-all') && isset(self::FILES[$normalized])) {
            return self::urlForCanonicalSlug($normalized);
        }

        $canonical = self::SLUG_ALIASES[$normalized] ?? $normalized;

        if ($canonical === 'standard' || ! isset(self::FILES[$canonical])) {
            return self::defaultUrl();
        }

        return self::urlForCanonicalSlug($canonical);
    }

    private static function urlForCanonicalSlug(string $canonical): string
    {
        $stem = self::FILES[$canonical] ?? '';

        return self::fileUrl($stem);
    }

    private static function fileUrl(string $stem): string
    {
        $relative = self::resolvePath($stem);
        if ($relative === '') {
            return '';
        }

        return get_theme_file_uri($relative);
    }

    /**
     * Prefer PNG (July 2026 V7), fall back to SVG (May 2026 V5).
     */
    private static function resolvePath(string $stem): string
    {
        if ($stem === '') {
            return '';
        }

        foreach (self::EXTS as $ext) {
            $relative = self::DIR . $stem . '.' . $ext;
            if (is_readable(get_theme_file_path($relative))) {
                return $relative;
            }
        }

        return '';
    }
}
