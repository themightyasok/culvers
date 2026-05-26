<?php

declare(strict_types=1);

namespace App\CentreMap;

/**
 * Pre-rendered centre-map SVGs — one artwork per filter category.
 *
 * Files live under {@see self::DIR} with filenames matching category slugs
 * (see Plan my visit centre_map repeater + {@see DirectoryFilterDefinitions}).
 */
final class CentreMapFilterAssets
{
    private const DIR = 'resources/images/centre-map/filters/';

    /** @var array<string, string> canonical slug => filename */
    private const FILES = [
        'standard' => 'standard.svg',
        'shop-all' => 'shop-all.svg',
        'beauty-wellbeing' => 'beauty-wellbeing.svg',
        'fashion' => 'fashion.svg',
        'jewellery' => 'jewellery.svg',
        'toys-gifts' => 'toys-gifts.svg',
        'technology' => 'technology.svg',
        'services' => 'services.svg',
        'home' => 'home.svg',
        'eat-drink-all' => 'eat-drink-all.svg',
        'grab-go' => 'grab-go.svg',
        'restaurants' => 'restaurants.svg',
        'healthy-options' => 'healthy-options.svg',
        'cafes' => 'cafes.svg',
    ];

    /** Legacy / alternate slugs editors may still use in ACF. */
    private const SLUG_ALIASES = [
        'all' => 'standard',
        'eat-drink' => 'eat-drink-all',
        'eat-drink-cafes' => 'cafes',
        'eat-drink-takeaway' => 'grab-go',
        'eat-drink-restaurants' => 'restaurants',
    ];

    public static function hasFilterMaps(): bool
    {
        return is_readable(get_theme_file_path(self::DIR . self::FILES['standard']));
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

        foreach (self::FILES as $slug => $filename) {
            if ($slug === 'standard') {
                continue;
            }

            $url = self::fileUrl($filename);
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
        $filename = self::FILES[$canonical] ?? '';

        return self::fileUrl($filename);
    }

    private static function fileUrl(string $filename): string
    {
        if ($filename === '') {
            return '';
        }

        $path = self::DIR . $filename;

        if (! is_readable(get_theme_file_path($path))) {
            return '';
        }

        return get_theme_file_uri($path);
    }
}
