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
            'instructions' => __(
                'Shown on the olive directory tile (max ~120px tall). Use a transparent PNG. ' .
                'Wide wordmarks may look smaller in the shop page hero than on the tile — ' .
                'upload a square/emblem variant in the hero row if needed, or rely on auto wide-lockup sizing.',
                'culvers'
            ),
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

        $eatDrinkTypeChoices = [];
        foreach (DirectoryFilterDefinitions::eatDrinkCategoryPairs() as $slug => $label) {
            $eatDrinkTypeChoices[$slug] = $label;
        }

        $group->addCheckbox('shop_eat_drink_filter_types', [
            'label' => __('Eat & Drink filter categories', 'culvers'),
            'instructions' => __(
                'When this shop is cross-listed on /eat-drink/, pick which sidebar filters ' .
                'should include it (e.g. Cafés for Hotel Chocolat).',
                'culvers'
            ),
            'choices' => $eatDrinkTypeChoices,
            'layout' => 'vertical',
            'return_format' => 'value',
            'conditional_logic' => [[[
                'field' => 'shop_also_eat_drink',
                'operator' => '==',
                'value' => '1',
            ]]],
        ]);

        $group->addText('opening_hours_summary', [
            'label' => __('Opening hours line', 'culvers'),
            'instructions' => __(
                'Short line under the title on directory cards. Usually auto-synced from the ' .
                'Opening hours component (weekday highlights) — only edit manually if you need ' .
                'a fixed override.',
                'culvers'
            ),
            'default_value' => '',
            'placeholder' => __('Open today 9am – 5:30pm', 'culvers'),
        ]);

        $group->setLocation('post_type', '==', 'culvers_shop');

        acf_add_local_field_group($group->build());
    }
}
