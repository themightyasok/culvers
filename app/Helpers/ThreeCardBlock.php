<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Flexible layout `three_card_block`: normalize manual/blog cards from saved ACF data.
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
     * Structural normalization only — no demo copy or placeholder media is injected here.
     *
     * @param  array<string, mixed>  $component
     * @return array<string, mixed>
     */
    public static function applyEditorFallback(array $component): array
    {
        $source = (string) ($component['cards_source'] ?? 'manual');
        if ($source !== 'manual' && $source !== 'blog' && $source !== 'cpt') {
            $component['cards_source'] = 'manual';
        }

        return $component;
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

        $source = (string) ($component['cards_source'] ?? 'manual');
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
     * Tab panels for Blade: manual → single tab; blog → one tab per category.
     *
     * @param  array<string, mixed>  $component  Already sanitized + fallback merged.
     * @return list<array{label: string, slug: string, cards: list<array<string, mixed>>}>
     */
    public static function buildTabPanels(array $component): array
    {
        $source = (string) ($component['cards_source'] ?? 'manual');

        if ($source === 'blog') {
            return self::blogTabPanels($component);
        }

        if ($source === 'cpt') {
            return self::cptTabPanels($component);
        }

        return [
            [
                'label' => '',
                'slug' => 'manual',
                'cards' => self::normalizeManualCards(is_array($component['cards_items'] ?? null) ? $component['cards_items'] : []),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>|array<string, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private static function normalizeManualCards(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['card_title'] ?? ''));
            $href = trim((string) ($row['card_url'] ?? ''));
            $media = (string) ($row['card_media_type'] ?? 'image');
            if ($media !== 'video') {
                $media = 'image';
            }

            /** @var array<string, mixed>|null $img */
            $img = self::normalizeAcfFileField($row['card_image'] ?? null);
            /** @var array<string, mixed>|null $vid */
            $vid = self::normalizeAcfFileField($row['card_video'] ?? null, true);
            /** @var array<string, mixed>|null $poster */
            $poster = self::normalizeAcfFileField($row['card_video_poster'] ?? null);

            $alt = trim((string) ($row['card_image_alt'] ?? ''));

            if ($title === '' || $href === '') {
                continue;
            }

            $out[] = [
                'title' => $title,
                'url' => $href,
                'media_type' => $media,
                'image' => $img,
                'video' => $vid,
                'poster' => $poster,
                'alt' => $alt !== '' ? $alt : $title,
            ];

            if (count($out) >= 3) {
                break;
            }
        }

        return $out;
    }

    /**
     * Resolve ACF image/file fields saved as arrays or attachment IDs.
     *
     * @return array<string, mixed>|null
     */
    private static function normalizeAcfFileField(mixed $field, bool $isVideo = false): ?array
    {
        if (is_array($field) && ! empty($field['url'])) {
            return $field;
        }

        if (! is_numeric($field) || (int) $field <= 0) {
            return null;
        }

        $id = (int) $field;
        $url = wp_get_attachment_url($id);
        if (! is_string($url) || $url === '') {
            return null;
        }

        $mime = (string) (get_post_mime_type($id) ?: '');
        if ($isVideo && $mime !== '' && ! str_starts_with($mime, 'video/')) {
            return null;
        }

        $row = [
            'url' => $url,
            'mime_type' => $isVideo ? ($mime !== '' ? $mime : 'video/mp4') : $mime,
            'alt' => trim((string) get_post_meta($id, '_wp_attachment_image_alt', true)),
        ];

        return $row;
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

            $query = new \WP_Query([
                'post_type' => $postType,
                'post_status' => 'publish',
                'posts_per_page' => $perPage,
                'orderby' => $orderby,
                'order' => $order,
                'ignore_sticky_posts' => true,
                'no_found_rows' => true,
            ]);

            $cards = [];
            while ($query->have_posts()) {
                $query->the_post();
                $postId = (int) get_the_ID();
                $titleDecoded = html_entity_decode((string) get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $thumbId = (int) get_post_thumbnail_id($postId);
                /** @var array<string, mixed>|null $img */
                $img = null;
                $altText = $titleDecoded;
                if ($thumbId > 0) {
                    $url = wp_get_attachment_image_url($thumbId, 'large');
                    if (is_string($url) && $url !== '') {
                        $thumbAlt = trim((string) get_post_meta($thumbId, '_wp_attachment_image_alt', true));
                        $altText = $thumbAlt !== '' ? $thumbAlt : $titleDecoded;
                        $img = ['url' => $url, 'alt' => $altText];
                    }
                }

                $cards[] = [
                    'title' => $titleDecoded,
                    'url' => get_permalink($postId) ?: '',
                    'media_type' => 'image',
                    'image' => $img,
                    'video' => null,
                    'poster' => null,
                    'alt' => $altText,
                ];
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
                $postId = (int) get_the_ID();
                $thumbId = (int) get_post_thumbnail_id($postId);
                $titleDecoded = html_entity_decode((string) get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                /** @var array<string, mixed>|null $img */
                $img = null;
                $altText = $titleDecoded;
                if ($thumbId > 0) {
                    $u = wp_get_attachment_image_url($thumbId, 'large');
                    if (is_string($u) && $u !== '') {
                        $thumbAlt = trim((string) get_post_meta($thumbId, '_wp_attachment_image_alt', true));
                        $altText = $thumbAlt !== '' ? $thumbAlt : $titleDecoded;
                        $img = ['url' => $u, 'alt' => $altText];
                    }
                }

                $cards[] = [
                    'title' => $titleDecoded,
                    'url' => get_permalink($postId) ?: '',
                    'media_type' => 'image',
                    'image' => $img,
                    'video' => null,
                    'poster' => null,
                    'alt' => $altText,
                ];
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
}
