<?php

/**
 * Embedded Blade engine (theme-local BladeOne; avoids a separate WP BladeOne plugin when unused).
 */

declare(strict_types=1);

use eftec\bladeone\BladeOne;

if (! function_exists('culvers_blade')) {
    function culvers_blade(): BladeOne
    {
        static $blade = null;

        if ($blade instanceof BladeOne) {
            return $blade;
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

        return $blade;
    }
}
