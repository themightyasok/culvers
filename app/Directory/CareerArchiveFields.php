<?php

declare(strict_types=1);

namespace App\Directory;

use App\Admin\AdminMenu;
use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Theme options + archive wiring for `/careers/`.
 *
 * Mirrors {@see ShopArchiveFields}: the archive header reuses the shared
 * {@see resources/views/components/image-hero.blade.php} component
 * (~half-viewport-tall image banner with title + spaced subtitle stacked
 * vertically), with optional intro copy below.
 */
final class CareerArchiveFields extends AbstractArchiveFields
{
    public const OPTION_PAGE_SLUG = 'culvers-careers-archive';

    public const FIELD_PREFIX = 'careers_archive';

    /**
     * @return array<string, mixed>
     */
    protected static function archiveOptions(): array
    {
        return [
            'option_slug' => self::OPTION_PAGE_SLUG,
            'menu_title' => __('Careers directory', 'culvers'),
            'page_title' => __('Careers directory', 'culvers'),
            'description' => __('Hero and copy for the careers archive (/careers/).', 'culvers'),
            'icon' => 'dashicons-businessperson',
            'position' => AdminMenu::POS_CAREERS_DIRECTORY,
            'group_key' => 'group_culvers_careers_archive_options',
            'group_title' => __('Careers directory archive', 'culvers'),
            'hero_message_title' => __('Careers directory hero', 'culvers'),
            'intro_field_label' => __('Intro paragraph (/careers/)', 'culvers'),
            'intro_field_instructions' => __(
                'Centered below the hero. Leave blank to use the default Careers line.',
                'culvers'
            ),
            'extra' => static function (FieldsBuilder $group): void {
                CareerArchiveContactCta::appendFields($group);
            },
        ];
    }
}
