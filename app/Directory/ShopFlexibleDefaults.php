<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * When a shop post has no saved flexible rows yet, provide the standard stack from the developer designs.
 *
 * Once rows exist (including an intentionally empty list), the stored value wins.
 */
final class ShopFlexibleDefaults
{
    public static function register(): void
    {
        add_filter('acf/load_value/name=components', [self::class, 'filterLoadComponents'], 10, 3);
    }

    /**
     * @param mixed $value
     * @param string|int|false $postId
     * @param array<string, mixed> $field
     * @return mixed
     */
    public static function filterLoadComponents($value, $postId, array $field)
    {
        unset($field);

        if (! is_numeric($postId)) {
            return $value;
        }

        $pid = (int) $postId;
        if (get_post_type($pid) !== 'culvers_shop') {
            return $value;
        }

        if (is_array($value)) {
            return $value;
        }

        if ($value !== null && $value !== false) {
            return $value;
        }

        return self::defaultLayoutRows();
    }

    /**
     * Developer-release order: hero → intro → split highlight → store details → opening hours →
     * centre map → related shops. Authors run {@see scripts/shop-single-populate-flexible.php}
     * to hydrate imagery and copy; placeholders keep registry keys apparent in ACF UI.
     *
     * @return list<array<string, mixed>>
     */
    public static function defaultLayoutRows(): array
    {
        return [
            ['acf_fc_layout' => 'image_hero'],
            ['acf_fc_layout' => 'shop_intro_block'],
            ['acf_fc_layout' => 'shop_split_highlight'],
            ['acf_fc_layout' => 'shop_store_details'],
            [
                'acf_fc_layout' => 'opening_hours',
            ],
            ['acf_fc_layout' => 'centre_map'],
            ['acf_fc_layout' => 'shop_related_shops'],
        ];
    }
}
