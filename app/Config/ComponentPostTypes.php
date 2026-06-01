<?php

declare(strict_types=1);

namespace App\Config;

/**
 * Which flexible layouts appear in the ACF picker per post type.
 *
 * Keeps directory-only blocks off pages and marketing-only blocks off directory singles.
 * Canonical stacks for empty directory posts live in {@see \App\Directory\DirectoryFlexibleDefaults}.
 */
final class ComponentPostTypes
{
    /** Pre–post-type-split flexible field key still stored in `_components` on existing posts. */
    public const LEGACY_FLEXIBLE_FIELD_KEY = 'field_page_components_components';

    /** @var list<string> */
    private const PAGE_LAYOUTS = [
        'centre_map',
        'contact',
        'content_section',
        'faq',
        'hero_slider',
        'horizontal_scroller',
        'image_hero',
        'info_block',
        'leasing_agent_grid',
        'opening_hours',
        'section_header',
        'shop_split_highlight',
        'social_share',
        'text_image_slider',
        'three_card_block',
        'travel_calculator',
        'video_block',
    ];

    /** @var list<string> */
    private const SHOP_LAYOUTS = [
        'centre_map',
        'image_hero',
        'opening_hours',
        'shop_intro_block',
        'shop_related_shops',
        'shop_split_highlight',
        'shop_store_details',
    ];

    /** @var list<string> */
    private const EAT_DRINK_LAYOUTS = [
        'centre_map',
        'image_hero',
        'opening_hours',
        'shop_intro_block',
        'shop_related_eat_drink',
        'shop_split_highlight',
        'shop_store_details',
    ];

    /** @var list<string> */
    private const EVENT_LIKE_LAYOUTS = [
        'image_hero',
        'section_header',
        'shop_split_highlight',
        'social_share',
        'three_card_block',
    ];

    /** @var list<string> */
    private const EVENT_LAYOUTS = [
        'event_meta',
        'image_hero',
        'section_header',
        'shop_split_highlight',
        'social_share',
        'three_card_block',
    ];

    /** @var list<string> */
    private const CAREER_LAYOUTS = [
        'career_detail',
        'image_hero',
        'info_block',
        'shop_split_highlight',
    ];

    /**
     * ACF field groups to register — post types sharing an identical allowlist are grouped.
     *
     * @return list<array{group_key: string, post_types: list<string>, layouts: list<string>}>
     */
    public static function fieldGroupDefinitions(): array
    {
        return [
            [
                'group_key' => 'page',
                'post_types' => ['page'],
                'layouts' => self::PAGE_LAYOUTS,
            ],
            [
                'group_key' => 'shop',
                'post_types' => ['culvers_shop'],
                'layouts' => self::SHOP_LAYOUTS,
            ],
            [
                'group_key' => 'eat_drink',
                'post_types' => ['culvers_eat_drink'],
                'layouts' => self::EAT_DRINK_LAYOUTS,
            ],
            [
                'group_key' => 'event',
                'post_types' => ['culvers_event'],
                'layouts' => self::EVENT_LAYOUTS,
            ],
            [
                'group_key' => 'event_like',
                'post_types' => ['culvers_offer', 'culvers_news'],
                'layouts' => self::EVENT_LIKE_LAYOUTS,
            ],
            [
                'group_key' => 'career',
                'post_types' => ['culvers_career'],
                'layouts' => self::CAREER_LAYOUTS,
            ],
        ];
    }

    /**
     * ACF field key for the flexible `components` field on a given post type.
     */
    public static function flexibleFieldKeyForPostType(string $postType): ?string
    {
        foreach (self::fieldGroupDefinitions() as $definition) {
            if (in_array($postType, $definition['post_types'], true)) {
                return 'field_page_components_' . $definition['group_key'] . '_components';
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function allAssignedLayouts(): array
    {
        $layouts = [];

        foreach (self::fieldGroupDefinitions() as $definition) {
            foreach ($definition['layouts'] as $layout) {
                $layouts[] = $layout;
            }
        }

        return array_values(array_unique($layouts));
    }
}
