<?php

/**
 * Horizontal scroller — GSAP-driven seamless infinite strip with drag and per-item layout controls.
 *
 * Colours, type sizes, and common layout defaults come from {@see \App\Helpers\HorizontalScrollerPreset}
 * (Main → Style preset). Editors control copy, items, motion, and the optional CTA.
 */

use App\Helpers\Component;

return [
    'label' => __('Horizontal scroller', 'culvers'),
    'display' => 'block',
    'main' => [
        'msg_preset' => Component::sectionDivider(__('Style', 'culvers')),
        'scroller_preset' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Style preset', 'culvers'),
                'instructions' => __(
                    'Controls colours, typography scale, header alignment, strip spacing, and button styling. '
                    . 'Use “Homepage brand strip” for the logo row on the home page.',
                    'culvers'
                ),
                'choices' => [
                    'default' => __('Default (light text on dark band)', 'culvers'),
                    'homepage_brands' => __('Homepage brand strip (moss / olive on white)', 'culvers'),
                ],
                'default_value' => 'default',
                'layout' => 'vertical',
                'return_format' => 'value',
            ],
        ],
        'msg_main_copy' => Component::sectionDivider(__('Header & intro copy', 'culvers')),
        'scroller_header_text' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Header text', 'culvers'),
                'instructions' => __('Main heading for the section. HTML is supported.', 'culvers'),
                'required' => 0,
                'tabs' => 'all',
                'toolbar' => 'basic',
                'media_upload' => 0,
            ],
        ],
        'scroller_subheading_text' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Subheading text', 'culvers'),
                'instructions' => __('Optional subheading below the header.', 'culvers'),
                'required' => 0,
                'default_value' => '',
                'tabs' => 'all',
                'toolbar' => 'basic',
                'media_upload' => 0,
            ],
        ],
        'scroller_body_text' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Body text', 'culvers'),
                'instructions' => __('Optional body content below the subheader. HTML is supported.', 'culvers'),
                'required' => 0,
                'default_value' => '',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            ],
        ],
        'msg_main_button' => Component::sectionDivider(__('Button', 'culvers')),
        'scroller_button_text' => [
            'type' => 'text',
            'options' => [
                'label' => __('Button text', 'culvers'),
                'instructions' => __('Leave blank to hide the button.', 'culvers'),
                'required' => 0,
                'default_value' => '',
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_button_link' => [
            'type' => 'link',
            'options' => [
                'label' => __('Button link', 'culvers'),
                'required' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'msg_main_motion' => Component::sectionDivider(__('Strip motion', 'culvers')),
        'scroller_speed' => [
            'type' => 'select',
            'options' => [
                'label' => __('Scroll speed', 'culvers'),
                'choices' => [
                    'slow' => __('Slow', 'culvers'),
                    'medium' => __('Medium', 'culvers'),
                    'fast' => __('Fast', 'culvers'),
                ],
                'default_value' => 'medium',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_disabled' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Disable auto-scroll', 'culvers'),
                'instructions' => __(
                    'Cards line up centred with no auto-scroll, drag, or infinite loop.',
                    'culvers'
                ),
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
        ],
    ],
    'items' => [
        'scroller_items' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Floating content items', 'culvers'),
                'instructions' => __(
                    'Add mixed content items (image, video, text, image + text). '
                        . 'Items scroll horizontally and can be staggered vertically.',
                    'culvers'
                ),
                'required' => 0,
                'min' => 1,
                'max' => 50,
                'layout' => 'block',
                'button_label' => __('Add item', 'culvers'),
                'collapsed' => 'item_heading',
                'sub_fields' => [
                    'item_type' => [
                        'type' => 'select',
                        'options' => [
                            'label' => __('Item type', 'culvers'),
                            'choices' => [
                                'image' => __('Image', 'culvers'),
                                'video' => __('Video', 'culvers'),
                                'text' => __('Text', 'culvers'),
                                'image_text' => __('Image + Text', 'culvers'),
                            ],
                            'default_value' => 'image',
                            'allow_null' => 0,
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'item_size' => [
                        'type' => 'select',
                        'options' => [
                            'label' => __('Item size', 'culvers'),
                            'choices' => [
                                'small' => __('Small', 'culvers'),
                                'medium' => __('Medium', 'culvers'),
                                'large' => __('Large', 'culvers'),
                                'xlarge' => __('Extra large', 'culvers'),
                            ],
                            'default_value' => 'medium',
                            'allow_null' => 0,
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'item_vertical_offset' => [
                        'type' => 'select',
                        'options' => [
                            'label' => __('Vertical offset', 'culvers'),
                            'choices' => [
                                'high-up' => __('High up', 'culvers'),
                                'up' => __('Up', 'culvers'),
                                'center' => __('Center', 'culvers'),
                                'down' => __('Down', 'culvers'),
                                'low-down' => __('Low down', 'culvers'),
                            ],
                            'default_value' => 'center',
                            'allow_null' => 0,
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'item_aspect_ratio' => [
                        'type' => 'select',
                        'options' => [
                            'label' => __('Media aspect ratio', 'culvers'),
                            'choices' => [
                                'portrait' => __('Portrait (4:5)', 'culvers'),
                                'square' => __('Square (1:1)', 'culvers'),
                                'landscape' => __('Landscape (16:10)', 'culvers'),
                                'tall' => __('Tall (3:4)', 'culvers'),
                            ],
                            'default_value' => 'landscape',
                            'allow_null' => 0,
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'item_kicker' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Kicker (optional)', 'culvers'),
                            'instructions' => __('Small label above the item, e.g. "BARCELONA, 2024".', 'culvers'),
                            'required' => 0,
                            'placeholder' => __('BARCELONA, 2024', 'culvers'),
                        ],
                    ],
                    'item_heading' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Heading (optional)', 'culvers'),
                            'required' => 0,
                            'placeholder' => __('Headline or quote', 'culvers'),
                        ],
                    ],
                    'item_body' => [
                        'type' => 'textarea',
                        'options' => [
                            'label' => __('Body text (optional)', 'culvers'),
                            'required' => 0,
                            'rows' => 4,
                            'placeholder' => __('Additional supporting text', 'culvers'),
                        ],
                    ],
                    'item_image' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Item image', 'culvers'),
                            'instructions' => __('Used for image-based items.', 'culvers'),
                            'required' => 0,
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'library' => 'all',
                        ],
                    ],
                    'item_image_alt' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Image alt text', 'culvers'),
                            'instructions' => __(
                                'Descriptive alt text for accessibility. Leave empty for decorative images.',
                                'culvers'
                            ),
                            'required' => 0,
                        ],
                    ],
                    'item_video' => [
                        'type' => 'file',
                        'options' => [
                            'label' => __('Item video', 'culvers'),
                            'instructions' => __('MP4 or WebM file for video items.', 'culvers'),
                            'required' => 0,
                            'return_format' => 'array',
                            'mime_types' => 'mp4,webm',
                            'library' => 'all',
                            'conditional_logic' => [[
                                ['field' => 'item_type', 'operator' => '==', 'value' => 'video'],
                            ]],
                        ],
                    ],
                    'item_video_youtube_url' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Item video — YouTube URL / embed', 'culvers'),
                            'instructions' => __(
                                'YouTube URL or iframe embed. Used when no file is selected.',
                                'culvers'
                            ),
                            'required' => 0,
                            'placeholder' => 'https://www.youtube.com/watch?v=...',
                            'conditional_logic' => [[
                                ['field' => 'item_type', 'operator' => '==', 'value' => 'video'],
                            ]],
                        ],
                    ],
                    'item_video_poster' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Video poster', 'culvers'),
                            'instructions' => __('Optional poster image shown before video loads.', 'culvers'),
                            'required' => 0,
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'library' => 'all',
                            'conditional_logic' => [[
                                ['field' => 'item_type', 'operator' => '==', 'value' => 'video'],
                            ]],
                        ],
                    ],
                    'item_video_show_controls' => [
                        'type' => 'true_false',
                        'options' => [
                            'label' => __('Show video controls', 'culvers'),
                            'instructions' => __('Show native playback controls for this video item.', 'culvers'),
                            'default_value' => 0,
                            'ui' => 1,
                            'conditional_logic' => [[
                                ['field' => 'item_type', 'operator' => '==', 'value' => 'video'],
                            ]],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
