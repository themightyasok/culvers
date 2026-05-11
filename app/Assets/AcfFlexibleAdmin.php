<?php

declare(strict_types=1);

namespace App\Assets;

final class AcfFlexibleAdmin
{
    public static function register(): void
    {
        add_action('acf/input/admin_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function enqueue(): void
    {
        $path = get_template_directory() . '/resources/styles/acf-flexible-admin.css';
        if (! is_readable($path)) {
            return;
        }

        wp_enqueue_style(
            'culvers-acf-flexible-admin',
            get_template_directory_uri() . '/resources/styles/acf-flexible-admin.css',
            ['acf-input'],
            (string) filemtime($path)
        );
    }
}
