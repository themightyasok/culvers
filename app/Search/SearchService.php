<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Shared site search query + result shaping for the header REST endpoint
 * and the public search results template.
 */
final class SearchService
{
    public const MIN_QUERY_LENGTH = 2;

    public const RESULTS_MAX = 12;

    /**
     * @return list<\WP_Post>
     */
    public static function queryPosts(string $search, int $perPage): array
    {
        $search = trim($search);
        if ($search === '' || mb_strlen($search) < self::MIN_QUERY_LENGTH) {
            return [];
        }

        $perPage = max(1, min($perPage, self::RESULTS_MAX));
        $postTypes = self::publicSearchablePostTypes();
        if ($postTypes === []) {
            return [];
        }

        /** @var list<\WP_Post> $posts */
        $posts = get_posts([
            's' => $search,
            'post_type' => $postTypes,
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'orderby' => 'relevance',
            'order' => 'DESC',
            'suppress_filters' => false,
            'no_found_rows' => true,
        ]);

        return $posts;
    }

    /**
     * @return list<array{id:int,title:string,excerpt:string,url:string,type:string,subtype:string,subtypeLabel:string}>
     */
    public static function queryFormatted(string $search, int $perPage): array
    {
        $results = [];
        foreach (self::queryPosts($search, $perPage) as $post) {
            $results[] = self::format($post);
        }

        return $results;
    }

    /**
     * @return list<string>
     */
    public static function publicSearchablePostTypes(): array
    {
        $types = get_post_types([
            'public' => true,
            'exclude_from_search' => false,
        ], 'names');

        /** @var list<string> $list */
        $list = array_values(array_map('strval', $types));

        return array_values(array_filter($list, static fn (string $t): bool => $t !== 'attachment'));
    }

    /**
     * @return array{id:int,title:string,excerpt:string,url:string,type:string,subtype:string,subtypeLabel:string}
     */
    public static function format(\WP_Post $post): array
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
