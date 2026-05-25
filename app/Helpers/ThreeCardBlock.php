<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Directory\DirectoryCardImage;

/**
 * Flexible layout `three_card_block`: cards from directory CPT queries or blog categories.
 */
final class ThreeCardBlock
{
    /** @var list<string> */
    private const SUPPORTED_CPTS = [
        'culvers_event',
        'culvers_offer',
        'culvers_news',
        'culvers_shop',
        'culvers_eat_drink',
        'culvers_career',
    ];

    /**
     * Human-readable tab labels for each supported CPT. Kept inline (rather than reading
     * `WP_Post_Type::labels->name`) so the toggle pills stay short ("News" not "Latest News").
     *
     * @var array<string, string>
     */
    private const CPT_TAB_LABELS = [
        'culvers_event' => 'Events',
        'culvers_offer' => 'Offers',
        'culvers_news' => 'News',
        'culvers_shop' => 'Shops',
        'culvers_eat_drink' => 'Eat & Drink',
        'culvers_career' => 'Careers',
    ];

    /**
     * Normalize the CPT picker payload (legacy single-string OR new array). Order is preserved.
     *
     * @return list<string>
     */
    private static function normalizeCptList(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = $raw === '' ? [] : [$raw];
        }
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }
            if (! in_array($value, self::SUPPORTED_CPTS, true)) {
                continue;
            }
            if (in_array($value, $out, true)) {
                continue;
            }
            $out[] = $value;
        }

        return $out;
    }

    /**
     * Structural normalization — coerces legacy manual rows to CPT queries.
     *
     * @param  array<string, mixed>  $component
     * @return array<string, mixed>
     */
    public static function applyEditorFallback(array $component): array
    {
        $source = (string) ($component['cards_source'] ?? 'cpt');

        if ($source === 'manual') {
            $component = self::coerceLegacyManualToCpt($component);
            $source = 'cpt';
        }

        if ($source !== 'blog' && $source !== 'cpt') {
            $component['cards_source'] = 'cpt';
            if (self::normalizeCptList($component['cards_cpt_post_type'] ?? null) === []) {
                $component['cards_cpt_post_type'] = ['culvers_news', 'culvers_event', 'culvers_offer'];
                $component['cards_cpt_count'] = $component['cards_cpt_count'] ?? 3;
            }
        } else {
            $component['cards_source'] = $source;
        }

        unset($component['cards_items']);

        return $component;
    }

    /**
     * @param  array<string, mixed>  $component
     * @return array<string, mixed>
     */
    private static function coerceLegacyManualToCpt(array $component): array
    {
        $component['cards_source'] = 'cpt';
        $component['cards_cpt_count'] = (int) ($component['cards_cpt_count'] ?? 3);
        if ($component['cards_cpt_count'] < 1) {
            $component['cards_cpt_count'] = 3;
        }

        if (self::normalizeCptList($component['cards_cpt_post_type'] ?? null) !== []) {
            return $component;
        }

        $component['cards_cpt_post_type'] = [self::inferLegacyManualCpt($component)];

        if (trim((string) ($component['cards_view_all_url'] ?? '')) === '') {
            $archive = get_post_type_archive_link($component['cards_cpt_post_type'][0]);
            if (is_string($archive) && $archive !== '') {
                $component['cards_view_all_url'] = $archive;
            }
        }

        return $component;
    }

    /**
     * @param  array<string, mixed>  $component
     */
    private static function inferLegacyManualCpt(array $component): string
    {
        $heading = mb_strtolower(trim((string) ($component['cards_heading'] ?? '')));

        if (str_contains($heading, 'offer')) {
            return 'culvers_offer';
        }
        if (str_contains($heading, 'event')) {
            return 'culvers_event';
        }
        if (str_contains($heading, 'news')) {
            return 'culvers_news';
        }
        if (str_contains($heading, 'shop')) {
            return 'culvers_shop';
        }
        if (str_contains($heading, 'eat') || str_contains($heading, 'drink')) {
            return 'culvers_eat_drink';
        }
        if (str_contains($heading, 'career')) {
            return 'culvers_career';
        }

        foreach (is_array($component['cards_items'] ?? null) ? $component['cards_items'] : [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $url = mb_strtolower(trim((string) ($row['card_url'] ?? '')));
            if (str_contains($url, '/latest-offers')) {
                return 'culvers_offer';
            }
            if (str_contains($url, '/latest-events')) {
                return 'culvers_event';
            }
            if (str_contains($url, '/latest-news')) {
                return 'culvers_news';
            }
            if (str_contains($url, '/shops')) {
                return 'culvers_shop';
            }
            if (str_contains($url, '/eat-drink') || str_contains($url, '/dining')) {
                return 'culvers_eat_drink';
            }
        }

        return 'culvers_offer';
    }

    /**
     * Black overlay on card media (image or video). 0 = no overlay.
     *
     * @param  array<string, mixed>  $component
     */
    public static function mediaOverlayOpacity(array $component): int
    {
        return Component::overlayOpacityPercent($component['cards_media_overlay_opacity'] ?? null, 25);
    }

    /**
     * Resolve the View all URL — defaults to the chosen CPT's archive URL
     * when the editor leaves it blank in CPT mode (so a Latest Events
     * three_card_block on the What's On landing automatically links to
     * /latest-events/ without manual wiring).
     *
     * @param  array<string, mixed>  $component
     */
    public static function viewAllUrl(array $component): string
    {
        $explicit = trim((string) ($component['cards_view_all_url'] ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        $source = (string) ($component['cards_source'] ?? 'cpt');
        if ($source !== 'cpt') {
            return '';
        }

        $list = self::normalizeCptList($component['cards_cpt_post_type'] ?? null);
        if ($list === []) {
            return '';
        }

        /* Multi-CPT toggle (e.g. News / Events / Offers) has no single "View all" — each
           tab already deep-links its own archive via card permalinks, and a single button
           below would be ambiguous. Suppress unless the editor supplied an explicit URL. */
        if (count($list) > 1) {
            return '';
        }

        $url = get_post_type_archive_link($list[0]);

        return is_string($url) ? $url : '';
    }

    /**
     * Tab panels for Blade: blog → one tab per category; CPT → one tab per directory.
     *
     * @param  array<string, mixed>  $component  Already sanitized + fallback merged.
     * @return list<array{label: string, slug: string, cards: list<array<string, mixed>>}>
     */
    public static function buildTabPanels(array $component): array
    {
        $source = (string) ($component['cards_source'] ?? 'cpt');

        if ($source === 'blog') {
            return self::blogTabPanels($component);
        }

        return self::cptTabPanels($component);
    }

    /**
     * One tab panel per selected directory CPT (events / offers / news / shops /
     * eat-drink / careers). Title + post thumbnail render inside the existing
     * big-card layout, so the landing-page strips visually match Figma — overlay
     * style on a tall image — without forcing every CPT row to use the moss-tile
     * directory card pattern (which is reserved for the archive grids).
     *
     * Single-CPT selection still produces a single un-labelled panel (tabs hide
     * when there's only one). Multi-CPT selection produces a labelled tab per
     * CPT, e.g. the homepage "What are you looking for today?" News/Events/Offers
     * toggle.
     *
     * @param  array<string, mixed>  $component
     * @return list<array{label: string, slug: string, cards: list<array<string, mixed>>}>
     */
    private static function cptTabPanels(array $component): array
    {
        $list = self::normalizeCptList($component['cards_cpt_post_type'] ?? null);
        if ($list === []) {
            return [];
        }

        $perPage = (int) ($component['cards_cpt_count'] ?? 3);
        if ($perPage < 1) {
            $perPage = 3;
        }
        if ($perPage > 12) {
            $perPage = 12;
        }

        $isMulti = count($list) > 1;
        $panels = [];

        foreach ($list as $postType) {
            /* CPTs sort newest-first by default (matches the archive query
               hooks in DirectoryPostTypes::adjustArchiveQueries) so a "Latest
               X" strip surfaces the freshest items without extra config. */
            $orderby = 'date';
            $order = 'DESC';
            if ($postType === 'culvers_shop' || $postType === 'culvers_eat_drink' || $postType === 'culvers_career') {
                $orderby = 'title';
                $order = 'ASC';
            }

            $notIn = [];
            if (is_singular($postType)) {
                $currentId = (int) get_queried_object_id();
                if ($currentId > 0) {
                    $notIn[] = $currentId;
                }
            }

            $query = new \WP_Query([
                'post_type' => $postType,
                'post_status' => 'publish',
                'posts_per_page' => $perPage,
                'orderby' => $orderby,
                'order' => $order,
                'post__not_in' => $notIn,
                'ignore_sticky_posts' => true,
                'no_found_rows' => true,
            ]);

            $cards = [];
            while ($query->have_posts()) {
                $query->the_post();
                $postId = (int) get_the_ID();
                $cards[] = self::cardFromPostId($postId);
            }
            wp_reset_postdata();

            $panels[] = [
                'label' => $isMulti ? (self::CPT_TAB_LABELS[$postType] ?? $postType) : '',
                'slug' => $postType,
                'cards' => $cards,
            ];
        }

        return $panels;
    }

    /**
     * @return array<string, mixed>
     */
    private static function cardFromPostId(int $postId): array
    {
        $titleDecoded = html_entity_decode((string) get_the_title($postId), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $postType = get_post_type($postId);
        $thumbId = 0;
        $imageUrl = '';

        if (is_string($postType) && DirectoryCardImage::supportsPostType($postType)) {
            $resolved = DirectoryCardImage::resolve($postId);
            $thumbId = $resolved['attachment_id'];
            $imageUrl = $resolved['url'];
        } else {
            $thumbId = (int) get_post_thumbnail_id($postId);
            if ($thumbId > 0) {
                $url = wp_get_attachment_image_url($thumbId, 'large');
                $imageUrl = is_string($url) && $url !== '' ? $url : '';
            }
        }

        /** @var array<string, mixed>|null $img */
        $img = null;
        $altText = $titleDecoded;
        if ($imageUrl !== '') {
            if ($thumbId > 0) {
                $thumbAlt = trim((string) get_post_meta($thumbId, '_wp_attachment_image_alt', true));
                $altText = $thumbAlt !== '' ? $thumbAlt : $titleDecoded;
            }
            $img = ['url' => $imageUrl, 'alt' => $altText];
        }

        return [
            'title' => $titleDecoded,
            'url' => get_permalink($postId) ?: '',
            'media_type' => 'image',
            'image' => $img,
            'video' => null,
            'poster' => null,
            'alt' => $altText,
        ];
    }

    /**
     * @param  array<string, mixed>  $component
     * @return list<array{label: string, slug: string, cards: list<array<string, mixed>>}>
     */
    private static function blogTabPanels(array $component): array
    {
        $termsRaw = $component['cards_blog_categories'] ?? [];
        if (! is_array($termsRaw)) {
            $termsRaw = [];
        }

        $perPage = (int) ($component['cards_blog_per_category'] ?? 3);
        if ($perPage < 1) {
            $perPage = 3;
        }
        if ($perPage > 12) {
            $perPage = 12;
        }

        $panels = [];

        foreach ($termsRaw as $tid) {
            $termId = (int) $tid;
            if ($termId <= 0) {
                continue;
            }
            $term = get_category($termId);
            if (! $term instanceof \WP_Term) {
                continue;
            }

            $q = new \WP_Query([
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $perPage,
                'cat' => $termId,
                'ignore_sticky_posts' => true,
                'no_found_rows' => true,
            ]);

            $cards = [];
            while ($q->have_posts()) {
                $q->the_post();
                $cards[] = self::cardFromPostId((int) get_the_ID());
            }
            wp_reset_postdata();

            $panels[] = [
                'label' => $term->name,
                'slug' => $term->slug,
                'cards' => $cards,
            ];
        }

        return $panels;
    }

    /**
     * Figma homepage `51:8214` — intro + single CPT tab rows use stacked landscape
     * promo tiles on mobile; tabbed News/Events/Offers strips stay portrait Splide.
     *
     * @param  array<string, mixed>  $component
     */
    public static function usesMobilePromoStack(array $component): bool
    {
        if (count(self::buildTabPanels($component)) > 1) {
            return false;
        }

        $body = (string) ($component['cards_body'] ?? '');

        return trim(strip_tags($body)) !== '';
    }
}
