<?php

declare(strict_types=1);

namespace App\CentreMap;

/**
 * Pre-rendered centre-map SVGs — one artwork per filter category (May 2026 V5 set).
 *
 * Files live under {@see self::DIR} with filenames matching category slugs
 * (see Plan my visit centre_map repeater + {@see DirectoryFilterDefinitions}).
 */
final class CentreMapFilterAssets
{
    private const DIR = 'resources/images/centre-map/filters/';

    private const EXT = 'svg';

    /** @var array<string, string> canonical slug => filename stem */
    private const FILES = [
        'standard' => 'standard',
        'shop-all' => 'shop-all',
        'beauty-wellbeing' => 'beauty-wellbeing',
        'fashion' => 'fashion',
        'jewellery' => 'jewellery',
        'toys-gifts' => 'toys-gifts',
        'technology' => 'technology',
        'services' => 'services',
        'home' => 'home',
        'eat-drink-all' => 'eat-drink-all',
        'grab-go' => 'grab-go',
        'restaurants' => 'restaurants',
        'healthy-options' => 'healthy-options',
        'cafes' => 'cafes',
        'toilets' => 'toilets',
    ];

    /** Legacy / alternate slugs editors may still use in ACF. */
    private const SLUG_ALIASES = [
        'all' => 'standard',
        'eat-drink' => 'eat-drink-all',
        'eat-drink-cafes' => 'cafes',
        'eat-drink-takeaway' => 'grab-go',
        'eat-drink-restaurants' => 'restaurants',
        'guest-services' => 'toilets',
        'guest-services-toilets' => 'toilets',
    ];

    public static function hasFilterMaps(): bool
    {
        return is_readable(get_theme_file_path(self::DIR . self::filename('standard')));
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

    private static function filename(string $stem): string
    {
        return $stem . '.' . self::EXT;
    }

    private static function fileUrl(string $stem): string
    {
        if ($stem === '') {
            return '';
        }

        $path = self::DIR . self::filename($stem);

        if (! is_readable(get_theme_file_path($path))) {
            return '';
        }

        return get_theme_file_uri($path);
    }
}
