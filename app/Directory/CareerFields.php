<?php

declare(strict_types=1);

namespace App\Directory;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Editor fields for individual career roles — the short summary lines that
 * surface on the /careers/ listing card (employment type, location). The
 * full role description and "About the role / responsibilities / what we
 * offer" sections live on the single via the `career_detail` flexible
 * component.
 */
final class CareerFields
{
    public static function register(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        $group = new FieldsBuilder('group_culvers_career_directory', [
            'title' => __('Career listing fields', 'culvers'),
        ]);

        $group->addText('career_employment_type', [
            'label' => __('Employment type', 'culvers'),
            'instructions' => __('e.g. "Full time", "Part time", "Casual", "Apprenticeship".', 'culvers'),
            'placeholder' => __('Full time', 'culvers'),
            'wrapper' => ['width' => '50'],
        ]);

        $group->addText('career_location', [
            'label' => __('Location', 'culvers'),
            'instructions' => __('e.g. "Culver Square, Colchester" or "On-site".', 'culvers'),
            'placeholder' => __('Culver Square, Colchester', 'culvers'),
            'wrapper' => ['width' => '50'],
        ]);

        $group->addText('career_employer', [
            'label' => __('Employer (if not Culver Square)', 'culvers'),
            'instructions' => __('Optional. Leave blank when the role sits inside the centre team.', 'culvers'),
            'placeholder' => __('Independent retailer name', 'culvers'),
        ]);

        $group->addImage('career_employer_logo', [
            'label' => __('Employer logo', 'culvers'),
            'instructions' => __(
                'Brand logo for the listing card (white artwork preferred — it is rendered on the dark moss tile). Falls back to the role title when empty.',
                'culvers'
            ),
            'return_format' => 'array',
            'preview_size' => 'medium',
        ]);

        $group->setLocation('post_type', '==', 'culvers_career');

        acf_add_local_field_group($group->build());
    }
}
