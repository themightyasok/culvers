<?php

namespace App\Services;

/**
 * Component Cache Service
 *
 * Handles caching of component registry with invalidation
 */
class ComponentCache
{
    /** Cache key for component registry (bump version to invalidate stale/empty cache) */
    private const CACHE_KEY = 'culvers_theme_components_v10';

    /** Cache group name */
    private const CACHE_GROUP = 'components';

    /** Default cache expiry in seconds (1 hour) */
    private const DEFAULT_EXPIRY = 3600;

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
