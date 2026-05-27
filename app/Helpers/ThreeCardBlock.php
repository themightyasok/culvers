<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Directory\DirectoryCardImage;

/**
 * Flexible layout `three_card_block`: manual cards, directory CPT queries, or blog categories.
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

    /** News / Events / Offers tab strip — multi-CPT “View all” targets the What's On hub. */
    private const STORIES_TAB_CPTS = [
        'culvers_news',
        'culvers_event',
        'culvers_offer',
    ];

    /**
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
     * ACF Items-tab field map for manual card rows (shared by flexible layout + archive options).
     *
     * @param  array<int, array<int, array<string, string>>>  $conditionalLogic
     * @return array<string, array<string, mixed>>
     */
    public static function manualCardsItemsTabFields(array $conditionalLogic, string $repeaterKey = 'cards_items'): array
    {
        return [
            'cards_items_help' => [
                'type' => 'message',
                'options' => [
                    'message' => __(
                        'These manual cards are only rendered when <strong>Card source</strong> on the '
                        . '<em>Main</em> tab is set to <strong>Manual</strong>. For blog or directory CPT '
                        . 'sources, the row builds itself from those queries and ignores this list.',
                        'culvers'
                    ),
                    'esc_html' => 0,
                    'wrapper' => ['class' => 'culvers-acf-help'],
                ],
            ],
            $repeaterKey => [
                'type' => 'repeater',
                'options' => [
                    'label' => __('Cards (manual)', 'culvers'),
                    'instructions' => __(
                        'Exactly three cards recommended. Desktop video plays on hover; '
                            . 'mobile uses the mobile video when set, otherwise the main file. '
                            . 'Only used when source is "Manual".',
                        'culvers'
                    ),
                    'min' => 0,
                    'max' => 3,
                    'layout' => 'block',
                    'button_label' => __('Add card', 'culvers'),
                    'collapsed' => 'card_title',
                    'conditional_logic' => $conditionalLogic,
                    'sub_fields' => self::manualCardSubFields(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function manualCardSubFields(): array
    {
        return [
            'card_title' => [
                'type' => 'text',
                'options' => [
                    'label' => __('Card title', 'culvers'),
                    'required' => 1,
                    'wrapper' => ['width' => '70'],
                ],
            ],
            'card_media_type' => [
                'type' => 'radio',
                'options' => [
                    'label' => __('Media', 'culvers'),
                    'choices' => [
                        'image' => __('Image', 'culvers'),
                        'video' => __('Video', 'culvers'),
                    ],
                    'default_value' => 'image',
                    'layout' => 'horizontal',
                    'return_format' => 'value',
                    'wrapper' => ['width' => '30'],
                ],
            ],
            'card_url' => [
                'type' => 'url',
                'options' => [
                    'label' => __('Link URL', 'culvers'),
                    'required' => 1,
                ],
            ],
            'card_image' => [
                'type' => 'image',
                'options' => [
                    'label' => __('Image', 'culvers'),
                    'instructions' => __('Used when media is Image.', 'culvers'),
                    'return_format' => 'array',
                    'preview_size' => 'medium',
                    'library' => 'all',
                    'conditional_logic' => [[[
                        'field' => 'card_media_type',
                        'operator' => '==',
                        'value' => 'image',
                    ]]],
                ],
            ],
            'card_image_alt' => [
                'type' => 'text',
                'options' => [
                    'label' => __('Image alt text', 'culvers'),
                    'instructions' => __('Important for screen readers when using an image.', 'culvers'),
                    'conditional_logic' => [[[
                        'field' => 'card_media_type',
                        'operator' => '==',
                        'value' => 'image',
                    ]]],
                ],
            ],
            'card_video' => [
                'type' => 'file',
                'options' => [
                    'label' => __('Video file (desktop)', 'culvers'),
                    'instructions' => __(
                        'Default video for desktop hover playback and for mobile when no mobile file is set.',
                        'culvers'
                    ),
                    'mime_types' => 'mp4,webm',
                    'return_format' => 'array',
                    'library' => 'all',
                    'conditional_logic' => [[[
                        'field' => 'card_media_type',
                        'operator' => '==',
                        'value' => 'video',
                    ]]],
                ],
            ],
            'card_video_mobile' => [
                'type' => 'file',
                'options' => [
                    'label' => __('Video file (mobile)', 'culvers'),
                    'instructions' => __(
                        'Optional. Used on phones and touch viewports only — e.g. a tighter crop or shorter loop. '
                            . 'Falls back to the desktop video when empty.',
                        'culvers'
                    ),
                    'mime_types' => 'mp4,webm',
                    'return_format' => 'array',
                    'library' => 'all',
                    'conditional_logic' => [[[
                        'field' => 'card_media_type',
                        'operator' => '==',
                        'value' => 'video',
                    ]]],
                ],
            ],
        ];
    }

    /**
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
     * @param  array<string, mixed>  $component
     */
    public static function mediaOverlayOpacity(array $component): int
    {
        return Component::overlayOpacityPercent($component['cards_media_overlay_opacity'] ?? null, 25);
    }

    /**
     * @param  array<string, mixed>  $component
     */
    public static function viewAllUrl(array $component): string
    {
        $source = (string) ($component['cards_source'] ?? 'manual');
        if ($source === 'manual') {
            return '';
        }

        $explicit = trim((string) ($component['cards_view_all_url'] ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        if ($source === 'blog') {
            return self::defaultPostsIndexUrl();
        }

        $list = self::normalizeCptList($component['cards_cpt_post_type'] ?? null);
        if ($list === []) {
            return '';
        }

        if (count($list) === 1) {
            $url = get_post_type_archive_link($list[0]);

            return is_string($url) ? $url : '';
        }

        if (self::isStoriesTabCptList($list)) {
            return self::whatsOnLandingUrl();
        }

        return '';
    }

    private static function defaultPostsIndexUrl(): string
    {
        $postsPageId = (int) get_option('page_for_posts');

        return $postsPageId > 0 && is_string(get_permalink($postsPageId))
            ? (string) get_permalink($postsPageId)
            : home_url('/');
    }

    private static function whatsOnLandingUrl(): string
    {
        $page = get_page_by_path('whats-on');
        if ($page instanceof \WP_Post) {
            $url = get_permalink($page);

            return $url !== '' ? $url : home_url('/whats-on/');
        }

        return home_url('/whats-on/');
    }

    /**
     * @param  list<string>  $list
     */
    private static function isStoriesTabCptList(array $list): bool
    {
        if ($list === []) {
            return false;
        }

        foreach ($list as $postType) {
            if (! in_array($postType, self::STORIES_TAB_CPTS, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $component
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

        $itemsKey = self::manualItemsKey($component);

        return [
            [
                'label' => '',
                'slug' => 'manual',
                'cards' => self::normalizeManualCards(
                    is_array($component[$itemsKey] ?? null) ? $component[$itemsKey] : []
                ),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $component
     */
    public static function isManualSource(array $component): bool
    {
        return (string) ($component['cards_source'] ?? 'manual') === 'manual';
    }

    /**
     * @param  array<string, mixed>  $component
     */
    public static function usesMobilePromoStack(array $component): bool
    {
        if (! self::isManualSource($component)) {
            return false;
        }

        if (count(self::buildTabPanels($component)) > 1) {
            return false;
        }

        $body = (string) ($component['cards_body'] ?? '');

        return trim(strip_tags($body)) !== '';
    }

    /**
     * @param  array<string, mixed>  $component
     */
    private static function manualItemsKey(array $component): string
    {
        if (array_key_exists('cards_items', $component)) {
            return 'cards_items';
        }

        return 'archive_three_card_items';
    }

    /**
     * @param  array<int, mixed>  $rows
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

            $img = self::normalizeAcfFileField($row['card_image'] ?? null);
            $vid = self::normalizeAcfFileField($row['card_video'] ?? null, true);
            $vidMobile = self::normalizeAcfFileField($row['card_video_mobile'] ?? null, true);
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
                'video_mobile' => $vidMobile,
                'poster' => null,
                'alt' => $alt !== '' ? $alt : $title,
            ];

            if (count($out) >= 3) {
                break;
            }
        }

        return $out;
    }

    /**
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

        return [
            'url' => $url,
            'mime_type' => $isVideo ? ($mime !== '' ? $mime : 'video/mp4') : $mime,
            'alt' => trim((string) get_post_meta($id, '_wp_attachment_image_alt', true)),
        ];
    }

    /**
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
                $cards[] = self::cardFromPostId((int) get_the_ID());
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
}
