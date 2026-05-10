<?php

declare(strict_types=1);

namespace App\Nav;

/**
 * Ensures sibling mega-menu items under the same parent each resolve to a distinct
 * hover preview when possible. Many installs assign one attachment to a whole column
 * (or leave URLs identical), so the right-rail image never appears to change on hover.
 */
final class MegaMenuDistinctPreviews
{
    /**
     * @param  non-empty-string  $location
     * @return int Number of submenu rows updated (attachment meta written)
     */
    public static function assignDistinctAttachmentPreviews(string $location = 'primary_navigation'): int
    {
        $locs = get_nav_menu_locations();
        if (! isset($locs[$location])) {
            return 0;
        }

        $menuId = (int) $locs[$location];
        if ($menuId <= 0) {
            return 0;
        }

        $items = wp_get_nav_menu_items($menuId);
        if (! is_array($items) || $items === []) {
            return 0;
        }

        /** @var array<int, list<\WP_Post>> $byParent */
        $byParent = [];
        foreach ($items as $item) {
            if (! $item instanceof \WP_Post) {
                continue;
            }
            $pid = (int) get_post_meta((int) $item->ID, '_menu_item_menu_item_parent', true);
            if ($pid === 0) {
                continue;
            }
            if (! isset($byParent[$pid])) {
                $byParent[$pid] = [];
            }
            $byParent[$pid][] = $item;
        }

        $pool = self::attachmentIdPool();
        if ($pool === []) {
            return 0;
        }

        $updated = 0;
        foreach ($byParent as $children) {
            /** @var array<string, true> $seenUrls */
            $seenUrls = [];
            $cursor = 0;

            foreach ($children as $item) {
                $url = PrimaryNav::previewUrlForMenuItem($item);
                if ($url !== '' && ! isset($seenUrls[$url])) {
                    $seenUrls[$url] = true;

                    continue;
                }

                $next = self::nextUnusedAttachmentId($pool, $seenUrls, $cursor);
                if ($next === null) {
                    break;
                }

                [$aid, $imgUrl] = $next;
                update_post_meta((int) $item->ID, NavMenuItemMeta::META_PREVIEW_ATTACHMENT, $aid);
                delete_post_meta((int) $item->ID, NavMenuItemMeta::META_PREVIEW_URL);
                $seenUrls[$imgUrl] = true;
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * @param  list<int>               $pool
     * @param  array<string, true>     $seenUrls
     * @return array{0: int, 1: string}|null
     */
    private static function nextUnusedAttachmentId(array $pool, array &$seenUrls, int &$cursor): ?array
    {
        $n = count($pool);
        for ($step = 0; $step < $n; $step++) {
            $idx = ($cursor + $step) % $n;
            $aid = $pool[$idx];
            $u = wp_get_attachment_image_url($aid, 'large');
            if (! is_string($u) || $u === '') {
                continue;
            }
            if (isset($seenUrls[$u])) {
                continue;
            }
            $cursor = ($idx + 1) % $n;

            return [$aid, $u];
        }

        return null;
    }

    /**
     * @return list<int>
     */
    private static function attachmentIdPool(): array
    {
        $q = new \WP_Query([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => 400,
            'orderby' => 'ID',
            'order' => 'DESC',
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        $ids = [];
        foreach ($q->posts as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
