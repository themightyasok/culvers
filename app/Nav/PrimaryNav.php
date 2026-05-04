<?php

declare(strict_types=1);

namespace App\Nav;

/**
 * Builds a nested tree from a WordPress nav menu location for the Culver mega header.
 * Uses menu-item post meta so static analysis understands the data shape.
 *
 * @phpstan-type NavChild array{title: string, url: string, preview: string}
 * @phpstan-type NavBranch array{id: int, title: string, url: string, children: list<NavChild>}
 */
final class PrimaryNav
{
    /**
     * @return list<NavBranch>
     */
    public static function tree(string $location): array
    {
        $locs = get_nav_menu_locations();
        if (! isset($locs[$location])) {
            return [];
        }

        $menu = wp_get_nav_menu_object($locs[$location]);
        if (! $menu instanceof \WP_Term) {
            return [];
        }

        $items = wp_get_nav_menu_items($menu->term_id);
        if (! is_array($items) || $items === []) {
            return [];
        }

        /** @var list<\WP_Post> $items */
        $byParent = [];
        foreach ($items as $post) {
            $pid = self::parentId($post->ID);
            if (! isset($byParent[$pid])) {
                $byParent[$pid] = [];
            }
            $byParent[$pid][] = $post;
        }

        if (! isset($byParent[0])) {
            return [];
        }

        $tree = [];
        foreach ($byParent[0] as $parentPost) {
            $tree[] = self::branch($parentPost, $byParent);
        }

        return $tree;
    }

    /**
     * @param array<int, list<\WP_Post>> $byParent
     *
     * @return NavBranch
     */
    private static function branch(\WP_Post $parentPost, array $byParent): array
    {
        $children = [];
        $childPosts = $byParent[$parentPost->ID] ?? [];
        foreach ($childPosts as $childPost) {
            $children[] = [
                'title' => self::title($childPost),
                'url' => self::resolvedUrl($childPost),
                'preview' => self::previewUrl($childPost),
            ];
        }

        return [
            'id' => $parentPost->ID,
            'title' => self::title($parentPost),
            'url' => self::resolvedUrl($parentPost),
            'children' => $children,
        ];
    }

    private static function parentId(int $menuItemPostId): int
    {
        return (int) get_post_meta($menuItemPostId, '_menu_item_menu_item_parent', true);
    }

    private static function title(\WP_Post $post): string
    {
        return $post->post_title !== '' ? $post->post_title : '';
    }

    private static function resolvedUrl(\WP_Post $post): string
    {
        $custom = (string) get_post_meta($post->ID, '_menu_item_url', true);
        if ($custom !== '') {
            return esc_url($custom);
        }

        $type = (string) get_post_meta($post->ID, '_menu_item_type', true);
        $objectId = (int) get_post_meta($post->ID, '_menu_item_object_id', true);

        if ($type === 'post_type' && $objectId > 0) {
            $link = get_permalink($objectId);

            return is_string($link) ? esc_url($link) : '';
        }

        if ($type === 'taxonomy' && $objectId > 0) {
            $object = (string) get_post_meta($post->ID, '_menu_item_object', true);
            $termLink = get_term_link((int) $objectId, $object);
            if (! is_wp_error($termLink)) {
                return esc_url((string) $termLink);
            }
        }

        return '';
    }

    private static function previewUrl(\WP_Post $item): string
    {
        $remote = (string) get_post_meta($item->ID, NavMenuItemMeta::META_PREVIEW_URL, true);
        $remote = trim($remote);
        if ($remote !== '' && filter_var($remote, FILTER_VALIDATE_URL)) {
            return esc_url($remote);
        }

        $aid = (int) get_post_meta($item->ID, NavMenuItemMeta::META_PREVIEW_ATTACHMENT, true);
        if ($aid > 0) {
            $u = wp_get_attachment_image_url($aid, 'large');
            if (is_string($u) && $u !== '') {
                return esc_url($u);
            }
        }

        $objectId = (int) get_post_meta($item->ID, '_menu_item_object_id', true);
        $type = (string) get_post_meta($item->ID, '_menu_item_type', true);
        if ($type === 'post_type' && $objectId > 0) {
            $tid = (int) get_post_thumbnail_id($objectId);
            if ($tid > 0) {
                $u = wp_get_attachment_image_url($tid, 'large');
                if (is_string($u) && $u !== '') {
                    return esc_url($u);
                }
            }
        }

        return '';
    }

    /**
     * First hover-preview image per mega parent (Alpine initial state for open panel).
     *
     * @return array<int, array{preview: string, alt: string}>
     */
    public static function megaPreviewDefaults(string $location): array
    {
        $out = [];
        foreach (self::tree($location) as $branch) {
            if ($branch['children'] === []) {
                continue;
            }
            $preview = '';
            $alt = '';
            foreach ($branch['children'] as $child) {
                if ($child['preview'] !== '') {
                    $preview = $child['preview'];
                    $alt = $child['title'];
                    break;
                }
            }
            $out[$branch['id']] = [
                'preview' => $preview,
                'alt' => $alt,
            ];
        }

        return $out;
    }
}
