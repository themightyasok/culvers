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
 * uppercase subtitle stacked vertically). Below the hero the **latest stories**
 * three-card strip is configured here; {@see EatDrinkArchiveFields} exposes the
 * same strip for `/eat-drink/` with its own options.
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
                ArchiveStoriesThreeCardFields::attach(
                    $group,
                    self::FIELD_PREFIX,
                    __('below the shop listing', 'culvers')
                );
            },
        ]);
    }
}
