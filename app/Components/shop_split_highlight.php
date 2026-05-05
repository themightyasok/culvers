<?php

/**
 * Shop detail — 60/40 split: Deep Moss content column + flush lifestyle image (rounded frame).
 */

return [
    'label' => __('Shop — split highlight', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'split_kicker' => [
            'type' => 'text',
            'options' => [
                'label' => __('Kicker line', 'culvers'),
                'instructions' => __('First line in Glowleaf serif (e.g. Piercing Parlour).', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'split_headline' => [
            'type' => 'text',
            'options' => [
                'label' => __('Headline line', 'culvers'),
                'instructions' => __('Second serif line (e.g. Now Open).', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'split_body' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Body', 'culvers'),
                'instructions' => __('White sans body copy and lists.', 'culvers'),
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'media_upload' => 0,
            ],
        ],
        'split_cta_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('CTA label', 'culvers'),
                'instructions' => __('Leave blank to hide.', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'split_cta_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('CTA URL', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'split_image' => [
            'type' => 'image',
            'options' => [
                'label' => __('Right column image', 'culvers'),
                'instructions' => __('Portrait lifestyle crop; fills the 40% column.', 'culvers'),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
        ],
        'tab_padding' => [
            'type' => 'tab',
            'options' => ['label' => __('Padding', 'culvers')],
        ],
    ],
];
