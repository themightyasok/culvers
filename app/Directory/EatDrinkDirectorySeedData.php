<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Eat & Drink directory venues — Figma Food Directory Page (node 51:5462).
 *
 * @see scripts/eat-drink-directory-populate.php
 */
final class EatDrinkDirectorySeedData
{
    public const FIGMA_FRAME_NOTE = 'KoBl6rTY98YnvusBgKLx4A · Food Directory · node 51:5462';

    public const DEFAULT_HOURS_LINE = 'Open Today 9am - 5.30pm';

    /**
     * @return list<array{
     *     title: string,
     *     slug: string,
     *     logo_url: string|null,
     *     logo_theme_file: string|null,
     *     featured_url: string|null,
     *     type_slug: string
     * }>
     */
    public static function venues(): array
    {
        return [
            [
                'title' => 'Greggs Bakery',
                'slug' => 'greggs',
                'logo_url' => null,
                'logo_theme_file' => null,
                'featured_url' => null,
                'type_slug' => 'grab-go',
            ],
            [
                'title' => 'Toast Coffee',
                'slug' => 'toast-coffee',
                'logo_url' => null,
                'logo_theme_file' => null,
                'featured_url' => null,
                'type_slug' => 'cafes',
            ],
            [
                'title' => 'Subway',
                'slug' => 'subway',
                'logo_url' => null,
                'logo_theme_file' => 'subway-logo-hero.svg',
                'featured_url' => null,
                'type_slug' => 'grab-go',
            ],
            [
                'title' => 'Juicy Bar Vitality',
                'slug' => 'juicy-bar-vitality',
                'logo_url' => null,
                'logo_theme_file' => null,
                'featured_url' => null,
                'type_slug' => 'healthy-options',
            ],
            [
                'title' => "Godfrey's Creperie",
                'slug' => 'godfreys-creperie',
                'logo_url' => null,
                'logo_theme_file' => null,
                'featured_url' => null,
                'type_slug' => 'restaurants',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedSlugs(): array
    {
        return array_map(
            static fn (array $row): string => $row['slug'],
            self::venues()
        );
    }

    public static function themeSeedAssetPath(string $filename): string
    {
        return get_theme_file_path('resources/images/seeds/' . ltrim($filename, '/'));
    }
}
