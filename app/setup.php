<?php

/**
 * Theme bootstrap: assets, supports, menus, ACF.
 */

declare(strict_types=1);

namespace App;

add_action('wp_enqueue_scripts', static function (): void {
    $theme_uri = get_template_directory_uri();
    $theme_path = get_template_directory();
    $version = (string) wp_get_theme()->get('Version');

    $use_vite_hmr = defined('CULVERS_USE_VITE') && constant('CULVERS_USE_VITE');
    $environment_type = function_exists('wp_get_environment_type') ? wp_get_environment_type() : 'production';
    $home_host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
    $is_local_host = in_array($home_host, ['localhost', '127.0.0.1', '::1'], true)
        || (bool) preg_match('/(\.local|\.test)$/', $home_host);
    $is_local_runtime = (defined('WP_DEBUG') && WP_DEBUG)
        || in_array($environment_type, ['local', 'development'], true)
        || $is_local_host;

    $vite_dev_url = 'http://localhost:5173';
    $vite_running = false;

    if ($use_vite_hmr && $is_local_runtime) {
        $check = $vite_dev_url . '/wp-content/themes/culvers/resources/styles/app.css';
        $parsed = wp_parse_url($check);
        if (isset($parsed['host']) && in_array($parsed['host'], ['localhost', '127.0.0.1', '::1'], true)) {
            $ctx = stream_context_create([
                'http' => ['timeout' => 0.5, 'ignore_errors' => true, 'method' => 'GET'],
            ]);
            $vite_running = @file_get_contents($check, false, $ctx) !== false;
        }
    }

    if ($use_vite_hmr && $vite_running) {
        wp_enqueue_style(
            'culvers-styles',
            $vite_dev_url . '/wp-content/themes/culvers/resources/styles/app.css',
            [],
            time()
        );
        wp_enqueue_script(
            'culvers-scripts',
            $vite_dev_url . '/wp-content/themes/culvers/resources/scripts/app.js',
            [],
            time(),
            true
        );
    } else {
        $css_path = null;
        $css_uri = null;
        if (file_exists($theme_path . '/css/app.css')) {
            $css_path = $theme_path . '/css/app.css';
            $css_uri = $theme_uri . '/css/app.css';
        } elseif (file_exists($theme_path . '/app.css')) {
            $css_path = $theme_path . '/app.css';
            $css_uri = $theme_uri . '/app.css';
        }
        if ($css_path !== null && $css_uri !== null) {
            $ver = $is_local_runtime ? (string) time() : (string) filemtime($css_path);
            wp_enqueue_style('culvers-styles', $css_uri, [], $ver ?: $version);
        }

        if (file_exists($theme_path . '/js/app.js')) {
            $js_path = $theme_path . '/js/app.js';
            $ver_js = $is_local_runtime ? (string) time() : (string) filemtime($js_path);
            wp_enqueue_script('culvers-scripts', $theme_uri . '/js/app.js', [], $ver_js ?: $version, true);
        }
    }

    if (! ($use_vite_hmr && $vite_running)) {
        add_filter('script_loader_tag', static function (string $tag, string $handle): string {
            if ($handle === 'culvers-scripts') {
                return str_replace(' src', ' defer src', $tag);
            }

            return $tag;
        }, 10, 2);
    }
}, 100);

add_action('enqueue_block_editor_assets', static function (): void {
    $theme_uri = get_template_directory_uri();
    $theme_path = get_template_directory();
    $version = (string) wp_get_theme()->get('Version');

    if (file_exists($theme_path . '/resources/styles/editor.css')) {
        wp_enqueue_style('culvers-editor', $theme_uri . '/resources/styles/editor.css', [], $version);
    }
}, 100);

add_action('after_setup_theme', static function (): void {
    remove_theme_support('block-templates');
    remove_theme_support('core-block-patterns');

    register_nav_menus([
        'primary_navigation' => __('Primary navigation', 'culvers'),
        'footer_navigation' => __('Footer navigation', 'culvers'),
    ]);

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    add_theme_support('editor-styles');
    $td = get_template_directory();
    if (file_exists($td . '/css/app.css')) {
        add_editor_style('css/app.css');
    } elseif (file_exists($td . '/app.css')) {
        add_editor_style('app.css');
    }
}, 20);

add_action('acf/init', static function (): void {
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    try {
        new Fields();
    } catch (\Throwable $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[culvers][acf] ' . $e->getMessage());
        }
    }
}, 20);

add_filter('acf/settings/save_json', static fn (): string => get_stylesheet_directory() . '/acf-json');

add_filter(
    'acf/settings/load_json',
    static function (array $paths): array {
        $paths[] = get_stylesheet_directory() . '/acf-json';

        return $paths;
    }
);

add_filter('intermediate_image_sizes_advanced', static function (array $sizes): array {
    unset($sizes['medium'], $sizes['medium_large'], $sizes['large']);

    return $sizes;
});

add_action('init', static function (): void {
    remove_image_size('1536x1536');
    remove_image_size('2048x2048');
}, 99);
