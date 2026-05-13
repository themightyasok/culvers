<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Eat & Drink directory — bottom three-card strip (News / Events / Offers CPT pills or WP category override).
 *
 * @see ArchiveStoriesThreeCard
 * @see filter culvers_eat_drink_archive_three_card_component
 */
final class EatDrinkArchiveThreeCard
{
    /**
     * @return array<string, mixed>|null
     */
    public static function componentOrNull(): ?array
    {
        return ArchiveStoriesThreeCard::componentOrNull(
            EatDrinkArchiveFields::FIELD_PREFIX,
            'culvers_eat_drink_archive_three_card_component'
        );
    }
}
