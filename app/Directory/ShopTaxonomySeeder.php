<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Default shop categories / retailer types matching Figma Shopping Directory (51:7183).
 */
final class ShopTaxonomySeeder
{
    private const OPTION_KEY = 'culvers_shop_terms_seeded';

    public static function maybeSeed(): void
    {
        if ((bool) get_option(self::OPTION_KEY, false)) {
            return;
        }

        DirectoryFilterDefinitions::syncTaxonomyTerms(
            'culvers_shop_category',
            DirectoryFilterDefinitions::shopCategoryPairs()
        );
        DirectoryFilterDefinitions::syncTaxonomyTerms(
            'culvers_shop_type',
            DirectoryFilterDefinitions::shopRetailerTypePairs()
        );

        update_option(self::OPTION_KEY, '1', true);
    }

    public static function syncNow(): void
    {
        DirectoryFilterDefinitions::syncTaxonomyTerms(
            'culvers_shop_category',
            DirectoryFilterDefinitions::shopCategoryPairs()
        );
        DirectoryFilterDefinitions::syncTaxonomyTerms(
            'culvers_shop_type',
            DirectoryFilterDefinitions::shopRetailerTypePairs()
        );
        update_option(self::OPTION_KEY, '1', true);
    }
}
