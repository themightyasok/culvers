<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Eat & Drink directory filters — Figma Food Directory sidebar (frame 51:7657).
 *
 * Marketing filters (Grab & Go, Restaurants, …) live on {@see culvers_eat_drink_type}
 * so mega-menu URLs (`/eat-drink/?type=grab-go`) and card `data-type-slugs` align.
 */
final class EatDrinkTaxonomySeeder
{
    private const OPTION_KEY = 'culvers_eat_drink_terms_seeded_v2';

    public static function maybeSeed(): void
    {
        if ((bool) get_option(self::OPTION_KEY, false)) {
            return;
        }

        DirectoryFilterDefinitions::syncTaxonomyTerms(
            'culvers_eat_drink_type',
            DirectoryFilterDefinitions::eatDrinkCategoryPairs()
        );

        update_option(self::OPTION_KEY, '1', true);
    }

    /** Force re-sync after deploy (CLI / eval). */
    public static function syncNow(): void
    {
        $pairs = DirectoryFilterDefinitions::eatDrinkCategoryPairs();
        DirectoryFilterDefinitions::syncTaxonomyTerms('culvers_eat_drink_type', $pairs);

        $allowed = array_fill_keys(array_keys($pairs), true);
        $terms = get_terms([
            'taxonomy' => 'culvers_eat_drink_type',
            'hide_empty' => false,
        ]);
        if (is_array($terms)) {
            foreach ($terms as $term) {
                if (isset($allowed[$term->slug])) {
                    continue;
                }
                if ((int) $term->count > 0) {
                    continue;
                }
                wp_delete_term((int) $term->term_id, 'culvers_eat_drink_type');
            }
        }

        update_option(self::OPTION_KEY, '1', true);
    }
}
