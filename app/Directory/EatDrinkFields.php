<?php

declare(strict_types=1);

namespace App\Directory;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Editor fields for individual Eat & Drink venues — same shape as
 * {@see ShopFields}: a logo (used on the olive card tile) and a short
 * "Open Today" line under the title. Single venue layout uses flexible
 * content for the rest of the page.
 */
final class EatDrinkFields
{
    public static function register(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        $group = new FieldsBuilder('group_culvers_eat_drink_directory', [
            'title' => __('Eat & Drink listing fields', 'culvers'),
        ]);

        $group->addImage('eat_drink_logo', [
            'label' => __('Logo (card)', 'culvers'),
            'instructions' => __('Shown on the olive card tile. Falls back to featured image if empty.', 'culvers'),
            'return_format' => 'array',
            'preview_size' => 'medium',
            'library' => 'all',
        ]);

        $group->addText('eat_drink_hours_summary', [
            'label' => __('Opening hours line', 'culvers'),
            'instructions' => __('Short line under the title on cards, e.g. “Open Today 9am – 9pm”.', 'culvers'),
            'default_value' => '',
            'placeholder' => __('Open Today 9am – 9pm', 'culvers'),
        ]);

        $group->setLocation('post_type', '==', 'culvers_eat_drink');

        acf_add_local_field_group($group->build());
    }
}
