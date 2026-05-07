<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Default event category names for the What's On directory.
 *
 * Seeded once on first init so the CMS isn't empty when an editor opens
 * What's On → Event categories.
 */
final class EventTaxonomySeeder
{
    private const OPTION_KEY = 'culvers_event_terms_seeded';

    public static function maybeSeed(): void
    {
        if ((bool) get_option(self::OPTION_KEY, false)) {
            return;
        }

        foreach (self::categoryNames() as $name) {
            if ($name === '' || term_exists($name, 'culvers_event_category')) {
                continue;
            }
            wp_insert_term($name, 'culvers_event_category');
        }

        update_option(self::OPTION_KEY, '1', true);
    }

    /**
     * @return list<string>
     */
    private static function categoryNames(): array
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
