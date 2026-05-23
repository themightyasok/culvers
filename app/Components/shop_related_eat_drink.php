<?php

/**
 * Eat & drink detail — "More flavours to discover" row using directory card styling (up to four picks).
 */

use App\Helpers\Component;

return [
    'label' => __('Eat & drink — related venues', 'culvers'),
    'display' => 'block',
    'main' => [
        'eat_drink_related_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'default_value' => __('More flavours to discover', 'culvers'),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'eat_drink_related_heading_level' => Component::headingLevelField(null, false, 2, '30'),
        'eat_drink_related_view_all_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('View all URL', 'culvers'),
                'instructions' => __('Typically the eat & drink directory archive.', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'eat_drink_related_view_all_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('View all label', 'culvers'),
                'default_value' => __('View all', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
    ],
    'items' => [
        'eat_drink_related_posts' => [
            'type' => 'post_object',
            'options' => [
                'label' => __('Venues', 'culvers'),
                'instructions' => __(
                    'Pick up to four directory entries (current venue is skipped on the front end).',
                    'culvers'
                ),
                'post_type' => ['culvers_eat_drink'],
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
