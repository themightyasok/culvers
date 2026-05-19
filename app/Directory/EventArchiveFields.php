<?php

declare(strict_types=1);

namespace App\Directory;

use App\Admin\AdminMenu;

/**
 * Theme options + archive wiring for `/whats-on/` (events archive).
 *
 * Mirrors {@see ShopArchiveFields}: the archive header reuses the shared
 * {@see resources/views/components/image-hero.blade.php} component
 * (~half-viewport-tall image banner with title + spaced subtitle stacked
 * vertically), with optional intro copy below.
 *
 * Unlike Shop / Eat & Drink / Careers, the What's On grid is unfiltered
 * by design — see {@see resources/views/archive-culvers-event.blade.php}.
 */
final class EventArchiveFields
{
    public const OPTION_PAGE_SLUG = 'culvers-events-archive';

    public const FIELD_PREFIX = 'events_archive';

    public static function register(): void
    {
        ArchiveHeroFields::register([
            'option_slug' => self::OPTION_PAGE_SLUG,
            'menu_title' => __('What’s On directory', 'culvers'),
            'page_title' => __('What’s On directory', 'culvers'),
            'description' => __('Hero and copy for the events archive (/whats-on/).', 'culvers'),
            'icon' => 'dashicons-calendar-alt',
            'position' => AdminMenu::POS_EVENTS_DIRECTORY,
            'group_key' => 'group_culvers_events_archive_options',
            'group_title' => __('What’s On directory archive', 'culvers'),
            'field_prefix' => self::FIELD_PREFIX,
            'hero_message_title' => __('What’s On directory hero', 'culvers'),
            'hero_message_body' => __(
                'Static "header hero" image band (Figma 51:9360 — 1440×646) that bleeds under the site header. Add an image plus title + subtitle below.',
                'culvers'
            ),
            'intro_field_label' => __('Intro paragraph (/whats-on/)', 'culvers'),
            'intro_field_instructions' => __(
                'Centered below the hero. Leave blank to use the default What’s On line.',
                'culvers'
            ),
        ]);
    }
}
