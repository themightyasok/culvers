<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Constants\ComponentTypes;

/**
 * Flexible layout `three_card_block`: normalize manual/blog cards and demo fallback.
 */
final class ThreeCardBlock
{
    /** @var non-empty-string */
    private const DEMO_VIDEO_MP4 = 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4';

    /**
     * Merge demo headline/body/cards when editors add an empty block (local preview friendly).
     *
     * @param  array<string, mixed>  $component
     * @return array<string, mixed>
     */
    public static function applyEditorFallback(array $component): array
    {
        if (! apply_filters('culvers_three_card_demo_fallback', true)) {
            return $component;
        }

        $source = (string) ($component['cards_source'] ?? 'manual');
        if ($source !== 'manual' && $source !== 'blog') {
            $source = 'manual';
            $component['cards_source'] = 'manual';
        }

        /** Full starter demo only when Manual + no cards yet (does not overwrite a deliberate empty heading/body once rows exist). */
        $manualEmptyCards = $source === 'manual' && empty($component['three_cards']);

        if ($manualEmptyCards) {
            $component['three_cards'] = self::demoRepeaterRows();
        }

        if ($manualEmptyCards && trim((string) ($component['block_heading'] ?? '')) === '') {
            $component['block_heading'] = __('Fun for the whole family', 'culvers');
        }

        if ($manualEmptyCards && trim((string) ($component['block_body'] ?? '')) === '') {
            $component['block_body'] = sprintf(
                '<p>%s</p>',
                esc_html__(
                    'Discover shops, places to eat, and everything you need to plan your visit — all in one welcoming destination.',
                    'culvers'
                )
            );
        }

        return $component;
    }

    /**
     * One flexible-content row: homepage three-up strip with video cards (used when the front page has no three-card block in ACF yet).
     *
     * @return array<string, mixed>
     */
    public static function homepageFeaturedFlexibleRow(): array
    {
        return [
            'acf_fc_layout' => 'three_card_block',
            'component_width' => 12,
            'background_type' => ComponentTypes::BACKGROUND_NONE,
            'body_text_tone' => TailwindColors::DEFAULT_BODY_TEXT_TONE,
            'visibility_mobile' => 'visible',
            'cards_source' => 'manual',
            'block_heading' => __('Fun for the whole family', 'culvers'),
            'block_subheading' => '',
            'block_heading_level' => '2',
            'block_body' => sprintf(
                '<p>%s</p>',
                esc_html__(
                    'Discover shops, places to eat, and everything you need to plan your visit — all in one welcoming destination.',
                    'culvers'
                )
            ),
            'three_cards' => self::homepageVideoCardRows(),
            'top_padding' => ComponentTypes::PADDING_MEDIUM,
            'bottom_padding' => ComponentTypes::PADDING_MEDIUM,
        ];
    }

    /**
     * Three manual cards with looping video (homepage default until replaced in the editor).
     *
     * @return list<array<string, mixed>>
     */
    public static function homepageVideoCardRows(): array
    {
        $demoUrl = self::DEMO_VIDEO_MP4;

        return [
            [
                'card_title' => __('Shop', 'culvers'),
                'card_url' => home_url('/shopping/'),
                'card_media_type' => 'video',
                'card_video' => [
                    'url' => $demoUrl,
                    'mime_type' => 'video/mp4',
                ],
                'card_image' => null,
                'card_video_poster' => null,
                'card_image_alt' => '',
            ],
            [
                'card_title' => __('Eat & Drink', 'culvers'),
                'card_url' => home_url('/dining/'),
                'card_media_type' => 'video',
                'card_video' => [
                    'url' => $demoUrl,
                    'mime_type' => 'video/mp4',
                ],
                'card_image' => null,
                'card_video_poster' => null,
                'card_image_alt' => '',
            ],
            [
                'card_title' => __('Plan My Visit', 'culvers'),
                'card_url' => home_url('/visit/'),
                'card_media_type' => 'video',
                'card_video' => [
                    'url' => $demoUrl,
                    'mime_type' => 'video/mp4',
                ],
                'card_image' => null,
                'card_video_poster' => null,
                'card_image_alt' => '',
            ],
        ];
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

        return [
            [
                'label' => '',
                'slug' => 'manual',
                'cards' => self::normalizeManualCards(is_array($component['three_cards'] ?? null) ? $component['three_cards'] : []),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function demoRepeaterRows(): array
    {
        return self::homepageVideoCardRows();
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
            $img = isset($row['card_image']) && is_array($row['card_image']) ? $row['card_image'] : null;
            /** @var array<string, mixed>|null $vid */
            $vid = isset($row['card_video']) && is_array($row['card_video']) ? $row['card_video'] : null;
            /** @var array<string, mixed>|null $poster */
            $poster = isset($row['card_video_poster']) && is_array($row['card_video_poster'])
                ? $row['card_video_poster']
                : null;

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
     * @param  array<string, mixed>  $component
     * @return list<array{label: string, slug: string, cards: list<array<string, mixed>>}>
     */
    private static function blogTabPanels(array $component): array
    {
        $termsRaw = $component['blog_category_tabs'] ?? [];
        if (! is_array($termsRaw)) {
            $termsRaw = [];
        }

        $perPage = (int) ($component['blog_posts_per_category'] ?? 3);
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
