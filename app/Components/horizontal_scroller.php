<?php

/**
 * Horizontal scroller — GSAP-driven seamless infinite strip with drag and per-item layout controls.
 */

use App\Helpers\Component;
use App\Helpers\Padding;
use App\Helpers\Typography;

$colourChoices = [
    'text-white' => __('White', 'culvers'),
    'text-black' => __('Black', 'culvers'),
    'text-brand-500' => __('Brand bright', 'culvers'),
    'text-deep-moss' => __('Deep moss', 'culvers'),
    'text-faded-olive' => __('Faded olive', 'culvers'),
];

return [
    'label' => __('Horizontal scroller', 'culvers'),
    'display' => 'block',
    'main' => [
        // ---------- Header / intro copy ----------
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
        // ---------- Header layout ----------
        'msg_main_layout' => Component::sectionDivider(__('Header layout', 'culvers')),
        'scroller_header_alignment' => [
            'type' => 'select',
            'options' => [
                'label' => __('Header vertical alignment', 'culvers'),
                'choices' => [
                    'top' => __('Top', 'culvers'),
                    'middle' => __('Middle', 'culvers'),
                    'bottom' => __('Bottom', 'culvers'),
                ],
                'default_value' => 'top',
                'allow_null' => 0,
                'wrapper' => ['width' => '33'],
            ],
        ],
        'scroller_header_text_alignment' => [
            'type' => 'select',
            'options' => [
                'label' => __('Header & subheader horizontal alignment', 'culvers'),
                'choices' => [
                    'left' => __('Left', 'culvers'),
                    'center' => __('Center', 'culvers'),
                    'right' => __('Right', 'culvers'),
                ],
                'default_value' => 'left',
                'allow_null' => 0,
                'wrapper' => ['width' => '33'],
            ],
        ],
        'scroller_intro_flush' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Tight header to cards', 'culvers'),
                'instructions' => __(
                    'Remove the default gap below the header block so the intro sits directly above the card row.',
                    'culvers'
                ),
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => ['width' => '34'],
            ],
        ],
        // ---------- Button ----------
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
        'scroller_button_variant' => [
            'type' => 'select',
            'options' => [
                'label' => __('Variant', 'culvers'),
                'choices' => [
                    'primary' => __('Primary', 'culvers'),
                    'secondary' => __('Secondary', 'culvers'),
                    'outline' => __('Outline', 'culvers'),
                ],
                'default_value' => 'primary',
                'allow_null' => 0,
                'wrapper' => ['width' => '33'],
            ],
        ],
        'scroller_button_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Size', 'culvers'),
                'choices' => [
                    'sm' => __('Small', 'culvers'),
                    'md' => __('Medium', 'culvers'),
                    'lg' => __('Large', 'culvers'),
                ],
                'default_value' => 'md',
                'allow_null' => 0,
                'wrapper' => ['width' => '33'],
            ],
        ],
        'scroller_button_show_arrow' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Show arrow icon', 'culvers'),
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '34'],
            ],
        ],
        // ---------- Strip motion ----------
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
                'wrapper' => ['width' => '33'],
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
                'wrapper' => ['width' => '33'],
            ],
        ],
        'scroller_item_spacing' => [
            'type' => 'range',
            'options' => [
                'label' => __('Space between strip items', 'culvers'),
                'instructions' => __('Horizontal gap between items (px).', 'culvers'),
                'default_value' => 240,
                'min' => 12,
                'max' => 6000,
                'step' => 32,
                'append' => 'px',
                'wrapper' => ['width' => '34'],
            ],
        ],
    ],
    'typography' => [
        // Header
        'msg_typo_header' => Component::sectionDivider(__('Header', 'culvers')),
        'scroller_header_text_color' => [
            'type' => 'select',
            'options' => [
                'label' => __('Colour', 'culvers'),
                'choices' => $colourChoices,
                'default_value' => 'text-white',
                'allow_null' => 0,
                'wrapper' => ['width' => '33'],
            ],
        ],
        'scroller_header_text_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Size', 'culvers'),
                'choices' => Typography::getHeaderSizeChoices(),
                'default_value' => 'text-8xl',
                'allow_null' => 0,
                'wrapper' => ['width' => '33'],
            ],
        ],
        'scroller_header_text_weight' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Weight (Canela)', 'culvers'),
                'choices' => Typography::getCanelaHeadingWeightChoices(),
                'layout' => 'horizontal',
                'default_value' => 'font-normal',
                'allow_null' => 0,
                'wrapper' => ['width' => '34'],
            ],
        ],
        // Subheading
        'msg_typo_subheading' => Component::sectionDivider(__('Subheading', 'culvers')),
        'scroller_subheading_text_color' => [
            'type' => 'select',
            'options' => [
                'label' => __('Colour', 'culvers'),
                'choices' => $colourChoices,
                'default_value' => 'text-white',
                'allow_null' => 0,
                'wrapper' => ['width' => '33'],
            ],
        ],
        'scroller_subheading_text_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Size', 'culvers'),
                'choices' => Typography::getBodySizeChoices(),
                'default_value' => 'text-xl',
                'allow_null' => 0,
                'wrapper' => ['width' => '33'],
            ],
        ],
        'scroller_subheading_text_weight' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Weight', 'culvers'),
                'choices' => Typography::getWeightChoices(),
                'layout' => 'horizontal',
                'default_value' => 'font-medium',
                'allow_null' => 0,
                'wrapper' => ['width' => '34'],
            ],
        ],
        // Body
        'msg_typo_body' => Component::sectionDivider(__('Body', 'culvers')),
        'scroller_body_text_color' => [
            'type' => 'select',
            'options' => [
                'label' => __('Colour', 'culvers'),
                'choices' => $colourChoices,
                'default_value' => 'text-white',
                'allow_null' => 0,
                'wrapper' => ['width' => '33'],
            ],
        ],
        'scroller_body_text_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Size', 'culvers'),
                'choices' => Typography::getBodySizeChoices(),
                'default_value' => 'text-xl',
                'allow_null' => 0,
                'wrapper' => ['width' => '33'],
            ],
        ],
        'scroller_body_text_weight' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Weight', 'culvers'),
                'choices' => Typography::getWeightChoices(),
                'layout' => 'horizontal',
                'default_value' => 'font-medium',
                'allow_null' => 0,
                'wrapper' => ['width' => '34'],
            ],
        ],
        // Item kicker
        'msg_typo_kicker' => Component::sectionDivider(__('Item kicker', 'culvers')),
        'scroller_item_kicker_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Size', 'culvers'),
                'choices' => Typography::getBodySizeChoices(),
                'default_value' => 'text-xs',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_kicker_weight' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Weight', 'culvers'),
                'choices' => Typography::getWeightChoices(),
                'layout' => 'horizontal',
                'default_value' => 'font-semibold',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        // Item heading
        'msg_typo_item_heading' => Component::sectionDivider(__('Item heading', 'culvers')),
        'scroller_item_heading_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Size', 'culvers'),
                'choices' => Typography::getHeaderSizeChoices(),
                'default_value' => 'text-2xl',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_heading_weight' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Weight (Canela)', 'culvers'),
                'choices' => Typography::getCanelaHeadingWeightChoices(),
                'layout' => 'horizontal',
                'default_value' => 'font-normal',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        // Item body
        'msg_typo_item_body' => Component::sectionDivider(__('Item body', 'culvers')),
        'scroller_item_body_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Size', 'culvers'),
                'choices' => Typography::getBodySizeChoices(),
                'default_value' => 'text-lg',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_body_weight' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Weight', 'culvers'),
                'choices' => Typography::getWeightChoices(),
                'layout' => 'horizontal',
                'default_value' => 'font-normal',
                'allow_null' => 0,
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
