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
     *     category_slug: string,
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
                'category_slug' => 'bakery',
                'type_slug' => 'takeaway',
            ],
            [
                'title' => 'Toast Coffee',
                'slug' => 'toast-coffee',
                'logo_url' => null,
                'logo_theme_file' => null,
                'featured_url' => null,
                'category_slug' => 'coffee-cake',
                'type_slug' => 'cafe',
            ],
            [
                'title' => 'Subway',
                'slug' => 'subway',
                'logo_url' => null,
                'logo_theme_file' => 'subway-logo-hero.svg',
                'featured_url' => null,
                'category_slug' => 'burgers-grill',
                'type_slug' => 'takeaway',
            ],
            [
                'title' => 'Juicy Bar Vitality',
                'slug' => 'juicy-bar-vitality',
                'logo_url' => null,
                'logo_theme_file' => null,
                'featured_url' => null,
                'category_slug' => 'healthy',
                'type_slug' => 'takeaway',
            ],
            [
                'title' => "Godfrey's Creperie",
                'slug' => 'godfreys-creperie',
                'logo_url' => null,
                'logo_theme_file' => null,
                'featured_url' => null,
                'category_slug' => 'sweet-treats',
                'type_slug' => 'restaurant',
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
