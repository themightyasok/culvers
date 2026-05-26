<?php

declare(strict_types=1);

namespace App\Directory;

use App\Helpers\ThreeCardBlock;
use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Reusable Theme Options rows for News / Events / Offers (directory CPT tabs) vs blog-category override.
 *
 * Registered under `{fieldPrefixRoot}_three_card_*` alongside each directory archive (shops, eat-and-drink, …).
 */
final class ArchiveStoriesThreeCardFields
{
    /**
     * @param  string  $fieldPrefixRoot  e.g. {@see ShopArchiveFields::FIELD_PREFIX} or {@see EatDrinkArchiveFields::FIELD_PREFIX}.
     */
    public static function attach(FieldsBuilder $group, string $fieldPrefixRoot, string $belowGridPhrase): void
    {
        $stem = $fieldPrefixRoot . '_three_card';

        $body = sprintf(
            /* translators: %s: phrase such as “below the shop listing”. */
            __(
                'Same three-card block as flexible layouts: pills plus three cards %s. '
                . 'Choose manual cards (image / video), directory CPT tabs (News / Events / Offers by default), '
                . 'or blog category tabs.',
                'culvers'
            ),
            $belowGridPhrase
        );

        $group->addMessage(__('Latest stories strip', 'culvers'), $body, ['new_lines' => 'wpautop']);

        $group->addTrueFalse("{$stem}_enabled", [
            'label' => __('Show latest stories strip', 'culvers'),
            'instructions' => __('Heading, filters, three cards, and View all link.', 'culvers'),
            'default_value' => 1,
            'ui' => 1,
        ]);

        $group->addText("{$stem}_heading", [
            'label' => __('Stories heading', 'culvers'),
            'instructions' => __('Leave blank for the default line.', 'culvers'),
            'default_value' => '',
        ]);

        $group->addRadio("{$stem}_source", [
            'label' => __('Card source', 'culvers'),
            'instructions' => __(
                'Manual — pick up to three cards. Directory — News / Events / Offers tabs (default). '
                    . 'Blog — WordPress category tabs.',
                'culvers'
            ),
            'choices' => [
                'manual' => __('Manual (up to three cards)', 'culvers'),
                'cpt' => __('Directory posts (News / Events / Offers)', 'culvers'),
                'blog' => __('Blog posts (category tabs)', 'culvers'),
            ],
            'default_value' => 'cpt',
            'layout' => 'horizontal',
            'return_format' => 'value',
        ]);

        self::attachManualCardsRepeater($group, $stem);

        $group->addTaxonomy("{$stem}_category_tabs", [
            'label' => __('Category tabs', 'culvers'),
            'instructions' => __(
                'Pill order follows your selection — each pill lists recent blog posts in that category.',
                'culvers'
            ),
            'taxonomy' => 'category',
            'field_type' => 'multi_select',
            'return_format' => 'id',
            'allow_null' => 1,
            'conditional_logic' => [[[
                'field' => "{$stem}_source",
                'operator' => '==',
                'value' => 'blog',
            ]]],
        ]);

        $group->addNumber("{$stem}_posts_per_tab", [
            'label' => __('Items per tab / row', 'culvers'),
            'instructions' => __('How many cards per active tab — default directory CPT strips use three.', 'culvers'),
            'default_value' => 3,
            'min' => 1,
            'max' => 12,
            'step' => 1,
            'conditional_logic' => [[[
                'field' => "{$stem}_source",
                'operator' => '!=',
                'value' => 'manual',
            ]]],
        ]);

        $group->addUrl("{$stem}_view_all_url", [
            'label' => __('View all URL', 'culvers'),
            'instructions' => __('Defaults to the posts page when left blank.', 'culvers'),
            'default_value' => '',
            'conditional_logic' => [[[
                'field' => "{$stem}_source",
                'operator' => '!=',
                'value' => 'manual',
            ]]],
        ]);

        $group->addText("{$stem}_view_all_label", [
            'label' => __('View all label', 'culvers'),
            'instructions' => __('Defaults to “View all”.', 'culvers'),
            'default_value' => '',
            'conditional_logic' => [[[
                'field' => "{$stem}_source",
                'operator' => '!=',
                'value' => 'manual',
            ]]],
        ]);
    }

    private static function attachManualCardsRepeater(FieldsBuilder $group, string $stem): void
    {
        $repeaterKey = "{$stem}_items";
        $repeater = $group->addRepeater($repeaterKey, [
            'label' => __('Cards (manual)', 'culvers'),
            'instructions' => __(
                'Exactly three cards recommended. Video plays while hovered (respects reduced motion).',
                'culvers'
            ),
            'min' => 0,
            'max' => 3,
            'layout' => 'block',
            'button_label' => __('Add card', 'culvers'),
            'collapsed' => 'card_title',
            'conditional_logic' => [[[
                'field' => "{$stem}_source",
                'operator' => '==',
                'value' => 'manual',
            ]]],
        ]);

        foreach (ThreeCardBlock::manualCardSubFields() as $fieldName => $fieldDef) {
            $options = $fieldDef['options'] ?? [];
            $type = $fieldDef['type'] ?? 'text';

            match ($type) {
                'text' => $repeater->addText($fieldName, $options),
                'url' => $repeater->addUrl($fieldName, $options),
                'radio' => $repeater->addRadio($fieldName, $options),
                'image' => $repeater->addImage($fieldName, $options),
                'file' => $repeater->addFile($fieldName, $options),
                default => null,
            };
        }

        $repeater->endRepeater();
    }
}
