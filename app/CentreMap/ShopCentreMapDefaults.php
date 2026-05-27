<?php

declare(strict_types=1);

namespace App\CentreMap;

use App\Directory\DirectoryFilterDefinitions;

/**
 * Canonical centre-map filter rows for directory singles (shop / eat-drink).
 *
 * New posts often inherit the `centre_map` layout key from
 * {@see \App\Directory\DirectoryFlexibleDefaults} without repeater rows — the
 * band renders a heading and map but no filter panel. These defaults mirror
 * the populated singles (e.g. Ann Summers) and pre-select the retailer's
 * primary category where possible.
 */
final class ShopCentreMapDefaults
{
    /**
     * @return list<string>
     */
    public static function supportedPostTypes(): array
    {
        return ['culvers_shop', 'culvers_eat_drink'];
    }

    public static function supportsPostType(string $postType): bool
    {
        return in_array($postType, self::supportedPostTypes(), true);
    }

    /**
     * @return list<array{category_group: string, category_label: string, category_slug: string, category_url: string}>
     */
    public static function categoryRows(): array
    {
        $home = static fn (string $path): string => function_exists('home_url') ? home_url($path) : $path;

        $rows = [
            [
                'category_group' => __('Shop', 'culvers'),
                'category_label' => __('All', 'culvers'),
                'category_slug' => 'shop-all',
                'category_url' => $home('/shops/'),
            ],
        ];

        foreach (DirectoryFilterDefinitions::shopCategoryPairs() as $slug => $label) {
            $rows[] = [
                'category_group' => __('Shop', 'culvers'),
                'category_label' => $label,
                'category_slug' => $slug,
                'category_url' => $home('/shops/?category=' . $slug),
            ];
        }

        $rows[] = [
            'category_group' => __('Eat and drink', 'culvers'),
            'category_label' => __('All', 'culvers'),
            'category_slug' => 'eat-drink-all',
            'category_url' => $home('/eat-drink/'),
        ];

        $eatDrinkRows = [
            'cafes' => __('Cafés & Coffee', 'culvers'),
            'grab-go' => __('Takeaway', 'culvers'),
            'restaurants' => __('Restaurants', 'culvers'),
        ];

        foreach ($eatDrinkRows as $slug => $label) {
            $mapSlug = match ($slug) {
                'cafes' => 'eat-drink-cafes',
                'grab-go' => 'eat-drink-takeaway',
                'restaurants' => 'eat-drink-restaurants',
                default => $slug,
            };
            $rows[] = [
                'category_group' => __('Eat and drink', 'culvers'),
                'category_label' => $label,
                'category_slug' => $mapSlug,
                'category_url' => $home('/eat-drink/?type=' . $slug),
            ];
        }

        $guestServices = [
            ['toilets', __('Toilets & accessible facilities', 'culvers')],
        ];

        foreach ($guestServices as [$slug, $label]) {
            $rows[] = [
                'category_group' => __('Guest Services', 'culvers'),
                'category_label' => $label,
                'category_slug' => $slug,
                'category_url' => '',
            ];
        }

        return $rows;
    }

    /**
     * Pre-select the retailer's primary directory category on shop singles.
     *
     * @return array{group: string, slug: string, label: string}|null
     */
    public static function initialSelectionForPost(int $postId): ?array
    {
        $postType = get_post_type($postId);
        if (! is_string($postType) || ! self::supportsPostType($postType)) {
            return null;
        }

        $taxonomy = $postType === 'culvers_eat_drink' ? 'culvers_eat_drink_type' : 'culvers_shop_category';
        $terms = wp_get_post_terms($postId, $taxonomy, ['fields' => 'all']);
        if (! is_array($terms) || $terms === []) {
            return self::defaultSelection($postType);
        }

        $term = $terms[0];
        if (! $term instanceof \WP_Term) {
            return self::defaultSelection($postType);
        }

        $slug = (string) $term->slug;
        $label = (string) $term->name;

        foreach (self::categoryRows() as $row) {
            if (($row['category_slug'] ?? '') !== $slug) {
                continue;
            }

            return [
                'group' => sanitize_title((string) ($row['category_group'] ?? '')),
                'slug' => $slug,
                'label' => $label,
            ];
        }

        return self::defaultSelection($postType);
    }

    /**
     * @return array{group: string, slug: string, label: string}
     */
    private static function defaultSelection(string $postType): array
    {
        if ($postType === 'culvers_eat_drink') {
            return [
                'group' => sanitize_title(__('Eat and drink', 'culvers')),
                'slug' => 'eat-drink-all',
                'label' => __('All', 'culvers'),
            ];
        }

        return [
            'group' => sanitize_title(__('Shop', 'culvers')),
            'slug' => 'shop-all',
            'label' => __('All', 'culvers'),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function mergeComponentRow(array $row, ?int $postId = null): array
    {
        if (($row['acf_fc_layout'] ?? '') !== 'centre_map') {
            return $row;
        }

        $cats = $row['centre_map_categories'] ?? null;
        if (is_array($cats) && $cats !== []) {
            return $row;
        }

        $row['centre_map_categories'] = self::categoryRows();

        if ($postId === null || $postId <= 0) {
            return $row;
        }

        if (trim((string) ($row['centre_map_heading'] ?? '')) === '') {
            $row['centre_map_heading'] = __('Find your way around', 'culvers');
        }

        return $row;
    }

    /**
     * @param list<array<string, mixed>> $components
     * @return list<array<string, mixed>>
     */
    public static function mergeIntoComponents(array $components, int $postId): array
    {
        foreach ($components as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $components[$i] = self::mergeComponentRow($row, $postId);
        }

        return $components;
    }
}
