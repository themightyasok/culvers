<?php

declare(strict_types=1);

if (! file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
    wp_die(
        esc_html__('Please run composer install in wp-content/themes/culvers.', 'culvers'),
        esc_html__('Theme bootstrap error', 'culvers'),
        ['response' => 500]
    );
}

require $composer;

require_once __DIR__ . '/app/blade-instance.php';

if (! function_exists('e')) {
    /**
     * HTML-escape a string for Blade `{!! nl2br(e(...)) !!}` and Laravel-style templates.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
    }
}

if (! function_exists('blade')) {
    /**
     * @param array<string, mixed> $data
     */
    function blade(string $template, array $data = []): string
    {
        try {
            if (function_exists('wp_bladeone')) {
                return (string) wp_bladeone()->run($template, $data);
            }

            return culvers_blade()->run($template, $data);
        } catch (\Throwable $e) {
            error_log('[culvers][blade] ' . $e->getMessage());

            return '';
        }
    }
}

if (! function_exists('blade_view')) {
    /**
     * @param array<string, mixed> $data
     */
    function blade_view(string $template, array $data = []): void
    {
        try {
            if (function_exists('wp_bladeone')) {
                echo wp_bladeone()->run($template, $data);
                return;
            }

            echo culvers_blade()->run($template, $data);
        } catch (\Throwable $e) {
            error_log('[culvers][blade_view] ' . $e->getMessage());
            /* Show the message in the rendered page on local only — `WP_DEBUG` is often on
               in staging, where the error trace would leak internal paths to anyone with
               the URL. Keep `error_log` always-on so the message still reaches the log. */
            if (function_exists('wp_get_environment_type') && wp_get_environment_type() === 'local') {
                echo '<pre>' . esc_html($e->getMessage()) . '</pre>';
            }
        }
    }
}

if (! function_exists('blade_component')) {
    /**
     * @param array<string, mixed> $data
     */
    function blade_component(string $componentName, array $data = []): void
    {
        $template = 'components.' . str_replace('_', '-', $componentName);
        blade_view($template, $data);
    }
}

$setupPath = locate_template('app/setup.php');
if ($setupPath) {
    require_once $setupPath;
}

add_action('wp', static function (): void {
    $title = \App\Support\DocumentTitle::current();
    if (function_exists('wp_bladeone')) {
        wp_bladeone()->share('title', $title);

        return;
    }

    culvers_blade()->share('title', $title);
});
