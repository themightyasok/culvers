<?php

/**
 * Full-viewport hero carousel (Splide): slides with imagery, headline stack, CTA, glowleaf frame.
 *
 * Per-slide mobile imagery lives inside each slide row on the Items tab — there
 * are no block-level mobile overrides for this component.
 */

return [
    'label' => __('Hero slider', 'culvers'),
    'display' => 'block',
    'main' => [
        'hero_instructions' => [
            'type' => 'message',
            'options' => [
                'message' => __(
                    'Place this block first on the page for the intended full-bleed hero. The site header overlaps '
                    . 'the imagery (fixed header). Use at least one slide. '
                    . 'Optional per-slide mobile crops are set on each row in the <strong>Items</strong> tab '
                    . '(<em>Image — mobile (optional)</em>).',
                    'culvers'
                ),
                'new_lines' => 'wpautop',
                'wrapper' => ['class' => 'culvers-acf-help'],
                'esc_html' => 0,
            ],
        ],
        'hero_content_align' => [
            'type' => 'select',
            'options' => [
                'label' => __('Text alignment (horizontal)', 'culvers'),
                'instructions' => __('Copy block stays vertically centred.', 'culvers'),
                'choices' => [
                    'left' => __('Left', 'culvers'),
                    'center' => __('Center', 'culvers'),
                    'right' => __('Right', 'culvers'),
                ],
                'default_value' => 'center',
                'return_format' => 'value',
            ],
        ],
    ],
    'items' => [
        'hero_slides' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Slides', 'culvers'),
                'instructions' => __(
                    'Each slide can carry its own desktop and mobile image, headline stack, and CTA.',
                    'culvers'
                ),
                'min' => 1,
                'max' => 12,
                'layout' => 'block',
                'button_label' => __('Add slide', 'culvers'),
                'collapsed' => 'slide_headline',
                'sub_fields' => [
                    'slide_image' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Image — desktop / tablet', 'culvers'),
                            'instructions' => __('Default from the md breakpoint upward.', 'culvers'),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'required' => 1,
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'slide_image_mobile' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Image — mobile (optional)', 'culvers'),
                            'instructions' => __('Shown only below md when set; otherwise reuses the image above.', 'culvers'),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'slide_kicker' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Sub-head / kicker', 'culvers'),
                            'instructions' => __('Short uppercase line above the headline.', 'culvers'),
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
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'slide_cta_url' => [
                        'type' => 'url',
                        'options' => [
                            'label' => __('CTA URL', 'culvers'),
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
