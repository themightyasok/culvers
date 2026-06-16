<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Default shop categories / retailer types matching Figma Shopping Directory (51:7183).
 */
final class ShopTaxonomySeeder extends AbstractTaxonomySeeder
{
    protected static function optionKey(): string
    {
        return 'culvers_shop_terms_seeded';
    }

    protected static function performSeed(): void
    {
        DirectoryFilterDefinitions::syncTaxonomyTerms(
            'culvers_shop_category',
            DirectoryFilterDefinitions::shopCategoryPairs()
        );
        DirectoryFilterDefinitions::syncTaxonomyTerms(
            'culvers_shop_type',
            DirectoryFilterDefinitions::shopRetailerTypePairs()
        );
    }

    public static function syncNow(): void
    {
        self::performSeed();
        update_option(self::optionKey(), '1', true);
    }
}
