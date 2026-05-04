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

if (! function_exists('blade')) {
    /**
     * @param array<string, mixed> $data
     */
    function blade(string $template, array $data = []): string
    {
        if (! function_exists('wp_bladeone')) {
            return '';
        }

        try {
            return wp_bladeone()->run($template, $data);
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
        if (! function_exists('wp_bladeone')) {
            return;
        }

        try {
            echo wp_bladeone()->run($template, $data);
        } catch (\Throwable $e) {
            error_log('[culvers][blade_view] ' . $e->getMessage());
            if (defined('WP_DEBUG') && WP_DEBUG) {
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

foreach (['setup', 'filters'] as $file) {
    $path = locate_template("app/{$file}.php");
    if ($path) {
        require_once $path;
    }
}

add_action('wp', static function (): void {
    if (! function_exists('wp_bladeone')) {
        return;
    }

    wp_bladeone()->share('title', \App\culvers_document_title());
});
