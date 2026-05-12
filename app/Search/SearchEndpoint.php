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

    private const RESULTS_MAX = 12;

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
                    'maximum' => self::RESULTS_MAX,
                ],
            ],
        ]);
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response
    {
        $query = trim((string) $request->get_param('q'));
        if ($query === '' || mb_strlen($query) < 2) {
            return new \WP_REST_Response([], 200);
        }

        $perPage = (int) $request->get_param('per_page');
        if ($perPage < 1 || $perPage > self::RESULTS_MAX) {
            $perPage = 8;
        }

        $cacheKey = 'culvers_search_' . md5($query . '|' . $perPage);
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return new \WP_REST_Response($cached, 200);
        }

        $postTypes = self::publicSearchablePostTypes();
        if ($postTypes === []) {
            return new \WP_REST_Response([], 200);
        }

        $posts = get_posts([
            's' => $query,
            'post_type' => $postTypes,
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'orderby' => 'relevance',
            'order' => 'DESC',
            'suppress_filters' => false,
            'no_found_rows' => true,
        ]);

        /** @var array<int, array{id:int,title:string,excerpt:string,url:string,type:string,subtype:string,subtypeLabel:string}> $results */
        $results = [];
        foreach ($posts as $post) {
            $results[] = self::format($post);
        }

        set_transient($cacheKey, $results, self::CACHE_TTL_SECONDS);

        return new \WP_REST_Response($results, 200);
    }

    /**
     * @return list<string>
     */
    private static function publicSearchablePostTypes(): array
    {
        $types = get_post_types([
            'public' => true,
            'exclude_from_search' => false,
        ], 'names');

        /** @var list<string> $list */
        $list = array_values(array_map('strval', $types));

        /*
         * Drop the "attachment" type — media library entries are noise in
         * the header search, even though WordPress marks them public.
         */
        return array_values(array_filter($list, static fn (string $t): bool => $t !== 'attachment'));
    }

    /**
     * @return array{id:int,title:string,excerpt:string,url:string,type:string,subtype:string,subtypeLabel:string}
     */
    private static function format(\WP_Post $post): array
    {
        $title = wp_strip_all_tags((string) get_the_title($post));
        $excerpt = self::resolveExcerpt($post);
        $typeObject = get_post_type_object($post->post_type);
        $subtypeLabel = $typeObject instanceof \WP_Post_Type
            ? (string) ($typeObject->labels->singular_name ?? $typeObject->name)
            : $post->post_type;

        return [
            'id' => (int) $post->ID,
            'title' => $title,
            'excerpt' => $excerpt,
            'url' => (string) get_permalink($post),
            'type' => 'post',
            'subtype' => (string) $post->post_type,
            'subtypeLabel' => $subtypeLabel,
        ];
    }

    private static function resolveExcerpt(\WP_Post $post): string
    {
        /*
         * Manual excerpt wins. Falls back to a short auto-excerpt off
         * post_content (strip shortcodes / tags, single line, ≤ 140 chars
         * with ellipsis), then finally the post-type singular label so
         * the second line is never blank.
         */
        $manual = trim((string) $post->post_excerpt);
        if ($manual !== '') {
            return self::truncate(wp_strip_all_tags($manual), 140);
        }

        $body = (string) $post->post_content;
        $body = strip_shortcodes($body);
        $body = wp_strip_all_tags($body);
        $body = trim(preg_replace('/\s+/u', ' ', $body) ?? '');
        if ($body !== '') {
            return self::truncate($body, 140);
        }

        $typeObject = get_post_type_object($post->post_type);

        return $typeObject instanceof \WP_Post_Type
            ? (string) ($typeObject->labels->singular_name ?? $typeObject->name)
            : $post->post_type;
    }

    private static function truncate(string $text, int $maxChars): string
    {
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $maxChars - 1)) . '…';
    }
}
