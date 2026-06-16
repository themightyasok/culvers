<?php

declare(strict_types=1);

namespace App\Directory;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Editor fields for individual shop entries (directory cards + future single template).
 */
final class ShopFields
{
    public static function register(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        $group = new FieldsBuilder('group_culvers_shop_directory', [
            'title' => __('Shop listing fields', 'culvers'),
        ]);

        $group->addImage('shop_logo', [
            'label' => __('Logo (card)', 'culvers'),
            'instructions' => __('Shown on the olive card tile. Falls back to featured image if empty.', 'culvers'),
            'return_format' => 'array',
            'preview_size' => 'medium',
            'library' => 'all',
        ]);

        $group->addTrueFalse('shop_logo_preserve_colors', LogoPreserveColors::acfFieldDefinition());

        $group->addTrueFalse('shop_also_eat_drink', [
            'label' => __('Also list under Eat & Drink', 'culvers'),
            'instructions' => __(
                'Turn on for shops that also serve food or drink (e.g. a chocolatier with a café). ' .
                'The shop will appear on the Eat & Drink archive too, using this same listing — ' .
                'no separate Eat & Drink entry is needed.',
                'culvers'
            ),
            'ui' => 1,
            'ui_on_text' => __('Yes', 'culvers'),
            'ui_off_text' => __('No', 'culvers'),
            'default_value' => 0,
        ]);

        $group->addText('opening_hours_summary', [
            'label' => __('Opening hours line', 'culvers'),
            'instructions' => __('Short line under the title on cards, e.g. “Open today 9am – 5:30pm”.', 'culvers'),
            'default_value' => '',
            'placeholder' => __('Open today 9am – 5:30pm', 'culvers'),
        ]);

        $group->setLocation('post_type', '==', 'culvers_shop');

        acf_add_local_field_group($group->build());
    }
}
