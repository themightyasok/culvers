<?php

declare(strict_types=1);

namespace App\Directory;

use App\Admin\AdminMenu;

/**
 * Theme options + archive wiring for `/latest-news/`.
 *
 * Mirrors {@see OfferArchiveFields}: the archive header reuses the shared
 * {@see resources/views/components/image-hero.blade.php} component
 * (~half-viewport image banner with title + spaced subtitle stacked
 * vertically), with optional intro copy below.
 */
final class NewsArchiveFields
{
    public const OPTION_PAGE_SLUG = 'culvers-news-archive';

    public const FIELD_PREFIX = 'news_archive';

    public static function register(): void
    {
        ArchiveHeroFields::register([
            'option_slug' => self::OPTION_PAGE_SLUG,
            'menu_title' => __('Latest News directory', 'culvers'),
            'page_title' => __('Latest News directory', 'culvers'),
            'description' => __('Hero and copy for the news archive (/latest-news/).', 'culvers'),
            'icon' => 'dashicons-megaphone',
            'position' => AdminMenu::POS_NEWS_DIRECTORY,
            'group_key' => 'group_culvers_news_archive_options',
            'group_title' => __('Latest News directory archive', 'culvers'),
            'field_prefix' => self::FIELD_PREFIX,
            'hero_message_title' => __('Latest News directory hero', 'culvers'),
            'hero_message_body' => __(
                'Static "header hero" image band (Figma 51:9360 — 1440×646) that bleeds under the site header. Add an image plus title + subtitle below.',
                'culvers'
            ),
            'intro_field_label' => __('Intro paragraph (/latest-news/)', 'culvers'),
            'intro_field_instructions' => __(
                'Centered below the hero. Leave blank to use the default Latest News line.',
                'culvers'
            ),
        ]);
    }
}
