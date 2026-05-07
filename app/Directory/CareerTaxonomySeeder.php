<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Default career-department names for the Careers directory.
 *
 * Seeded once on first init so the CMS isn't empty when an editor opens
 * Careers → Departments.
 */
final class CareerTaxonomySeeder
{
    private const OPTION_KEY = 'culvers_career_terms_seeded';

    public static function maybeSeed(): void
    {
        if ((bool) get_option(self::OPTION_KEY, false)) {
            return;
        }

        foreach (self::departmentNames() as $name) {
            if ($name === '' || term_exists($name, 'culvers_career_department')) {
                continue;
            }
            wp_insert_term($name, 'culvers_career_department');
        }

        update_option(self::OPTION_KEY, '1', true);
    }

    /**
     * @return list<string>
     */
    private static function departmentNames(): array
    {
        return [
            __('Centre Management', 'culvers'),
            __('Customer Experience', 'culvers'),
            __('Operations & Maintenance', 'culvers'),
            __('Security', 'culvers'),
            __('Marketing & Events', 'culvers'),
            __('Cleaning', 'culvers'),
            __('In-store roles', 'culvers'),
        ];
    }
}
