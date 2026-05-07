<?php

declare(strict_types=1);

namespace App\Directory;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Theme options + archive wiring for /shops/.
 *
 * The hero band uses the shared {@see ArchiveHeroFields} builder so every
 * directory archive (`/shops/`, `/eat-drink/`, `/whats-on/`, `/careers/`)
 * renders the same {@see resources/views/components/image-hero.blade.php}
 * component (image banner, ~half-viewport tall, glowleaf title + spaced
 * uppercase subtitle stacked vertically). Below the hero we keep a shop-
 * specific "Latest stories" three-card strip that no other directory needs.
 */
final class ShopArchiveFields
{
    public const OPTION_PAGE_SLUG = 'culvers-directory';

    public const FIELD_PREFIX = 'shops_archive';

    public static function register(): void
    {
        ArchiveHeroFields::register([
            'option_slug' => self::OPTION_PAGE_SLUG,
            'menu_title' => __('Shop directory', 'culvers'),
            'page_title' => __('Shop directory', 'culvers'),
            'description' => __('Hero, intro and stories strip for the shops archive (/shops/).', 'culvers'),
            'icon' => 'dashicons-store',
            'position' => 61,
            'group_key' => 'group_culvers_shop_archive_options',
            'group_title' => __('Shop directory archive', 'culvers'),
            'field_prefix' => self::FIELD_PREFIX,
            'hero_message_title' => __('Shop directory hero', 'culvers'),
            'hero_message_body' => __(
                'Static "header hero" band (Figma 51:9360 — 1440×646) that bleeds under the site header. Add an image plus title + subtitle below.',
                'culvers'
            ),
            'intro_field_label' => __('Intro paragraph (/shops/)', 'culvers'),
            'intro_field_instructions' => __(
                'Centered below the hero. Leave blank for the default Culver Square directory line.',
                'culvers'
            ),
            'extra' => static function (FieldsBuilder $group): void {
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
            },
        ]);
    }
}
