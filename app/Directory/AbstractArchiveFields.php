<?php

declare(strict_types=1);

namespace App\Directory;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Template-method base for the six directory archive theme-options screens.
 *
 * Each subclass declares its `OPTION_PAGE_SLUG` / `FIELD_PREFIX` constants and
 * implements {@see archiveOptions()} returning the per-CPT-specific labels.
 * Shared boilerplate ({@see ArchiveHeroFields::register()} dispatch, the
 * verbatim Figma description, and the late-static `field_prefix` wiring) lives
 * here so each `*ArchiveFields` class is just its config delta.
 *
 * @internal Extended by {@see ShopArchiveFields}, {@see EatDrinkArchiveFields},
 *           {@see EventArchiveFields}, {@see OfferArchiveFields},
 *           {@see NewsArchiveFields}, {@see CareerArchiveFields}.
 */
abstract class AbstractArchiveFields
{
    /** Subclasses override with their CPT-specific ACF field prefix. */
    public const FIELD_PREFIX = '';

    public static function register(): void
    {
        /** @var array{
         *     option_slug: string,
         *     menu_title: string,
         *     page_title: string,
         *     description: string,
         *     icon: string,
         *     position: int,
         *     group_key: string,
         *     group_title: string,
         *     hero_message_title: string,
         *     intro_field_label: string,
         *     intro_field_instructions: string,
         *     hero_message_body?: string,
         *     extra?: callable(FieldsBuilder): void
         * } $options */
        $options = static::archiveOptions();

        $config = $options + [
            'field_prefix' => static::FIELD_PREFIX,
            'hero_message_body' => __(
                'Static "header hero" image band (Figma 51:9360 — 1440×646) that bleeds under the site header. Add an image plus title + subtitle below.',
                'culvers'
            ),
        ];

        /** @var array{
         *     option_slug: string,
         *     menu_title: string,
         *     page_title: string,
         *     description: string,
         *     icon: string,
         *     position: int,
         *     group_key: string,
         *     group_title: string,
         *     field_prefix: string,
         *     hero_message_title: string,
         *     hero_message_body: string,
         *     intro_field_label: string,
         *     intro_field_instructions: string,
         *     extra?: callable(FieldsBuilder): void
         * } $config */
        ArchiveHeroFields::register($config);
    }

    /**
     * Per-CPT labels & options. `field_prefix` and `hero_message_body` are
     * filled in by {@see register()} and may be overridden by returning them
     * here.
     *
     * @return array<string, mixed>
     */
    abstract protected static function archiveOptions(): array;
}
