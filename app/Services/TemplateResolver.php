<?php

namespace App\Services;

/**
 * Template Resolver Service
 *
 * Resolves flexible layout keys to on-disk Blade partial paths under
 * `resources/views/components/`. The singleton is used from
 * `resources/views/partials/flexible-components.blade.php` only — not from
 * {@see ComponentRegistry} (registry caches ACF layout definitions only).
 *
 * @package App\Services
 */
class TemplateResolver
{
    /** @var self|null Singleton instance */
    private static ?self $instance = null;

    /** @var array<string> Template search paths */
    private array $paths = [];

    /** @var array<string, string|null> Cache of resolved template paths */
    private array $resolvedCache = [];

    private function __construct()
    {
        $this->paths = [
            get_template_directory() . '/resources/views/components',
        ];
    }

    /**
     * Get singleton instance
     *
     * @return self Template resolver instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Resolve template file path for component
     *
     * @param string $componentName Component name/key (e.g., "header_text")
     * @return string|null Absolute path to template file or null if not found
     */
    public function resolve(string $componentName): ?string
    {
        // Check cache first
        if (isset($this->resolvedCache[$componentName])) {
            return $this->resolvedCache[$componentName];
        }

        $templateName = str_replace('_', '-', $componentName);

        // Security: Sanitize template name to prevent directory traversal
        $templateName = basename($templateName);

        foreach ($this->paths as $path) {
            // Security: Validate path is within template directory
            $realPath = realpath($path);
            if (! $realPath) {
                continue;
            }

            $templatePath = $realPath . '/' . $templateName . '.blade.php';

            // Security: Double-check resolved path is still within allowed directory
            $realTemplatePath = realpath($templatePath);
            if ($realTemplatePath && str_starts_with($realTemplatePath, $realPath)) {
                if (file_exists($realTemplatePath)) {
                    $this->resolvedCache[$componentName] = $realTemplatePath;
                    return $realTemplatePath;
                }
            }
        }

        $this->resolvedCache[$componentName] = null;
        return null;
    }
}
