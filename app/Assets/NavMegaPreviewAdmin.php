<?php

declare(strict_types=1);

namespace App\Assets;

/**
 * Media-library picker for mega-menu hover previews on the Menus screen.
 */
final class NavMegaPreviewAdmin
{
    public static function register(): void
    {
        add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function enqueue(string $hook): void
    {
        if ($hook !== 'nav-menus.php') {
            return;
        }

        wp_enqueue_media();

        $scriptPath = get_template_directory() . '/resources/scripts/admin/nav-mega-preview.js';
        if (is_readable($scriptPath)) {
            wp_enqueue_script(
                'culvers-nav-mega-preview',
                get_template_directory_uri() . '/resources/scripts/admin/nav-mega-preview.js',
                ['jquery', 'media-upload', 'media-views'],
                (string) filemtime($scriptPath),
                true
            );

            wp_localize_script('culvers-nav-mega-preview', 'culversMegaPreview', [
                'i18n' => [
                    'select' => __('Mega menu preview image', 'culvers'),
                    'use' => __('Use image', 'culvers'),
                    'selectButton' => __('Select image', 'culvers'),
                    'changeButton' => __('Change image', 'culvers'),
                    'removeButton' => __('Remove image', 'culvers'),
                ],
            ]);
        }

        $stylePath = get_template_directory() . '/resources/styles/admin/nav-mega-preview.css';
        if (is_readable($stylePath)) {
            wp_enqueue_style(
                'culvers-nav-mega-preview',
                get_template_directory_uri() . '/resources/styles/admin/nav-mega-preview.css',
                [],
                (string) filemtime($stylePath)
            );
        }
    }
}
