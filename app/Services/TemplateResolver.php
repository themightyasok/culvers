<?php

namespace App\Services;

/**
 * Template Resolver Service
 *
 * Resolves component template paths with fallback support.
 * Uses singleton pattern for shared instance and caching.
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

    /**
     * Get template name for Blade @include directive
     *
     * @param string $componentName Component name/key
     * @return string Blade template name (e.g., "components.header-text")
     */
    public function getTemplateName(string $componentName): string
    {
        $templatePath = $this->resolve($componentName);

        if ($templatePath) {
            $templateName = str_replace('_', '-', $componentName);
            return 'components.' . $templateName;
        }

        // Fallback
        return 'components.' . str_replace('_', '-', $componentName);
    }

    /**
     * Add custom template search path
     *
     * @param string $path Absolute path to template directory
     * @return void
     */
    public function addPath(string $path): void
    {
        if (! in_array($path, $this->paths)) {
            array_unshift($this->paths, $path);
            // Clear cache when paths change
            $this->resolvedCache = [];
        }
    }

    /**
     * Clear template path resolution cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->resolvedCache = [];
    }
}
