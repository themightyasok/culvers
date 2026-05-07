<?php

/**
 * Three card block — hero-style row with optional blog category tabs + View all.
 */

use App\Helpers\Component;

return [
    'label' => __('Three card block', 'culvers'),
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
                'instructions' => __(
                    'Manual entries, blog posts grouped by category tabs, '
                        . 'or the latest items from a single directory CPT '
                        . '(events, offers, news, shops, eat & drink, careers).',
                    'culvers'
                ),
                'choices' => [
                    'manual' => __('Manual (up to three cards)', 'culvers'),
                    'blog' => __('Blog posts (category tabs)', 'culvers'),
                    'cpt' => __('Directory CPT (latest items)', 'culvers'),
                ],
                'default_value' => 'manual',
                'layout' => 'horizontal',
                'return_format' => 'value',
            ],
        ],
        'cards_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
            ],
        ],
        'cards_subheading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Subheading', 'culvers'),
                'instructions' => __('Optional line above body text (sans, uppercase styling in the theme).', 'culvers'),
            ],
        ],
        'cards_heading_level' => Component::headingLevelField(allowH1: true),
        'cards_body' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Body', 'culvers'),
                'instructions' => __('Supporting copy below the heading.', 'culvers'),
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            ],
        ],
        'cards_items' => [
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
                            'instructions' => __('Used when media type is Image (ignored for video cards).', 'culvers'),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'library' => 'all',
                        ],
                    ],
                    'card_image_alt' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Image alt text', 'culvers'),
                            'instructions' => __('Important for screen readers when using an image.', 'culvers'),
                        ],
                    ],
                    'card_video' => [
                        'type' => 'file',
                        'options' => [
                            'label' => __('Video file', 'culvers'),
                            'instructions' => __('Used when media type is Video (ignored for image cards).', 'culvers'),
                            'mime_types' => 'mp4,webm',
                            'return_format' => 'array',
                            'library' => 'all',
                        ],
                    ],
                    'card_video_poster' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Video poster (optional)', 'culvers'),
                            'instructions' => __(
                                'Not used on the live card — the first frame of the video file is shown until hover.',
                                'culvers'
                            ),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'library' => 'all',
                        ],
                    ],
                ],
            ],
        ],
        'cards_blog_categories' => [
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
        'cards_blog_per_category' => [
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
        'cards_cpt_post_type' => [
            'type' => 'select',
            'options' => [
                'label' => __('Directory CPT', 'culvers'),
                'instructions' => __('Pick which directory the cards are pulled from.', 'culvers'),
                'choices' => [
                    'culvers_event' => __('Latest Events', 'culvers'),
                    'culvers_offer' => __('Latest Offers', 'culvers'),
                    'culvers_news' => __('Latest News', 'culvers'),
                    'culvers_shop' => __('Shops', 'culvers'),
                    'culvers_eat_drink' => __('Eat & Drink', 'culvers'),
                    'culvers_career' => __('Careers', 'culvers'),
                ],
                'default_value' => 'culvers_event',
                'allow_null' => 0,
                'return_format' => 'value',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'cards_source',
                            'operator' => '==',
                            'value' => 'cpt',
                        ],
                    ],
                ],
            ],
        ],
        'cards_cpt_count' => [
            'type' => 'number',
            'options' => [
                'label' => __('Cards to show', 'culvers'),
                'instructions' => __('Up to three columns; extra items wrap on smaller breakpoints.', 'culvers'),
                'default_value' => 3,
                'min' => 1,
                'max' => 12,
                'step' => 1,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'cards_source',
                            'operator' => '==',
                            'value' => 'cpt',
                        ],
                    ],
                ],
            ],
        ],
        'cards_view_all_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('View all URL', 'culvers'),
                'instructions' => __(
                    'Typically your blog index, the matching CPT archive, or any landing page. '
                        . 'Leave blank when source is "Directory CPT" to auto-link to that CPT\'s archive.',
                    'culvers'
                ),
                'default_value' => '',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'cards_source',
                            'operator' => '==',
                            'value' => 'blog',
                        ],
                    ],
                    [
                        [
                            'field' => 'cards_source',
                            'operator' => '==',
                            'value' => 'cpt',
                        ],
                    ],
                ],
            ],
        ],
        'cards_view_all_label' => [
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
                    [
                        [
                            'field' => 'cards_source',
                            'operator' => '==',
                            'value' => 'cpt',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
