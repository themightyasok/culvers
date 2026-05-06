<?php

/**
 * Shop detail — full-bleed image hero with optional logo lockup (Figma shop header; static image, not video hero).
 */

return [
    'label' => __('Shop — image hero', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'hero_image' => [
            'type' => 'image',
            'options' => [
                'label' => __('Hero image', 'culvers'),
                'instructions' => __('Wide lifestyle storefront shot (≈1440×746 tall band in developer designs).', 'culvers'),
                'preview_size' => 'large',
                'library' => 'all',
            ],
        ],
        'hero_image_mobile' => [
            'type' => 'image',
            'options' => [
                'label' => __('Hero image (mobile)', 'culvers'),
                'instructions' => __('Optional tighter crop for small screens.', 'culvers'),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
        ],
        'hero_logo' => [
            'type' => 'image',
            'options' => [
                'label' => __('Logo', 'culvers'),
                'instructions' => __('Center lockup over the hero (white artwork preferred). If empty, use the title lines below.', 'culvers'),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
        ],
        'hero_title_line' => [
            'type' => 'text',
            'options' => [
                'label' => __('Title line', 'culvers'),
                'instructions' => __('Large serif line when no logo is set (e.g. ACCESSORIZE).', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'hero_subtitle_line' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Subtitle line', 'culvers'),
                'instructions' => __('Small spaced sans line under the logo/title (e.g. LONDON).', 'culvers'),
                'rows' => 2,
                'new_lines' => 'br',
                'wrapper' => ['width' => '50'],
            ],
        ],
        'hero_overlay_opacity' => [
            'type' => 'number',
            'options' => [
                'label' => __('Image overlay darkness', 'culvers'),
                'instructions' => __('Solid black overlay opacity on the hero image (0–85). 0 = none, 70 = strong.', 'culvers'),
                'default_value' => 50,
                'min' => 0,
                'max' => 85,
                'step' => 1,
                'append' => '%',
            ],
        ],
        'tab_padding' => [
            'type' => 'tab',
            'options' => ['label' => __('Padding', 'culvers')],
        ],
    ],
];
