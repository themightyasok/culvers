<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Default cuisine categories / venue types for the Eat & Drink directory.
 *
 * Seeded once on first init so the CMS isn't empty when an editor opens
 * Eat & Drink → Cuisine categories.
 */
final class EatDrinkTaxonomySeeder
{
    private const OPTION_KEY = 'culvers_eat_drink_terms_seeded';

    public static function maybeSeed(): void
    {
        if ((bool) get_option(self::OPTION_KEY, false)) {
            return;
        }

        foreach (self::cuisineNames() as $name) {
            if ($name === '' || term_exists($name, 'culvers_eat_drink_category')) {
                continue;
            }
            wp_insert_term($name, 'culvers_eat_drink_category');
        }

        foreach (self::venueTypeNames() as $name) {
            if ($name === '' || term_exists($name, 'culvers_eat_drink_type')) {
                continue;
            }
            wp_insert_term($name, 'culvers_eat_drink_type');
        }

        update_option(self::OPTION_KEY, '1', true);
    }

    /**
     * @return list<string>
     */
    private static function cuisineNames(): array
    {
        return [
            __('Coffee & Cake', 'culvers'),
            __('Casual Dining', 'culvers'),
            __('Italian', 'culvers'),
            __('Asian', 'culvers'),
            __('Burgers & Grill', 'culvers'),
            __('Bakery', 'culvers'),
            __('Healthy', 'culvers'),
            __('Sweet Treats', 'culvers'),
        ];
    }

    /**
     * @return list<string>
     */
    private static function venueTypeNames(): array
    {
        return [
            __('Restaurant', 'culvers'),
            __('Café', 'culvers'),
            __('Takeaway', 'culvers'),
            __('Bar', 'culvers'),
        ];
    }
}
