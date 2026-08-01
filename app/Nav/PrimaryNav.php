<?php

declare(strict_types=1);

namespace App\Nav;

/**
 * Builds a nested tree from a WordPress nav menu location for the Culver mega header.
 * Renders the assigned menu as saved in WP (order, parents, URLs) — only dedupes identical
 * mega hover previews. Use {@see CulverSquareFigmaPrimaryMenu::rebuildAssignedPrimaryMenu()}
 * to seed the canonical Figma tree into the database.
 *
 * @phpstan-type NavChild array{title: string, url: string, preview: string}
 * @phpstan-type NavBranch array{id: int, title: string, url: string, children: list<NavChild>, is_current: bool}
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

        return self::dedupeMegaChildPreviewsInTree($tree);
    }

    /**
     * Pass-through — sibling mega previews may match when editors reuse a branch image.
     * Do not invent substitutes from the Media Library: that pulled “latest upload”
     * assets (logos, offer art) onto rows that had no fixed preview (e.g. Latest Offers).
     * Set a mega preview per submenu item in Appearance → Menus instead.
     *
     * @param  list<NavBranch>  $tree
     * @return list<NavBranch>
     */
    private static function dedupeMegaChildPreviewsInTree(array $tree): array
    {
        $out = [];
        foreach ($tree as $branch) {
            $out[] = self::withCurrentFlag([
                'id' => $branch['id'],
                'title' => $branch['title'],
                'url' => $branch['url'],
                'children' => $branch['children'],
            ]);
        }

        return $out;
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
            $preview = self::previewUrl($childPost);
            if ($preview === '') {
                $preview = CulverSquareFigmaPrimaryMenu::localAttachmentPreviewForMenuItems($parentPost, $childPost);
            }
            $children[] = [
                'title' => self::title($childPost),
                'url' => self::resolvedUrl($childPost),
                'preview' => $preview,
            ];
        }

        return self::withCurrentFlag([
            'id' => $parentPost->ID,
            'title' => self::title($parentPost),
            'url' => self::resolvedUrl($parentPost),
            'children' => $children,
        ]);
    }

    /**
     * @param array{id: int, title: string, url: string, children: list<NavChild>} $branch
     *
     * @return NavBranch
     */
    private static function withCurrentFlag(array $branch): array
    {
        return [
            'id' => $branch['id'],
            'title' => $branch['title'],
            'url' => $branch['url'],
            'children' => $branch['children'],
            'is_current' => self::branchIsCurrent($branch['title'], $branch['url'], $branch['children']),
        ];
    }

    /**
     * @param list<NavChild> $children
     */
    private static function branchIsCurrent(string $title, string $branchUrl, array $children): bool
    {
        if (self::branchMatchesQueriedSection($title)) {
            return true;
        }

        if (self::requestMatchesUrl($branchUrl)) {
            return true;
        }

        foreach ($children as $child) {
            if (self::requestMatchesUrl($child['url'])) {
                return true;
            }
        }

        return false;
    }

    private static function branchMatchesQueriedSection(string $title): bool
    {
        if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($title, __('Shop', 'culvers'))) {
            return is_post_type_archive('culvers_shop') || is_singular('culvers_shop');
        }

        if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($title, __('Eat & Drink', 'culvers'))) {
            return is_post_type_archive('culvers_eat_drink') || is_singular('culvers_eat_drink');
        }

        if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($title, __('Careers', 'culvers'))) {
            return is_post_type_archive('culvers_career') || is_singular('culvers_career');
        }

        if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($title, __("what's on", 'culvers'))) {
            return is_page('whats-on')
                || is_post_type_archive(['culvers_event', 'culvers_offer', 'culvers_news'])
                || is_singular(['culvers_event', 'culvers_offer', 'culvers_news']);
        }

        if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($title, __('Plan my visit', 'culvers'))) {
            return is_page(['plan-my-visit', 'accessible-guide']);
        }

        if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($title, __('Guest Services', 'culvers'))) {
            return is_page(['guest-services', 'contact']);
        }

        return false;
    }

    private static function requestMatchesUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || $url === '#') {
            return false;
        }

        $parsed = wp_parse_url($url);
        if (! is_array($parsed)) {
            return false;
        }

        $wantPath = isset($parsed['path']) ? untrailingslashit((string) $parsed['path']) : '';
        if ($wantPath === '') {
            $wantPath = '/';
        }

        /** @var array<string, string> $wantQuery */
        $wantQuery = [];
        if (! empty($parsed['query'])) {
            parse_str((string) $parsed['query'], $wantQuery);
        }

        $currentPath = self::currentRequestPath();
        if ($wantPath !== $currentPath) {
            return false;
        }

        foreach ($wantQuery as $key => $value) {
            if (! isset($_GET[$key]) || (string) $_GET[$key] !== (string) $value) {
                return false;
            }
        }

        return true;
    }

    private static function currentRequestPath(): string
    {
        global $wp;
        if (isset($wp) && $wp instanceof \WP) {
            $request = trim((string) $wp->request, '/');
            if ($request === '') {
                return '/';
            }

            return untrailingslashit('/' . $request);
        }

        $path = (string) wp_parse_url(home_url(add_query_arg([])), PHP_URL_PATH);
        $path = untrailingslashit($path);

        return $path === '' ? '/' : $path;
    }

    private static function parentId(int $menuItemPostId): int
    {
        return (int) get_post_meta($menuItemPostId, '_menu_item_menu_item_parent', true);
    }

    private static function title(\WP_Post $post): string
    {
        if ($post->post_title === '') {
            return '';
        }

        // WP nav items often store `&` as `&amp;`; decode once so Blade/Alpine show “Eat & Drink”.
        return html_entity_decode($post->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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

    /**
     * Resolved hover-preview URL for a nav menu item (meta → linked featured image).
     * Exposed for CLI / maintenance scripts.
     */
    public static function previewUrlForMenuItem(\WP_Post $item): string
    {
        return self::previewUrl($item);
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
