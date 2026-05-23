<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Resolves eat & drink IDs for the "More flavours to discover" row.
 */
final class RelatedEatDrinkPostIds
{
    /**
     * Random published venues for eat & drink singles (always attempts {@see $count} picks).
     *
     * @return list<int>
     */
    public static function randomPublished(int $excludePostId = 0, int $count = 4): array
    {
        if ($count <= 0) {
            return [];
        }

        $notIn = $excludePostId > 0 ? [$excludePostId] : [];

        $query = new \WP_Query([
            'post_type' => 'culvers_eat_drink',
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'post__not_in' => $notIn,
            'orderby' => 'rand',
            'fields' => 'ids',
            'suppress_filters' => true,
            'no_found_rows' => true,
        ]);

        /** @var list<int> $ids */
        $ids = [];
        foreach ($query->posts as $postId) {
            $id = (int) $postId;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
