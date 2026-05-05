<?php

declare(strict_types=1);

namespace App\Directory;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Theme options + archive wiring for /shops/ (hero uses shared hero-slider component).
 */
final class ShopArchiveFields
{
    private static bool $optionsPageRegistered = false;

    public static function register(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        if (function_exists('acf_add_options_page') && ! self::$optionsPageRegistered) {
            acf_add_options_page([
                'page_title' => __('Shop directory', 'culvers'),
                'menu_title' => __('Shop directory', 'culvers'),
                'menu_slug' => 'culvers-directory',
                'capability' => 'edit_theme_options',
                'redirect' => false,
                'position' => 61,
                'icon_url' => 'dashicons-store',
                'description' => __('Hero and copy for the shops archive (/shops/).', 'culvers'),
            ]);
            self::$optionsPageRegistered = true;
        }

        $group = new FieldsBuilder('group_culvers_shop_archive_options', [
            'title' => __('Shop directory archive', 'culvers'),
        ]);

        $group->addMessage(
            __('Shop directory hero', 'culvers'),
            __(
                'The hero uses the same slider component as flexible pages. Add at least one slide for a hero to appear on /shops/.',
                'culvers'
            ),
            ['new_lines' => 'wpautop']
        );

        $repeater = $group->addRepeater('shops_archive_hero_slides', [
            'label' => __('Hero slides', 'culvers'),
            'min' => 0,
            'max' => 12,
            'layout' => 'block',
            'button_label' => __('Add slide', 'culvers'),
        ]);

        $repeater->addImage('slide_image', [
            'label' => __('Image (desktop)', 'culvers'),
            'return_format' => 'array',
            'preview_size' => 'medium',
            'required' => 1,
        ]);

        $repeater->addImage('slide_image_mobile', [
            'label' => __('Image (mobile, optional)', 'culvers'),
            'instructions' => __('Falls back to desktop image when empty.', 'culvers'),
            'return_format' => 'array',
            'preview_size' => 'medium',
        ]);

        $repeater->addTextarea('slide_headline', [
            'label' => __('Headline', 'culvers'),
            'rows' => 3,
            'new_lines' => 'br',
        ]);

        $repeater->addText('slide_kicker', [
            'label' => __('Sub-head / kicker', 'culvers'),
            'instructions' => __('Short uppercase line under the headline.', 'culvers'),
        ]);

        $repeater->addTextarea('slide_body', [
            'label' => __('Body', 'culvers'),
            'rows' => 4,
            'new_lines' => 'br',
        ]);

        $repeater->addText('slide_cta_label', [
            'label' => __('CTA label', 'culvers'),
        ]);

        $repeater->addUrl('slide_cta_url', [
            'label' => __('CTA URL', 'culvers'),
        ]);

        $repeater->endRepeater();

        $group->addSelect('shops_archive_hero_align', [
            'label' => __('Hero text alignment', 'culvers'),
            'choices' => [
                'left' => __('Left', 'culvers'),
                'center' => __('Center', 'culvers'),
                'right' => __('Right', 'culvers'),
            ],
            'default_value' => 'center',
            'return_format' => 'value',
        ]);

        $group->addTextarea('shops_archive_intro_copy', [
            'label' => __('Intro paragraph (/shops/)', 'culvers'),
            'instructions' => __('Centered below the hero. Leave blank for the default Culver Square directory line.', 'culvers'),
            'rows' => 4,
            'new_lines' => 'wpautop',
        ]);

        $group->addMessage(
            __('Latest stories strip', 'culvers'),
            __(
                'Three-up cards (same block as flexible pages) appear below the shop grid. '
                    . 'Choose category tabs for News / Events / Offers, or leave tabs empty '
                    . 'to pull the three latest posts.',
                'culvers'
            ),
            ['new_lines' => 'wpautop']
        );

        $group->addTrueFalse('shops_archive_three_card_enabled', [
            'label' => __('Show latest stories strip', 'culvers'),
            'instructions' => __('Heading, filters, three cards, and View all link.', 'culvers'),
            'default_value' => 1,
            'ui' => 1,
        ]);

        $group->addText('shops_archive_three_card_heading', [
            'label' => __('Stories heading', 'culvers'),
            'instructions' => __('Leave blank for the default line.', 'culvers'),
            'default_value' => '',
        ]);

        $group->addTaxonomy('shops_archive_three_card_category_tabs', [
            'label' => __('Category tabs', 'culvers'),
            'instructions' => __(
                'Order defines pill order (e.g. News, Events, Offers). Empty = one row from latest posts (no tabs).',
                'culvers'
            ),
            'taxonomy' => 'category',
            'field_type' => 'multi_select',
            'return_format' => 'id',
            'allow_null' => 1,
        ]);

        $group->addNumber('shops_archive_three_card_posts_per_tab', [
            'label' => __('Posts per tab / row', 'culvers'),
            'instructions' => __('Up to three columns; extra posts wrap on smaller breakpoints.', 'culvers'),
            'default_value' => 3,
            'min' => 1,
            'max' => 12,
            'step' => 1,
        ]);

        $group->addUrl('shops_archive_three_card_view_all_url', [
            'label' => __('View all URL', 'culvers'),
            'instructions' => __('Defaults to the posts page when left blank.', 'culvers'),
            'default_value' => '',
        ]);

        $group->addText('shops_archive_three_card_view_all_label', [
            'label' => __('View all label', 'culvers'),
            'instructions' => __('Defaults to “View all”.', 'culvers'),
            'default_value' => '',
        ]);

        $group->setLocation('options_page', '==', 'culvers-directory');

        acf_add_local_field_group($group->build());
    }
}
