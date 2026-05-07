<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Theme options + archive wiring for `/eat-drink/`.
 *
 * Mirrors {@see ShopArchiveFields}: the archive header reuses the shared
 * {@see resources/views/components/image-hero.blade.php} component (static
 * "header hero" Figma pattern, ~half-viewport tall on desktop with title
 * + spaced uppercase subtitle stacked vertically) so the page reads
 * identical to `/shops/`, with optional intro copy below.
 */
final class EatDrinkArchiveFields
{
    public const OPTION_PAGE_SLUG = 'culvers-eat-drink-archive';

    public const FIELD_PREFIX = 'eat_drink_archive';

    public static function register(): void
    {
        ArchiveHeroFields::register([
            'option_slug' => self::OPTION_PAGE_SLUG,
            'menu_title' => __('Eat & Drink directory', 'culvers'),
            'page_title' => __('Eat & Drink directory', 'culvers'),
            'description' => __('Hero and copy for the venues archive (/eat-drink/).', 'culvers'),
            'icon' => 'dashicons-coffee',
            'position' => 62,
            'group_key' => 'group_culvers_eat_drink_archive_options',
            'group_title' => __('Eat & Drink directory archive', 'culvers'),
            'field_prefix' => self::FIELD_PREFIX,
            'hero_message_title' => __('Eat & Drink directory hero', 'culvers'),
            'hero_message_body' => __(
                'Static "header hero" image band (Figma 51:9360 — 1440×646) that bleeds under the site header. Add an image plus title + subtitle below.',
                'culvers'
            ),
            'intro_field_label' => __('Intro paragraph (/eat-drink/)', 'culvers'),
            'intro_field_instructions' => __(
                'Centered below the hero. Leave blank to use the default Eat & Drink line.',
                'culvers'
            ),
        ]);
    }
}
