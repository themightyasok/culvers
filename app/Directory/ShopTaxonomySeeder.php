<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Default shop categories / retailer types matching Figma mega-menu labels (English).
 */
final class ShopTaxonomySeeder
{
    private const OPTION_KEY = 'culvers_shop_terms_seeded';

    public static function maybeSeed(): void
    {
        if ((bool) get_option(self::OPTION_KEY, false)) {
            return;
        }

        foreach (self::categoryNames() as $name) {
            if ($name === '') {
                continue;
            }
            if (term_exists($name, 'culvers_shop_category')) {
                continue;
            }
            wp_insert_term($name, 'culvers_shop_category');
        }

        foreach (self::retailerTypeNames() as $name) {
            if ($name === '') {
                continue;
            }
            if (term_exists($name, 'culvers_shop_type')) {
                continue;
            }
            wp_insert_term($name, 'culvers_shop_type');
        }

        update_option(self::OPTION_KEY, '1', true);
    }

    /**
     * @return list<string>
     */
    private static function categoryNames(): array
    {
        return [
            __('Beauty & Wellbeing', 'culvers'),
            __('Fashion', 'culvers'),
            __('Jewellery', 'culvers'),
            __('Toys & Gifts', 'culvers'),
            __('Technology', 'culvers'),
            __('Services', 'culvers'),
            __('Home', 'culvers'),
        ];
    }

    /**
     * @return list<string>
     */
    private static function retailerTypeNames(): array
    {
        return [
            __('National store', 'culvers'),
            __('Independent', 'culvers'),
        ];
    }
}
