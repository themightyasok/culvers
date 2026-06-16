<?php

declare(strict_types=1);

namespace App\View;

use eftec\bladeone\BladeOne;

/**
 * Theme-local BladeOne instance.
 *
 * Lazily constructs a singleton scoped to {@see resolve()} so callers don't
 * have to track lifecycle or share state. Used as a fallback when the optional
 * `wp-bladeone` plugin isn't active (it isn't on local; it is on staging via
 * `WP_BLADEONE_VIEWS` / `WP_BLADEONE_CACHE` in `wp-config.php`).
 */
final class BladeInstance
{
    private static ?BladeOne $instance = null;

    public static function resolve(): BladeOne
    {
        if (self::$instance instanceof BladeOne) {
            return self::$instance;
        }

        $views = get_template_directory() . '/resources/views';
        $cache = get_template_directory() . '/storage/cache';

        if (! is_dir($cache) && ! wp_mkdir_p($cache)) {
            wp_die(
                esc_html__('Could not create storage/cache for Blade templates. Check theme directory permissions.', 'culvers'),
                esc_html__('Theme bootstrap error', 'culvers'),
                ['response' => 500]
            );
        }

        $blade = new BladeOne($views, $cache, BladeOne::MODE_AUTO);
        // Prevent @include argument keys from persisting into later templates (flexible rows, etc.).
        $blade->includeScope = true;

        self::$instance = $blade;

        return $blade;
    }
}
