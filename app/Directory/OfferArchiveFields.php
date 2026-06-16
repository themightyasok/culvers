<?php

declare(strict_types=1);

namespace App\Directory;

use App\Admin\AdminMenu;

/**
 * Theme options + archive wiring for `/latest-offers/`.
 *
 * Mirrors {@see EventArchiveFields}: same shared
 * {@see resources/views/components/image-hero.blade.php} component
 * (~half-viewport image banner with title + spaced subtitle stacked
 * vertically), with optional intro copy below.
 */
final class OfferArchiveFields extends AbstractArchiveFields
{
    public const OPTION_PAGE_SLUG = 'culvers-offers-archive';

    public const FIELD_PREFIX = 'offers_archive';

    /**
     * @return array<string, mixed>
     */
    protected static function archiveOptions(): array
    {
        return [
            'option_slug' => self::OPTION_PAGE_SLUG,
            'menu_title' => __('Latest Offers directory', 'culvers'),
            'page_title' => __('Latest Offers directory', 'culvers'),
            'description' => __('Hero and copy for the offers archive (/latest-offers/).', 'culvers'),
            'icon' => 'dashicons-tag',
            'position' => AdminMenu::POS_OFFERS_DIRECTORY,
            'group_key' => 'group_culvers_offers_archive_options',
            'group_title' => __('Latest Offers directory archive', 'culvers'),
            'hero_message_title' => __('Latest Offers directory hero', 'culvers'),
            'intro_field_label' => __('Intro paragraph (/latest-offers/)', 'culvers'),
            'intro_field_instructions' => __(
                'Centered below the hero. Leave blank to use the default Latest Offers line.',
                'culvers'
            ),
        ];
    }
}
