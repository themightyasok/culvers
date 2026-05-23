<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Header search REST endpoint.
 *
 *   GET /wp-json/culvers/v1/search?q=<query>&per_page=<n>
 *
 * Returns a flat list of search results in the shape the header search
 * Alpine controller renders directly:
 *
 *   [
 *       { id, title, excerpt, url, type, subtype, subtypeLabel },
 *       ...
 *   ]
 *
 * The header design (Figma node `51:8146`) renders two lines per result —
 * **title** on top and a short **excerpt / supporting copy** beneath —
 * with the matched term re-weighted to Halyard Medium. The default
 * `/wp/v2/search` endpoint only returns the title, so we expose a small
 * dedicated route that joins title + sanitised excerpt without forcing
 * the client to fan out per-post fetches.
 *
 * Public endpoint — no nonce. Capped at 12 results per query. Cached for
 * 60 seconds per (query, per_page) pair so a typing user doesn't hammer
 * the DB.
 */
final class SearchEndpoint
{
    public const NAMESPACE = 'culvers/v1';

    public const ROUTE = '/search';

    private const CACHE_TTL_SECONDS = 60;

    public static function register(): void
    {
        register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods' => 'GET',
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true',
            'args' => [
                'q' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'per_page' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 8,
                    'minimum' => 1,
                    'maximum' => SearchService::RESULTS_MAX,
                ],
            ],
        ]);
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response
    {
        $query = trim((string) $request->get_param('q'));
        if ($query === '' || mb_strlen($query) < SearchService::MIN_QUERY_LENGTH) {
            return new \WP_REST_Response([], 200);
        }

        $perPage = (int) $request->get_param('per_page');
        if ($perPage < 1 || $perPage > SearchService::RESULTS_MAX) {
            $perPage = 8;
        }

        $cacheKey = 'culvers_search_' . md5($query . '|' . $perPage);
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return new \WP_REST_Response($cached, 200);
        }

        $results = SearchService::queryFormatted($query, $perPage);

        set_transient($cacheKey, $results, self::CACHE_TTL_SECONDS);

        return new \WP_REST_Response($results, 200);
    }
}
