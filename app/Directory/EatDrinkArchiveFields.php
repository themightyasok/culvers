<?php

declare(strict_types=1);

namespace App\Directory;

use App\Admin\AdminMenu;
use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Theme options + archive wiring for `/eat-drink/`.
 *
 * Mirrors {@see ShopArchiveFields}: the archive header reuses the shared
 * {@see resources/views/components/image-hero.blade.php} component (static
 * "header hero" Figma pattern, ~half-viewport tall on desktop with title
 * + spaced uppercase subtitle stacked vertically) so the page reads
 * identical to `/shops/`, with optional intro copy below.
 */
final class EatDrinkArchiveFields extends AbstractArchiveFields
{
    public const OPTION_PAGE_SLUG = 'culvers-eat-drink-archive';

    public const FIELD_PREFIX = 'eat_drink_archive';

    /**
     * @return array<string, mixed>
     */
    protected static function archiveOptions(): array
    {
        return [
            'option_slug' => self::OPTION_PAGE_SLUG,
            'menu_title' => __('Eat & Drink directory', 'culvers'),
            'page_title' => __('Eat & Drink directory', 'culvers'),
            'description' => __('Hero, intro copy, and optional stories strip for the venues archive (/eat-drink/).', 'culvers'),
            'icon' => 'dashicons-coffee',
            'position' => AdminMenu::POS_EAT_DRINK_DIRECTORY,
            'group_key' => 'group_culvers_eat_drink_archive_options',
            'group_title' => __('Eat & Drink directory archive', 'culvers'),
            'hero_message_title' => __('Eat & Drink directory hero', 'culvers'),
            'intro_field_label' => __('Intro paragraph (/eat-drink/)', 'culvers'),
            'intro_field_instructions' => __(
                'Centered below the hero. Leave blank to use the default Eat & Drink line.',
                'culvers'
            ),
            'extra' => static function (FieldsBuilder $group): void {
                ArchiveStoriesThreeCardFields::attach(
                    $group,
                    self::FIELD_PREFIX,
                    __('below the Eat & Drink listing', 'culvers')
                );
            },
        ];
    }
}
