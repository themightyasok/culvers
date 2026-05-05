<?php

declare(strict_types=1);

namespace App\Directory;

use App\Constants\ComponentTypes;
use App\Helpers\ThreeCardBlock;

/**
 * Bottom-of-/shops/ strip: heading + optional category tabs + three cards + View all (matches flexible {@see three-card-block}).
 *
 * @see filter culvers_shops_archive_three_card_component — pass a full component array to override options entirely.
 */
final class ShopArchiveThreeCard
{
    /**
     * @return array<string, mixed>|null Component payload for `components.three-card-block`, or null when disabled / empty.
     */
    public static function componentOrNull(): ?array
    {
        /** @var mixed $filtered */
        $filtered = apply_filters('culvers_shops_archive_three_card_component', null);
        if (is_array($filtered)) {
            return self::finalizeOrNull(array_merge(self::defaultsSkeleton(), $filtered));
        }

        return self::fromOptions();
    }

    /**
     * @return array<string, mixed>
     */
    private static function defaultsSkeleton(): array
    {
        return [
            'background_type' => ComponentTypes::BACKGROUND_NONE,
            'top_padding' => ComponentTypes::PADDING_LARGE,
            'bottom_padding' => ComponentTypes::PADDING_MEDIUM,
            'block_heading_level' => '2',
            'block_subheading' => '',
            'block_body' => '',
            '_grid_classes' => 'relative z-[20] w-full text-deep-moss',
        ];
    }

    private static function fromOptions(): ?array
    {
        if (! function_exists('get_field')) {
            return self::finalizeOrNull(array_merge(self::defaultsSkeleton(), self::fallbackWithoutAcf()));
        }

        $enabled = get_field('shops_archive_three_card_enabled', 'option');
        if ($enabled === false || $enabled === 0 || $enabled === '0') {
            return null;
        }

        $heading = trim((string) (get_field('shops_archive_three_card_heading', 'option') ?: ''));
        if ($heading === '') {
            $heading = __('What are you looking for today?', 'culvers');
        }

        $viewAllUrl = trim((string) (get_field('shops_archive_three_card_view_all_url', 'option') ?: ''));
        if ($viewAllUrl === '') {
            $viewAllUrl = self::defaultPostsIndexUrl();
        }

        $viewAllLabel = trim((string) (get_field('shops_archive_three_card_view_all_label', 'option') ?: ''));
        if ($viewAllLabel === '') {
            $viewAllLabel = __('View all', 'culvers');
        }

        $perPage = (int) get_field('shops_archive_three_card_posts_per_tab', 'option');
        if ($perPage < 1) {
            $perPage = 3;
        }
        if ($perPage > 12) {
            $perPage = 12;
        }

        /** @var mixed $categoryRaw */
        $categoryRaw = get_field('shops_archive_three_card_category_tabs', 'option');
        $categoryIds = self::normalizeCategoryIds($categoryRaw);

        $base = array_merge(self::defaultsSkeleton(), [
            'block_heading' => $heading,
            'blog_view_all_url' => $viewAllUrl,
            'blog_view_all_label' => $viewAllLabel,
        ]);

        if ($categoryIds !== []) {
            return self::finalizeOrNull(array_merge($base, [
                'cards_source' => 'blog',
                'blog_category_tabs' => $categoryIds,
                'blog_posts_per_category' => $perPage,
            ]));
        }

        return self::finalizeOrNull(array_merge($base, [
            'cards_source' => 'manual',
            'three_cards' => self::manualCardsFromRecentPosts($perPage),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private static function fallbackWithoutAcf(): array
    {
        return [
            'block_heading' => __('What are you looking for today?', 'culvers'),
            'cards_source' => 'manual',
            'three_cards' => self::manualCardsFromRecentPosts(3),
            'blog_view_all_url' => self::defaultPostsIndexUrl(),
            'blog_view_all_label' => __('View all', 'culvers'),
        ];
    }

    private static function defaultPostsIndexUrl(): string
    {
        $postsPageId = (int) get_option('page_for_posts');

        return $postsPageId > 0 && is_string(get_permalink($postsPageId))
            ? (string) get_permalink($postsPageId)
            : home_url('/');
    }

    /**
     * @param  mixed  $raw  ACF taxonomy field (`return_format` => id).
     * @return list<int>
     */
    private static function normalizeCategoryIds(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $v) {
            $id = (int) $v;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function manualCardsFromRecentPosts(int $count): array
    {
        $count = min(max($count, 1), 3);
        $q = new \WP_Query([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
        ]);

        $rows = [];
        while ($q->have_posts()) {
            $q->the_post();
            $postId = (int) get_the_ID();
            $thumbId = (int) get_post_thumbnail_id($postId);
            $titleDecoded = html_entity_decode((string) get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $permalink = get_permalink($postId);
            if (! is_string($permalink) || $permalink === '' || $titleDecoded === '') {
                continue;
            }

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

            $rows[] = [
                'card_title' => $titleDecoded,
                'card_url' => $permalink,
                'card_media_type' => 'image',
                'card_image' => $img,
                'card_image_alt' => $altText,
            ];

            if (count($rows) >= 3) {
                break;
            }
        }
        wp_reset_postdata();

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $component
     */
    private static function finalizeOrNull(array $component): ?array
    {
        $normalized = ThreeCardBlock::applyEditorFallback($component);
        foreach (ThreeCardBlock::buildTabPanels($normalized) as $tab) {
            if (($tab['cards'] ?? []) !== []) {
                return $normalized;
            }
        }

        return null;
    }
}
