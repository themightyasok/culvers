<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Eat & Drink directory filters — Figma Food Directory sidebar (frame 51:7657).
 *
 * Marketing filters (Grab & Go, Restaurants, …) live on {@see culvers_eat_drink_type}
 * so mega-menu URLs (`/eat-drink/?type=grab-go`) and card `data-type-slugs` align.
 */
final class EatDrinkTaxonomySeeder extends AbstractTaxonomySeeder
{
    private const TAXONOMY = 'culvers_eat_drink_type';

    protected static function optionKey(): string
    {
        return 'culvers_eat_drink_terms_seeded_v2';
    }

    protected static function performSeed(): void
    {
        DirectoryFilterDefinitions::syncTaxonomyTerms(
            self::TAXONOMY,
            DirectoryFilterDefinitions::eatDrinkCategoryPairs()
        );
    }

    /** Force re-sync after deploy (CLI / eval). */
    public static function syncNow(): void
    {
        $pairs = DirectoryFilterDefinitions::eatDrinkCategoryPairs();
        DirectoryFilterDefinitions::syncTaxonomyTerms(self::TAXONOMY, $pairs);

        $allowed = array_fill_keys(array_keys($pairs), true);
        $terms = get_terms([
            'taxonomy' => self::TAXONOMY,
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
                wp_delete_term((int) $term->term_id, self::TAXONOMY);
            }
        }

        update_option(self::optionKey(), '1', true);
    }
}
