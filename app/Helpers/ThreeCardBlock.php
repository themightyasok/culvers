<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Flexible layout `three_card_block`: normalize manual/blog cards from saved ACF data.
 */
final class ThreeCardBlock
{
    /**
     * Structural normalization only — no demo copy or placeholder media is injected here.
     *
     * @param  array<string, mixed>  $component
     * @return array<string, mixed>
     */
    public static function applyEditorFallback(array $component): array
    {
        $source = (string) ($component['cards_source'] ?? 'manual');
        if ($source !== 'manual' && $source !== 'blog') {
            $component['cards_source'] = 'manual';
        }

        return $component;
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
