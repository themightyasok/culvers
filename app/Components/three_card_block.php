<?php

/**
 * Three card block — hero-style row with optional blog category tabs + View all.
 */

return [
    'label' => 'Three card block',
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'cards_source' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Card source', 'culvers'),
                'instructions' => __('Manual entries or latest posts per category tab.', 'culvers'),
                'choices' => [
                    'manual' => __('Manual (up to three cards)', 'culvers'),
                    'blog' => __('Blog posts (category tabs)', 'culvers'),
                ],
                'default_value' => 'manual',
                'layout' => 'horizontal',
                'return_format' => 'value',
            ],
        ],
        'block_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'default_value' => __('Fun for the whole family', 'culvers'),
            ],
        ],
        'block_subheading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Subheading', 'culvers'),
                'instructions' => __('Optional line above body text (sans, uppercase styling in the theme).', 'culvers'),
            ],
        ],
        'block_heading_level' => [
            'type' => 'select',
            'options' => [
                'label' => __('Heading level', 'culvers'),
                'instructions' => __('Use one H1 per page; this block defaults to H2.', 'culvers'),
                'choices' => [
                    '2' => __('H2 (default)', 'culvers'),
                    '1' => __('H1', 'culvers'),
                    '3' => __('H3', 'culvers'),
                ],
                'default_value' => '2',
                'return_format' => 'value',
            ],
        ],
        'block_body' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Body', 'culvers'),
                'instructions' => __('Supporting copy below the heading.', 'culvers'),
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'default_value' => sprintf(
                    '<p>%s</p>',
                    esc_html__(
                        'Discover shops, places to eat, and everything you need to plan your visit — all in one welcoming destination.',
                        'culvers'
                    )
                ),
            ],
        ],
        'three_cards' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Cards', 'culvers'),
                'instructions' => __('Exactly three cards recommended. Video plays while hovered (respects reduced motion).', 'culvers'),
                'min' => 0,
                'max' => 3,
                'layout' => 'block',
                'button_label' => __('Add card', 'culvers'),
                'conditional_logic' => [
                    [
                        [
                            'field' => 'cards_source',
                            'operator' => '==',
                            'value' => 'manual',
                        ],
                    ],
                ],
                'sub_fields' => [
                    'card_title' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Card title', 'culvers'),
                            'required' => 1,
                        ],
                    ],
                    'card_url' => [
                        'type' => 'url',
                        'options' => [
                            'label' => __('Link URL', 'culvers'),
                            'required' => 1,
                        ],
                    ],
                    'card_media_type' => [
                        'type' => 'radio',
                        'options' => [
                            'label' => __('Media type', 'culvers'),
                            'choices' => [
                                'image' => __('Image', 'culvers'),
                                'video' => __('Video', 'culvers'),
                            ],
                            'default_value' => 'image',
                            'layout' => 'horizontal',
                            'return_format' => 'value',
                        ],
                    ],
                    'card_image' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Image', 'culvers'),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'card_media_type',
                                        'operator' => '==',
                                        'value' => 'image',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'card_image_alt' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Image alt text', 'culvers'),
                            'instructions' => __('Important for screen readers when using an image.', 'culvers'),
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'card_media_type',
                                        'operator' => '==',
                                        'value' => 'image',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'card_video' => [
                        'type' => 'file',
                        'options' => [
                            'label' => __('Video file', 'culvers'),
                            'mime_types' => 'mp4,webm',
                            'return_format' => 'array',
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'card_media_type',
                                        'operator' => '==',
                                        'value' => 'video',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'card_video_poster' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Video poster (optional)', 'culvers'),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'card_media_type',
                                        'operator' => '==',
                                        'value' => 'video',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'blog_category_tabs' => [
            'type' => 'taxonomy',
            'options' => [
                'label' => __('Category tabs', 'culvers'),
                'instructions' => __('Order selected categories defines tab order. Each tab lists recent posts for that category.', 'culvers'),
                'taxonomy' => 'category',
                'field_type' => 'multi_select',
                'return_format' => 'id',
                'allow_null' => 1,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'cards_source',
                            'operator' => '==',
                            'value' => 'blog',
                        ],
                    ],
                ],
            ],
        ],
        'blog_posts_per_category' => [
            'type' => 'number',
            'options' => [
                'label' => __('Posts per tab', 'culvers'),
                'instructions' => __('How many posts to show when a tab is active (up to three columns).', 'culvers'),
                'default_value' => 3,
                'min' => 1,
                'max' => 12,
                'step' => 1,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'cards_source',
                            'operator' => '==',
                            'value' => 'blog',
                        ],
                    ],
                ],
            ],
        ],
        'blog_view_all_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('View all URL', 'culvers'),
                'instructions' => __('Typically your blog index or a landing page.', 'culvers'),
                'default_value' => '',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'cards_source',
                            'operator' => '==',
                            'value' => 'blog',
                        ],
                    ],
                ],
            ],
        ],
        'blog_view_all_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('View all label', 'culvers'),
                'default_value' => __('View all', 'culvers'),
                'conditional_logic' => [
                    [
                        [
                            'field' => 'cards_source',
                            'operator' => '==',
                            'value' => 'blog',
                        ],
                    ],
                ],
            ],
        ],
        'tab_padding' => [
            'type' => 'tab',
            'options' => ['label' => __('Padding', 'culvers')],
        ],
    ],
];
