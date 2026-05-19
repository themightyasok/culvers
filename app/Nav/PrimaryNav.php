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

        return self::dedupeMegaChildPreviewsInTree(self::moveShopBranchFirst(self::foldStrayShopCategoriesUnderShopBranch($tree)));
    }

    /**
     * @param list<NavBranch> $tree
     *
     * @return list<NavBranch>
     */
    private static function moveShopBranchFirst(array $tree): array
    {
        $shopLabel = __('Shop', 'culvers');
        $shop = null;
        $rest = [];

        foreach ($tree as $branch) {
            if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($branch['title'], $shopLabel)) {
                $shop = $branch;

                continue;
            }
            $rest[] = $branch;
        }

        if ($shop === null) {
            return $tree;
        }

        return array_merge([$shop], $rest);
    }

    /**
     * Shop directory categories belong under the Shop mega branch only. WordPress menus sometimes
     * hold them as duplicate top-level items; fold them into Shop here so the header never renders
     * them as bar pills (see mega-nav__top-item loop).
     *
     * @param list<NavBranch> $tree
     *
     * @return list<NavBranch>
     */
    private static function foldStrayShopCategoriesUnderShopBranch(array $tree): array
    {
        $shopLabel = __('Shop', 'culvers');
        $shopIndex = null;

        foreach ($tree as $i => $branch) {
            if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($branch['title'], $shopLabel)) {
                $shopIndex = $i;
                break;
            }
        }

        if ($shopIndex === null) {
            return $tree;
        }

        $shopBranch = $tree[$shopIndex];
        $mergedChildren = $shopBranch['children'];

        foreach ($tree as $i => $branch) {
            if ($i === $shopIndex) {
                continue;
            }
            if (! self::titleIsShopDirectoryMegaCategory($branch['title'])) {
                continue;
            }
            if (self::homeTitleMeansFrontPageLink($branch['title'], $branch['url'])) {
                continue;
            }
            if (self::shopChildTitlePresent($mergedChildren, $branch['title'])) {
                continue;
            }

            $url = self::canonicalShopDirectoryCategoryUrl($branch['title']);
            if ($url === '') {
                $url = $branch['url'];
            }

            $menuPost = get_post((int) $branch['id']);
            $preview = $menuPost instanceof \WP_Post ? self::previewUrl($menuPost) : '';
            if ($preview === '') {
                foreach ($branch['children'] as $sub) {
                    if ($sub['preview'] !== '') {
                        $preview = $sub['preview'];
                        break;
                    }
                }
            }

            $mergedChildren[] = [
                'title' => $branch['title'],
                'url' => $url,
                'preview' => $preview,
            ];
        }

        $shopBranch = [
            'id' => $shopBranch['id'],
            'title' => $shopBranch['title'],
            'url' => $shopBranch['url'],
            'children' => self::orderShopMegaChildren($mergedChildren),
        ];

        $out = [];
        foreach ($tree as $i => $branch) {
            if ($i === $shopIndex) {
                $out[] = $shopBranch;

                continue;
            }
            if (
                self::titleIsShopDirectoryMegaCategory($branch['title'])
                && ! self::homeTitleMeansFrontPageLink($branch['title'], $branch['url'])
            ) {
                continue;
            }
            $out[] = $branch;
        }

        return $out;
    }

    /**
     * When the mega submenu renders synthetic rows (folded Shop categories) or WP assigns the same
     * attachment to many items, hover previews would all look identical. Fill with distinct media URLs.
     *
     * @param  list<NavChild>  $children
     * @return list<NavChild>
     */
    private static function fillDistinctMegaPreviews(array $children): array
    {
        $pool = self::megaPreviewImageUrlPool();
        if ($pool === []) {
            return $children;
        }

        /** @var array<string, true> $seen */
        $seen = [];
        $cursor = 0;
        $out = [];

        foreach ($children as $child) {
            $url = trim($child['preview']);
            if ($url !== '' && ! isset($seen[$url])) {
                $seen[$url] = true;
                $out[] = $child;

                continue;
            }

            $picked = self::nextDistinctPoolUrl($pool, $seen, $cursor);
            if ($picked === null) {
                $out[] = $child;

                continue;
            }

            $seen[$picked] = true;
            $out[] = [
                'title' => $child['title'],
                'url' => $child['url'],
                'preview' => $picked,
            ];
        }

        return $out;
    }

    /**
     * @param  list<string>           $pool
     * @param  array<string, true>    $seen
     */
    private static function nextDistinctPoolUrl(array $pool, array &$seen, int &$cursor): ?string
    {
        $n = count($pool);
        for ($step = 0; $step < $n; $step++) {
            $idx = ($cursor + $step) % $n;
            $candidate = $pool[$idx];
            if (! isset($seen[$candidate])) {
                $cursor = ($idx + 1) % $n;

                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function megaPreviewImageUrlPool(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $q = new \WP_Query([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => 200,
            'orderby' => 'ID',
            'order' => 'DESC',
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        $urls = [];
        foreach ($q->posts as $aid) {
            $u = wp_get_attachment_image_url((int) $aid, 'large');
            if (is_string($u) && $u !== '') {
                $urls[] = esc_url($u);
            }
        }

        $cache = $urls;

        return $cache;
    }

    /**
     * @param  list<NavBranch>  $tree
     * @return list<NavBranch>
     */
    private static function dedupeMegaChildPreviewsInTree(array $tree): array
    {
        $out = [];
        foreach ($tree as $branch) {
            $kids = $branch['children'];
            if (count($kids) > 1) {
                $kids = self::fillDistinctMegaPreviews($kids);
            }
            $out[] = [
                'id' => $branch['id'],
                'title' => $branch['title'],
                'url' => $branch['url'],
                'children' => $kids,
            ];
        }

        return $out;
    }

    private static function titleIsShopDirectoryMegaCategory(string $title): bool
    {
        foreach (CulverSquareFigmaPrimaryMenu::shopMegaCategoryLinkRows() as $row) {
            if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($title, $row['title'])) {
                return true;
            }
        }

        return false;
    }

    private static function canonicalShopDirectoryCategoryUrl(string $title): string
    {
        foreach (CulverSquareFigmaPrimaryMenu::shopMegaCategoryLinkRows() as $row) {
            if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($title, $row['title'])) {
                return esc_url($row['url']);
            }
        }

        return '';
    }

    /**
     * @param list<NavChild> $children
     */
    private static function shopChildTitlePresent(array $children, string $title): bool
    {
        foreach ($children as $child) {
            if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($child['title'], $title)) {
                return true;
            }
        }

        return false;
    }

    /**
     * "Home" may mean the site front page; only fold homeware when the link targets the directory.
     */
    private static function homeTitleMeansFrontPageLink(string $title, string $branchUrl): bool
    {
        if (! CulverSquareFigmaPrimaryMenu::menuTitlesMatch($title, __('Home', 'culvers'))) {
            return false;
        }

        $trimmed = trim($branchUrl);
        if ($trimmed === '') {
            return false;
        }

        $siteHome = esc_url(home_url('/'));

        return rtrim($siteHome, '/') === rtrim($trimmed, '/');
    }

    /**
     * Match Figma / seed category order (Beauty, Fashion, …) with any extras last.
     *
     * @param list<NavChild> $children
     *
     * @return list<NavChild>
     */
    private static function orderShopMegaChildren(array $children): array
    {
        $remaining = $children;
        $sorted = [];

        foreach (CulverSquareFigmaPrimaryMenu::shopMegaCategoryLinkRows() as $row) {
            foreach ($remaining as $k => $ch) {
                if (CulverSquareFigmaPrimaryMenu::menuTitlesMatch($ch['title'], $row['title'])) {
                    $sorted[] = $ch;
                    unset($remaining[$k]);

                    break;
                }
            }
        }

        foreach ($remaining as $ch) {
            $sorted[] = $ch;
        }

        return $sorted;
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
