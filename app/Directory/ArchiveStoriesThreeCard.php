<?php

declare(strict_types=1);

namespace App\Directory;

use App\Constants\ComponentTypes;
use App\Helpers\Cast;
use App\Helpers\ThreeCardBlock;

/**
 * Theme-options-driven three-card strip (directory CPT pills or blog-category override).
 *
 * Field keys live under `{archivePrefix}_three_card_*` ({@see ArchiveStoriesThreeCardFields}).
 */
final class ArchiveStoriesThreeCard
{
    /** @var list<string> */
    private const DEFAULT_STORIES_TAB_CPTS = [
        'culvers_news',
        'culvers_event',
        'culvers_offer',
    ];

    /** @var list<string> */
    private const LEGACY_HOME_VIEW_ALL_LABELS = [
        'back to home',
        'back to homepage',
        'return to home',
        'return to homepage',
        'visit homepage',
        'visit home',
        'go home',
        'go to homepage',
        'go to home',
        'return homepage',
        'visit the homepage',
    ];

    /**
     * Component payload for `components.three-card-block`, or null when disabled / empty.
     *
     * @param  non-empty-string  $acfFieldPrefix  Root for `{prefix}_three_card_*` ({@see self::ACF_PREFIX_SHOPS} …).
     * @param  non-empty-string  $overrideFilterHook  `apply_filters` hook returning a full merged component or null.
     * @return array<string, mixed>|null
     */
    public static function componentOrNull(string $acfFieldPrefix, string $overrideFilterHook): ?array
    {
        /** @var mixed $filtered */
        $filtered = apply_filters($overrideFilterHook, null);
        if (is_array($filtered)) {
            return self::finalizeOrNull(array_merge(self::defaultsSkeleton(), $filtered));
        }

        return self::fromOptions($acfFieldPrefix);
    }

    /**
     * @return array<string, mixed>
     */
    private static function defaultsSkeleton(): array
    {
        return [
            'background_type' => ComponentTypes::BACKGROUND_NONE,
            'cards_heading_level' => '2',
            'cards_subheading' => '',
            'cards_body' => '',
            '_grid_classes' => 'relative z-20 w-full text-deep-moss',
        ];
    }

    /**
     * @param  non-empty-string  $acfFieldPrefix
     * @return array<string, mixed>|null
     */
    private static function fromOptions(string $acfFieldPrefix): ?array
    {
        $stem = $acfFieldPrefix . '_three_card';

        if (! function_exists('get_field')) {
            return self::finalizeOrNull(array_merge(self::defaultsSkeleton(), self::fallbackWithoutAcf()));
        }

        $enabled = get_field("{$stem}_enabled", 'option');
        if ($enabled === false || $enabled === 0 || $enabled === '0') {
            return null;
        }

        $heading = trim(Cast::toString(get_field("{$stem}_heading", 'option')));
        if ($heading === '') {
            $heading = __('What are you looking for today?', 'culvers');
        }

        $viewAllUrl = trim(Cast::toString(get_field("{$stem}_view_all_url", 'option')));
        if ($viewAllUrl === '') {
            $viewAllUrl = self::defaultPostsIndexUrl();
        }

        $viewAllLabel = trim(Cast::toString(get_field("{$stem}_view_all_label", 'option')));
        if ($viewAllLabel === '') {
            $viewAllLabel = __('View all', 'culvers');
        }

        $perPage = Cast::toInt(get_field("{$stem}_posts_per_tab", 'option'));
        if ($perPage < 1) {
            $perPage = 3;
        }
        if ($perPage > 12) {
            $perPage = 12;
        }

        $categoryIds = self::normalizeCategoryIds(get_field("{$stem}_category_tabs", 'option'));

        $base = array_merge(self::defaultsSkeleton(), [
            'cards_heading' => $heading,
            'cards_view_all_url' => $viewAllUrl,
            'cards_view_all_label' => $viewAllLabel,
        ]);

        if ($categoryIds !== []) {
            return self::finalizeOrNull(array_merge($base, [
                'cards_source' => 'blog',
                'cards_blog_categories' => $categoryIds,
                'cards_blog_per_category' => $perPage,
            ]));
        }

        return self::finalizeOrNull(array_merge($base, [
            'cards_source' => 'cpt',
            'cards_cpt_post_type' => self::DEFAULT_STORIES_TAB_CPTS,
            'cards_cpt_count' => $perPage,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private static function fallbackWithoutAcf(): array
    {
        return [
            'cards_heading' => __('What are you looking for today?', 'culvers'),
            'cards_source' => 'cpt',
            'cards_cpt_post_type' => self::DEFAULT_STORIES_TAB_CPTS,
            'cards_cpt_count' => 3,
            'cards_view_all_url' => self::defaultPostsIndexUrl(),
            'cards_view_all_label' => __('View all', 'culvers'),
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
     * CTA label: default “View all”, and replace accidental “back to home”-style option copy.
     */
    private static function normalizeViewAllLabel(string $raw): string
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return __('View all', 'culvers');
        }

        $collapsed = (string) preg_replace('/\s+/u', ' ', mb_strtolower($trimmed, 'UTF-8'));
        $collapsed = preg_replace('/[^\p{L}\p{N}\s]/u', '', $collapsed) ?? $collapsed;
        $collapsed = trim((string) preg_replace('/\s+/u', ' ', $collapsed));

        if ($collapsed === '' || in_array($collapsed, self::LEGACY_HOME_VIEW_ALL_LABELS, true)) {
            return __('View all', 'culvers');
        }

        return $trimmed;
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
     * @param  array<string, mixed>  $component
     * @return array<string, mixed>|null
     */
    private static function finalizeOrNull(array $component): ?array
    {
        $normalized = ThreeCardBlock::applyEditorFallback($component);
        $normalized['cards_view_all_label'] = self::normalizeViewAllLabel(
            Cast::toString($normalized['cards_view_all_label'] ?? '')
        );

        foreach (ThreeCardBlock::buildTabPanels($normalized) as $tab) {
            if ($tab['cards'] !== []) {
                return $normalized;
            }
        }

        return null;
    }
}
