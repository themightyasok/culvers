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
    private const CACHE_KEY = 'culvers_theme_components_v1';

    /** Cache group name */
    private const CACHE_GROUP = 'components';

    /** Default cache expiry in seconds (1 hour) */
    private const DEFAULT_EXPIRY = 3600;

    private string $cacheKey = self::CACHE_KEY;
    private int $cacheExpiry = self::DEFAULT_EXPIRY;

    /**
     * Get cached components
     */
    public function get(): ?array
    {
        $cached = wp_cache_get($this->cacheKey, self::CACHE_GROUP);

        // wp_cache_get returns false on miss, we return null for clarity
        if ($cached === false) {
            return null;
        }

        return $cached;
    }

    /**
     * Set cached components
     */
    public function set(array $components): bool
    {
        return wp_cache_set($this->cacheKey, $components, self::CACHE_GROUP, $this->cacheExpiry);
    }

    /**
     * Clear component cache
     */
    public function clear(): bool
    {
        return wp_cache_delete($this->cacheKey, self::CACHE_GROUP);
    }

    /**
     * Check if cache is valid
     */
    public function isValid(): bool
    {
        return $this->get() !== null;
    }

    /**
     * Set cache expiry time
     */
    public function setExpiry(int $seconds): void
    {
        $this->cacheExpiry = $seconds;
    }
}
