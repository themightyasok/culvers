<?php

declare(strict_types=1);

namespace App\Directory;

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
                . 'If you leave Category tabs empty, the pills default to News, Events and Offers (directory posts). '
                . 'Selecting WordPress categories here switches this strip to blog posts per category instead.',
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

        $group->addTaxonomy("{$stem}_category_tabs", [
            'label' => __('Category tabs', 'culvers'),
            'instructions' => __(
                'Optional override: pill order follows your selection — each pill lists recent '
                    . 'blog posts in that category (same as Flexible → Three card block → Blog source). '
                    . 'Leave empty to keep default News / Events / Offers CPT tabs.',
                'culvers'
            ),
            'taxonomy' => 'category',
            'field_type' => 'multi_select',
            'return_format' => 'id',
            'allow_null' => 1,
        ]);

        $group->addNumber("{$stem}_posts_per_tab", [
            'label' => __('Items per tab / row', 'culvers'),
            'instructions' => __('How many cards per active tab — default directory CPT strips use three.', 'culvers'),
            'default_value' => 3,
            'min' => 1,
            'max' => 12,
            'step' => 1,
        ]);

        $group->addUrl("{$stem}_view_all_url", [
            'label' => __('View all URL', 'culvers'),
            'instructions' => __('Defaults to the posts page when left blank.', 'culvers'),
            'default_value' => '',
        ]);

        $group->addText("{$stem}_view_all_label", [
            'label' => __('View all label', 'culvers'),
            'instructions' => __('Defaults to “View all”.', 'culvers'),
            'default_value' => '',
        ]);
    }
}
