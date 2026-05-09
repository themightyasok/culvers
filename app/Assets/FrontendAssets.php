<?php

declare(strict_types=1);

namespace App\Assets;

use App\Nav\PrimaryNav;
use App\Support\ViteDevProbe;

/**
 * Registers theme CSS/JS for the public front-end and the block editor.
 *
 * Vite HMR when {@see CULVERS_USE_VITE} is set and the dev server responds; otherwise
 * built assets under `dist/` with fallbacks to `css/` / `js/` / root `app.css`.
 */
final class FrontendAssets
{
    /** Adobe Fonts (Typekit) — Canela must be added to this kit for heading text per brand guidelines. */
    private const ADOBE_TYPEKIT_URL = 'https://use.typekit.net/gqo7cfj.css';

    private const ADOBE_TYPEKIT_HANDLE = 'culvers-adobe-typekit';

    private const SCRIPT_HANDLE = 'culvers-scripts';

    private const STYLE_HANDLE = 'culvers-styles';

    /** BugHerd sidebar (public project key — safe to ship in HTML). */
    private const BUGHERD_HANDLE = 'culvers-bugherd';

    private const BUGHERD_SCRIPT_URL = 'https://www.bugherd.com/sidebarv2.js?apikey=vj2lspmxd5z7tpnd3zwkiw';

    private static bool $deferMainScript = false;

    /**
     * Stylesheet handles that must print before the theme bundle so Tailwind wins ties.
     *
     * `global-styles` carries theme.json CSS (including link typography). Depending on it keeps
     * dependency order predictable when WP registers it as a linked asset.
     *
     * @return list<string>
     */
    private static function frontStyleDependencies(): array
    {
        $deps = [self::ADOBE_TYPEKIT_HANDLE];

        if (function_exists('wp_theme_has_theme_json') && wp_theme_has_theme_json() && wp_style_is('global-styles', 'registered')) {
            $deps[] = 'global-styles';
        }

        return $deps;
    }

    private static function enqueueAdobeTypekit(): void
    {
        wp_enqueue_style(
            self::ADOBE_TYPEKIT_HANDLE,
            self::ADOBE_TYPEKIT_URL,
            [],
            null
        );
    }

    public static function register(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueueFront'], 100);
        add_filter('script_loader_tag', [self::class, 'filterDeferMainScript'], 10, 2);
        add_action('enqueue_block_editor_assets', [self::class, 'enqueueEditor'], 100);
    }

    public static function enqueueFront(): void
    {
        $theme_uri = get_template_directory_uri();
        $theme_path = get_template_directory();
        $version = (string) wp_get_theme()->get('Version');

        /** @var array<string, mixed> $theme_script_extra */
        $theme_script_extra = [
            'restSearchUrl' => rest_url('wp/v2/search'),
            'megaDefaults' => PrimaryNav::megaPreviewDefaults('primary_navigation'),
        ];

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
            $vite_probe_url = $vite_dev_url . '/wp-content/themes/culvers/resources/styles/app.css';
            $vite_running = ViteDevProbe::localUrlOk($vite_probe_url);
        }

        self::$deferMainScript = ! ($use_vite_hmr && $vite_running);

        if ($use_vite_hmr && $vite_running) {
            self::enqueueAdobeTypekit();
            wp_enqueue_style(
                self::STYLE_HANDLE,
                $vite_dev_url . '/wp-content/themes/culvers/resources/styles/app.css',
                self::frontStyleDependencies(),
                (string) time()
            );
            wp_enqueue_script(
                self::SCRIPT_HANDLE,
                $vite_dev_url . '/wp-content/themes/culvers/resources/scripts/app.js',
                [],
                (string) time(),
                true
            );
            wp_localize_script(self::SCRIPT_HANDLE, 'culversTheme', $theme_script_extra);

            self::enqueueBugHerd();

            return;
        }

        $css_path = null;
        $css_uri = null;
        if (file_exists($theme_path . '/dist/css/app.css')) {
            $css_path = $theme_path . '/dist/css/app.css';
            $css_uri = $theme_uri . '/dist/css/app.css';
        } elseif (file_exists($theme_path . '/css/app.css')) {
            $css_path = $theme_path . '/css/app.css';
            $css_uri = $theme_uri . '/css/app.css';
        } elseif (file_exists($theme_path . '/app.css')) {
            $css_path = $theme_path . '/app.css';
            $css_uri = $theme_uri . '/app.css';
        }
        if ($css_uri !== null) {
            self::enqueueAdobeTypekit();
            // $css_uri and $css_path are assigned together in each branch above.
            $ver = $is_local_runtime ? (string) time() : (string) filemtime((string) $css_path);
            wp_enqueue_style(self::STYLE_HANDLE, $css_uri, self::frontStyleDependencies(), $ver ?: $version);
        }

        if (file_exists($theme_path . '/dist/js/app.js')) {
            $js_path = $theme_path . '/dist/js/app.js';
            $ver_js = $is_local_runtime ? (string) time() : (string) filemtime($js_path);
            wp_enqueue_script(
                self::SCRIPT_HANDLE,
                $theme_uri . '/dist/js/app.js',
                [],
                $ver_js ?: $version,
                true
            );
            wp_localize_script(self::SCRIPT_HANDLE, 'culversTheme', $theme_script_extra);
        } elseif (file_exists($theme_path . '/js/app.js')) {
            $js_path = $theme_path . '/js/app.js';
            $ver_js = $is_local_runtime ? (string) time() : (string) filemtime($js_path);
            wp_enqueue_script(self::SCRIPT_HANDLE, $theme_uri . '/js/app.js', [], $ver_js ?: $version, true);
            wp_localize_script(self::SCRIPT_HANDLE, 'culversTheme', $theme_script_extra);
        }

        self::enqueueBugHerd();
    }

    /**
     * BugHerd task overlay — loads async in the footer. Disabled when the
     * `culvers_load_bugherd` filter returns false (e.g. production go-live).
     */
    private static function enqueueBugHerd(): void
    {
        if (! apply_filters('culvers_load_bugherd', true)) {
            return;
        }

        wp_enqueue_script(
            self::BUGHERD_HANDLE,
            self::BUGHERD_SCRIPT_URL,
            [],
            null,
            [
                'in_footer' => true,
                'strategy' => 'async',
            ]
        );
    }

    /**
     * Defer the main bundle in production so it does not block parsing (skipped for Vite HMR).
     */
    public static function filterDeferMainScript(string $tag, string $handle): string
    {
        if (! self::$deferMainScript || $handle !== self::SCRIPT_HANDLE) {
            return $tag;
        }

        return str_replace(' src', ' defer src', $tag);
    }

    public static function enqueueEditor(): void
    {
        $theme_uri = get_template_directory_uri();
        $theme_path = get_template_directory();
        $version = (string) wp_get_theme()->get('Version');

        if (file_exists($theme_path . '/resources/styles/editor.css')) {
            self::enqueueAdobeTypekit();
            wp_enqueue_style('culvers-editor', $theme_uri . '/resources/styles/editor.css', [self::ADOBE_TYPEKIT_HANDLE], $version);
        }
    }
}
