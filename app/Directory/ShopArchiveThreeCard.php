<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Shops directory — bottom three-card strip (News / Events / Offers CPT pills or WP category override).
 *
 * @see ArchiveStoriesThreeCard
 * @see filter culvers_shops_archive_three_card_component
 */
final class ShopArchiveThreeCard
{
    /**
     * @return array<string, mixed>|null
     */
    public static function componentOrNull(): ?array
    {
        return ArchiveStoriesThreeCard::componentOrNull(
            ShopArchiveFields::FIELD_PREFIX,
            'culvers_shops_archive_three_card_component'
        );
    }
}
