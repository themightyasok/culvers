<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Default event category names for the What's On directory.
 *
 * Seeded once on first init so the CMS isn't empty when an editor opens
 * What's On → Event categories.
 */
final class EventTaxonomySeeder extends AbstractFlatListTaxonomySeeder
{
    protected static function optionKey(): string
    {
        return 'culvers_event_terms_seeded';
    }

    protected static function taxonomy(): string
    {
        return 'culvers_event_category';
    }

    /**
     * @return list<string>
     */
    protected static function termNames(): array
    {
        return [
            __('Family', 'culvers'),
            __('Music', 'culvers'),
            __('Workshop', 'culvers'),
            __('Seasonal', 'culvers'),
            __('Wellbeing', 'culvers'),
            __('Community', 'culvers'),
        ];
    }
}
