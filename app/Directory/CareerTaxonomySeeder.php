<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Default career-department names for the Careers directory.
 *
 * Seeded once on first init so the CMS isn't empty when an editor opens
 * Careers → Departments.
 */
final class CareerTaxonomySeeder extends AbstractFlatListTaxonomySeeder
{
    protected static function optionKey(): string
    {
        return 'culvers_career_terms_seeded';
    }

    protected static function taxonomy(): string
    {
        return 'culvers_career_department';
    }

    /**
     * @return list<string>
     */
    protected static function termNames(): array
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
