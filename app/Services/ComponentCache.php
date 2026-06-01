<?php

namespace App\Services;

/**
 * Component Cache Service
 *
 * Handles caching of component registry with invalidation on deploy (registry
 * source fingerprint) and theme activation.
 */
class ComponentCache
{
    /** Cache key for component registry (bump version to invalidate stale/empty cache) */
    private const CACHE_KEY = 'culvers_theme_components_v17';

    /** Cache group name */
    private const CACHE_GROUP = 'components';

    /** Default cache expiry in seconds (1 hour) */
    private const DEFAULT_EXPIRY = 3600;

    /** Stored fingerprint of registry PHP sources — auto-invalidates on file deploy */
    private const FINGERPRINT_OPTION = 'culvers_component_registry_fingerprint';

    /**
     * Clear cached layouts when component PHP changes (rsync deploy) or on first boot.
     * Hook from {@see \App\setup.php} on `after_setup_theme` before `acf/init`.
     */
    public static function invalidateIfRegistrySourcesChanged(): void
    {
        if (! function_exists('get_option') || ! function_exists('update_option')) {
            return;
        }

        $current = self::registrySourcesFingerprint();
        $stored = (string) get_option(self::FINGERPRINT_OPTION, '');

        if ($stored === $current) {
            return;
        }

        (new self())->clear();
        update_option(self::FINGERPRINT_OPTION, $current, false);
    }

    /**
     * @return string SHA-256 of registry-related file mtimes (deploy-sensitive).
     */
    public static function registrySourcesFingerprint(): string
    {
        $themeDir = function_exists('get_stylesheet_directory')
            ? get_stylesheet_directory()
            : '';

        if ($themeDir === '') {
            return '';
        }

        $paths = [
            $themeDir . '/app/ComponentRegistry.php',
            $themeDir . '/app/Config/ComponentPostTypes.php',
            $themeDir . '/app/Validators/FieldValidator.php',
        ];

        $componentFiles = glob($themeDir . '/app/Components/*.php') ?: [];
        sort($componentFiles);
        $paths = array_merge($paths, $componentFiles);

        $parts = [];
        foreach ($paths as $path) {
            if (! is_readable($path)) {
                continue;
            }
            $mtime = filemtime($path);
            $parts[] = basename($path) . ':' . ($mtime !== false ? (string) $mtime : '0');
        }

        sort($parts);

        return hash('sha256', implode("\n", $parts));
    }

    /**
     * Get cached components.
     *
     * @return array<string, mixed>|null
     */
    public function get(): ?array
    {
        $cached = wp_cache_get(self::CACHE_KEY, self::CACHE_GROUP);

        if ($cached === false || ! is_array($cached)) {
            return null;
        }

        return $cached;
    }

    /**
     * Set cached components.
     *
     * @param array<string, mixed> $components
     */
    public function set(array $components): bool
    {
        return wp_cache_set(self::CACHE_KEY, $components, self::CACHE_GROUP, self::DEFAULT_EXPIRY);
    }

    /**
     * Clear component cache
     */
    public function clear(): bool
    {
        return wp_cache_delete(self::CACHE_KEY, self::CACHE_GROUP);
    }
}
