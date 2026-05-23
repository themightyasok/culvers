<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Aligns the front-end search results query with the header predictive
 * search (same public post types, sensible page size).
 */
final class MainSearch
{
    private const RESULTS_PER_PAGE = 24;

    public static function register(): void
    {
        add_action('pre_get_posts', [self::class, 'adjustMainSearchQuery'], 10);
    }

    public static function adjustMainSearchQuery(\WP_Query $query): void
    {
        if (is_admin() || ! $query->is_main_query() || ! $query->is_search()) {
            return;
        }

        $postTypes = SearchService::publicSearchablePostTypes();
        if ($postTypes !== []) {
            $query->set('post_type', $postTypes);
        }

        $query->set('posts_per_page', self::RESULTS_PER_PAGE);
    }
}
