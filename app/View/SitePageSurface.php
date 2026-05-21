<?php

declare(strict_types=1);

namespace App\View;

/**
 * Figma page-surface tessellation for {@see resources/views/layouts/app.blade.php} `#app`.
 *
 * @phpstan-type SurfaceConfig array{
 *   modifier: string,
 *   tile: string,
 *   tile_height: int,
 * }
 */
final class SitePageSurface
{
    private const TILE_DIR = 'resources/images/page/';

    /**
     * @return SurfaceConfig
     */
    public static function config(): array
    {
        if (is_front_page()) {
            return [
                'modifier' => 'site-page-surface--home',
                'tile' => 'page-background-tile-home.svg',
                'tile_height' => 2954,
            ];
        }

        if (is_page('guest-services')) {
            return [
                'modifier' => 'site-page-surface--guest-services',
                'tile' => 'page-background-tile-guest-services.svg',
                'tile_height' => 2690,
            ];
        }

        return [
            'modifier' => 'site-page-surface--standard',
            'tile' => 'page-background-tile-standard.svg',
            'tile_height' => 2954,
        ];
    }

    public static function modifierClass(): string
    {
        return self::config()['modifier'];
    }

    public static function tileUri(): string
    {
        $tile = self::config()['tile'];

        return get_theme_file_uri(self::TILE_DIR . $tile);
    }

    /**
     * Tile height ÷ Figma frame width (1440) — used for responsive background-size.
     */
    public static function tileHeightRatio(): string
    {
        $height = self::config()['tile_height'];

        return number_format($height / 1440, 6, '.', '');
    }
}
