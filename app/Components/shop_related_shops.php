<?php

/**
 * Shop detail — "More shops" row using directory card styling (up to four picks).
 */

use App\Helpers\Component;

return [
    'label' => __('Shop — related shops', 'culvers'),
    'display' => 'block',
    'main' => [
        'related_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'default_value' => __('More shops you might enjoy', 'culvers'),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'related_heading_level' => Component::headingLevelField(null, false, 2, '30'),
        'related_view_all_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('View all URL', 'culvers'),
                'instructions' => __('Typically the shop directory archive.', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'related_view_all_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('View all label', 'culvers'),
                'default_value' => __('View all', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
    ],
    'items' => [
        'related_shop_posts' => [
            'type' => 'post_object',
            'options' => [
                'label' => __('Shops', 'culvers'),
                'instructions' => __(
                    'Pick up to four directory entries (current shop is skipped on the front end).',
                    'culvers'
                ),
                'post_type' => ['culvers_shop'],
                'taxonomy' => [],
                'allow_null' => 1,
                'multiple' => 1,
                'max' => 4,
                'return_format' => 'object',
                'ui' => 1,
            ],
        ],
    ],
];
