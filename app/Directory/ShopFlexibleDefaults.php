<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * @deprecated Use {@see DirectoryFlexibleDefaults} — kept for scripts/docs that
 *             reference the shop-only helper name.
 */
final class ShopFlexibleDefaults
{
    /**
     * @return list<array{acf_fc_layout: string}>
     */
    public static function defaultLayoutRows(): array
    {
        return DirectoryFlexibleDefaults::defaultLayoutRowsFor('culvers_shop');
    }
}
