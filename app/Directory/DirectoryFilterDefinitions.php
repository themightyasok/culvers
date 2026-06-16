<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Figma Developer Release — directory archive sidebar filters.
 *
 * @see KoBl6rTY98YnvusBgKLx4A
 * @see frame 51:7183 / 51:7443 — Shopping Directory (Category + Retailer type)
 * @see frame 51:7657 — Food Directory (Category + Retailer type)
 * @see frame 51:7805 — Careers Directory (Department + Contract type)
 */
final class DirectoryFilterDefinitions
{
    /**
     * @return list<array{slug: string, name: string}>
     */
    public static function shopCategories(): array
    {
        return self::pairsToOptions(self::shopCategoryPairs());
    }

    /**
     * @return list<array{slug: string, name: string}>
     */
    public static function shopRetailerTypes(): array
    {
        return self::pairsToOptions(self::shopRetailerTypePairs());
    }

    /**
     * Eat & Drink — Figma lists these under the first sidebar group (frame 51:7657).
     * Mega-menu deep links use `?type={slug}`; cards expose the same slugs on
     * `data-type-slugs`.
     *
     * @return list<array{slug: string, name: string}>
     */
    public static function eatDrinkCategories(): array
    {
        return self::pairsToOptions(self::eatDrinkCategoryPairs());
    }

    /**
     * @return array<string, string> slug => translated label
     */
    public static function shopCategoryPairs(): array
    {
        return [
            'beauty-wellbeing' => __('Beauty & Wellbeing', 'culvers'),
            'fashion' => __('Fashion', 'culvers'),
            'jewellery' => __('Jewellery', 'culvers'),
            'toys-gifts' => __('Toys & Gifts', 'culvers'),
            'technology' => __('Technology', 'culvers'),
            'speciality' => __('Speciality', 'culvers'),
            'home' => __('Home', 'culvers'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function shopRetailerTypePairs(): array
    {
        return [
            'national-store' => __('National store', 'culvers'),
            'independent' => __('Independent', 'culvers'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function eatDrinkCategoryPairs(): array
    {
        return [
            'grab-go' => __('Grab & Go', 'culvers'),
            'healthy-options' => __('Healthy Options', 'culvers'),
            'cafes' => __('Cafés', 'culvers'),
        ];
    }

    /**
     * @param array<string, string> $pairs
     * @return list<array{slug: string, name: string}>
     */
    private static function pairsToOptions(array $pairs): array
    {
        $out = [];
        foreach ($pairs as $slug => $name) {
            $out[] = ['slug' => $slug, 'name' => $name];
        }

        return $out;
    }

    /**
     * Sync taxonomy terms to the Figma filter lists (creates missing, renames by slug).
     *
     * @param array<string, string> $pairs
     */
    public static function syncTaxonomyTerms(string $taxonomy, array $pairs): void
    {
        foreach ($pairs as $slug => $name) {
            $existing = get_term_by('slug', $slug, $taxonomy);
            if ($existing instanceof \WP_Term) {
                if ($existing->name !== $name) {
                    wp_update_term((int) $existing->term_id, $taxonomy, ['name' => $name]);
                }
                continue;
            }
            $inserted = wp_insert_term($name, $taxonomy, ['slug' => $slug]);
            if ($inserted instanceof \WP_Error && $inserted->get_error_code() === 'term_exists') {
                $termId = (int) $inserted->get_error_data();
                if ($termId > 0) {
                    wp_update_term($termId, $taxonomy, ['name' => $name, 'slug' => $slug]);
                }
            }
        }
    }
}
