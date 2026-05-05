<?php

/**
 * Full-viewport hero carousel (Splide): slides with imagery, headline stack, CTA, glowleaf frame.
 */

return [
    'label' => __('Hero slider', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'hero_instructions' => [
            'type' => 'message',
            'options' => [
                'message' => __(
                    'Place this block first on the page for the intended full-bleed hero. The site header overlaps the imagery (fixed header). Use at least one slide.',
                    'culvers'
                ),
                'new_lines' => 'wpautop',
            ],
        ],
        'hero_slides' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Slides', 'culvers'),
                'min' => 1,
                'max' => 12,
                'layout' => 'block',
                'button_label' => __('Add slide', 'culvers'),
                'sub_fields' => [
                    'slide_image' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Image (desktop)', 'culvers'),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'required' => 1,
                        ],
                    ],
                    'slide_image_mobile' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Image (mobile, optional)', 'culvers'),
                            'instructions' => __('Falls back to desktop image when empty.', 'culvers'),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                        ],
                    ],
                    'slide_headline' => [
                        'type' => 'textarea',
                        'options' => [
                            'label' => __('Headline', 'culvers'),
                            'rows' => 3,
                            'new_lines' => 'br',
                        ],
                    ],
                    'slide_kicker' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Sub-head / kicker', 'culvers'),
                            'instructions' => __('Short uppercase line under the headline.', 'culvers'),
                        ],
                    ],
                    'slide_body' => [
                        'type' => 'textarea',
                        'options' => [
                            'label' => __('Body', 'culvers'),
                            'rows' => 4,
                            'new_lines' => 'br',
                        ],
                    ],
                    'slide_cta_label' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('CTA label', 'culvers'),
                        ],
                    ],
                    'slide_cta_url' => [
                        'type' => 'url',
                        'options' => [
                            'label' => __('CTA URL', 'culvers'),
                        ],
                    ],
                ],
            ],
        ],
        'hero_content_align' => [
            'type' => 'select',
            'options' => [
                'label' => __('Text alignment (horizontal)', 'culvers'),
                'instructions' => __('Copy block stays vertically centered.', 'culvers'),
                'choices' => [
                    'left' => __('Left', 'culvers'),
                    'center' => __('Center', 'culvers'),
                    'right' => __('Right', 'culvers'),
                ],
                'default_value' => 'center',
                'return_format' => 'value',
            ],
        ],
        'tab_padding' => [
            'type' => 'tab',
            'options' => ['label' => __('Padding', 'culvers')],
        ],
    ],
];
