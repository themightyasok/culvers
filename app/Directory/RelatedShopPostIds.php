<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Resolves shop IDs for the "More shops you might enjoy" row.
 */
final class RelatedShopPostIds
{
    /**
     * Random published shops for shop singles (always attempts {@see $count} picks).
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
            'post_type' => 'culvers_shop',
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

    /**
     * @param  mixed $raw  ACF `shops_related_posts` value.
     * @return list<int> Published `culvers_shop` IDs, excluding the current single.
     */
    public static function fromAcfValue(mixed $raw, int $excludePostId = 0, int $max = 4): array
    {
        $items = [];
        if ($raw instanceof \WP_Post) {
            $items = [$raw];
        } elseif (is_array($raw)) {
            $items = $raw;
        }

        $ids = [];
        foreach ($items as $item) {
            $id = self::resolvePostId($item);
            if ($id <= 0 || get_post_type($id) !== 'culvers_shop') {
                continue;
            }
            if ($excludePostId > 0 && $id === $excludePostId) {
                continue;
            }
            $ids[] = $id;
            if (count($ids) >= $max) {
                break;
            }
        }

        return $ids;
    }

    private static function resolvePostId(mixed $item): int
    {
        if ($item instanceof \WP_Post) {
            return (int) $item->ID;
        }

        if (is_numeric($item)) {
            return (int) $item;
        }

        if (is_array($item)) {
            return (int) ($item['ID'] ?? $item['id'] ?? 0);
        }

        return 0;
    }
}
