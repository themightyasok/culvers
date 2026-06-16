<?php

declare(strict_types=1);

namespace App\CentreMap;

use App\Directory\DirectoryFilterDefinitions;

/**
 * Single source of truth for the centre-map filter category list.
 *
 * The centre-map nav is identical everywhere it appears (Plan my visit + every
 * shop / eat-drink single), so the list lives here in code rather than being
 * duplicated into each post's ACF data. {@see categoryRows()} derives the Shop
 * and Eat & Drink groups from {@see DirectoryFilterDefinitions} (so renaming or
 * hiding a directory category updates the map automatically) plus the fixed
 * Guest Services links. The `centre_map` component no longer stores its own
 * category repeater — the Blade view reads this list directly.
 *
 * {@see initialSelectionForPost()} still pre-selects the retailer's primary
 * category on shop / eat-drink singles.
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

        foreach (DirectoryFilterDefinitions::eatDrinkCategoryPairs() as $slug => $label) {
            $rows[] = [
                'category_group' => __('Eat and drink', 'culvers'),
                'category_label' => $label,
                'category_slug' => $slug,
                'category_url' => $home('/eat-drink/?type=' . $slug),
            ];
        }

        $guestServices = [
            ['parent-child', __('Parent & Child Facilities', 'culvers'), $home('/guest-services/#parent-child')],
            ['lost-property', __('Lost Property', 'culvers'), $home('/guest-services/#lost-property')],
        ];

        foreach ($guestServices as [$slug, $label, $url]) {
            $rows[] = [
                'category_group' => __('Guest Services', 'culvers'),
                'category_label' => $label,
                'category_slug' => $slug,
                'category_url' => $url,
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

        $slug = $term->slug;
        $label = $term->name;

        foreach (self::categoryRows() as $row) {
            if ($row['category_slug'] !== $slug) {
                continue;
            }

            return [
                'group' => sanitize_title($row['category_group']),
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
}
