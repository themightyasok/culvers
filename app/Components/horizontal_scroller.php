<?php

/**
 * Horizontal scroller — GSAP-driven seamless infinite strip with drag and per-item layout controls.
 */

use App\Helpers\Padding;
use App\Helpers\Typography;

return [
    'label' => __('Horizontal scroller', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'scroller_header_text' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Header text', 'culvers'),
                'instructions' => __(
                    'Enter the main heading text for the horizontal scroller section. HTML is supported.',
                    'culvers'
                ),
                'required' => 0,
                'tabs' => 'all',
                'toolbar' => 'basic',
                'media_upload' => 0,
            ],
        ],
        'scroller_header_alignment' => [
            'type' => 'select',
            'options' => [
                'label' => __('Header vertical alignment', 'culvers'),
                'instructions' => __('Choose the vertical alignment of the header content.', 'culvers'),
                'choices' => [
                    'top' => __('Top', 'culvers'),
                    'middle' => __('Middle', 'culvers'),
                    'bottom' => __('Bottom', 'culvers'),
                ],
                'default_value' => 'top',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_header_text_alignment' => [
            'type' => 'select',
            'options' => [
                'label' => __('Header & subheader text alignment', 'culvers'),
                'instructions' => __(
                    'Choose the horizontal alignment for both the header and subheader (they move together).',
                    'culvers'
                ),
                'choices' => [
                    'left' => __('Left', 'culvers'),
                    'center' => __('Center', 'culvers'),
                    'right' => __('Right', 'culvers'),
                ],
                'default_value' => 'left',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_subheading_text' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Subheading text', 'culvers'),
                'instructions' => __('Enter optional subheading text below the header.', 'culvers'),
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
        'scroller_intro_flush' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Tight header to cards', 'culvers'),
                'instructions' => __(
                    'Remove the default gap below the header block so the intro sits directly above the card row '
                    . '(e.g. "Over X artists…" as the title for the scroller).',
                    'culvers'
                ),
                'default_value' => 0,
                'ui' => 1,
            ],
        ],
        'scroller_button_text' => [
            'type' => 'text',
            'options' => [
                'label' => __('Button text', 'culvers'),
                'instructions' => __('Enter the text for the optional button.', 'culvers'),
                'required' => 0,
                'default_value' => '',
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_button_link' => [
            'type' => 'link',
            'options' => [
                'label' => __('Button link', 'culvers'),
                'instructions' => __('Select the link for the button.', 'culvers'),
                'required' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_button_variant' => [
            'type' => 'select',
            'options' => [
                'label' => __('Button variant', 'culvers'),
                'instructions' => __('Choose the button style variant.', 'culvers'),
                'choices' => [
                    'primary' => __('Primary', 'culvers'),
                    'secondary' => __('Secondary', 'culvers'),
                    'outline' => __('Outline', 'culvers'),
                ],
                'default_value' => 'primary',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_button_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Button size', 'culvers'),
                'instructions' => __('Choose the button size.', 'culvers'),
                'choices' => [
                    'sm' => __('Small', 'culvers'),
                    'md' => __('Medium', 'culvers'),
                    'lg' => __('Large', 'culvers'),
                ],
                'default_value' => 'md',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_button_show_arrow' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Show arrow icon', 'culvers'),
                'instructions' => __('Display an arrow icon on the button.', 'culvers'),
                'default_value' => 1,
                'wrapper' => ['width' => '33'],
            ],
        ],
        'scroller_speed' => [
            'type' => 'select',
            'options' => [
                'label' => __('Scroll speed', 'culvers'),
                'instructions' => __('Choose the automatic scroll speed.', 'culvers'),
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
                'label' => __('Disable scroll', 'culvers'),
                'instructions' => __(
                    'When enabled, cards are shown in a single centered row with no auto-scroll, drag, or infinite loop.',
                    'culvers'
                ),
                'default_value' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_spacing' => [
            'type' => 'range',
            'options' => [
                'label' => __('Space between strip items', 'culvers'),
                'instructions' => __(
                    'Horizontal gap between logos/media in the scrolling row '
                        . '(also applies when scroll is disabled). Very high values are allowed '
                        . 'for extremely loose strips (up to 6000px).',
                    'culvers'
                ),
                'default_value' => 240,
                'min' => 12,
                'max' => 6000,
                'step' => 32,
                'append' => 'px',
            ],
        ],
        'scroller_msg_items_media' => [
            'type' => 'message',
            'options' => [
                'message' => __(
                    '<strong>Floating row</strong><br>Strip media is always flat (no gradient overlay). '
                    . 'The row spans the full viewport width. Settings below apply to each item.',
                    'culvers'
                ),
                'new_lines' => 'br',
            ],
        ],
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
                'sub_fields' => [
                    'tab_item_desktop' => [
                        'type' => 'tab',
                        'options' => ['label' => __('Desktop', 'culvers')],
                    ],
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
                            'instructions' => __('Upload an image for image-based items.', 'culvers'),
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
                                'Enter descriptive alt text for accessibility. Leave empty for decorative images.',
                                'culvers'
                            ),
                            'required' => 0,
                            'placeholder' => __('Description of the image', 'culvers'),
                        ],
                    ],
                    'item_video' => [
                        'type' => 'file',
                        'options' => [
                            'label' => __('Item video', 'culvers'),
                            'instructions' => __('Upload an mp4 or webm video file for video items.', 'culvers'),
                            'required' => 0,
                            'return_format' => 'array',
                            'mime_types' => 'mp4,webm',
                            'library' => 'all',
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'item_type',
                                        'operator' => '==',
                                        'value' => 'video',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'item_video_youtube_url' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Item video YouTube URL / embed', 'culvers'),
                            'instructions' => __(
                                'Paste a YouTube URL or iframe embed code. Used when no file is selected.',
                                'culvers'
                            ),
                            'required' => 0,
                            'placeholder' => 'https://www.youtube.com/watch?v=...',
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'item_type',
                                        'operator' => '==',
                                        'value' => 'video',
                                    ],
                                ],
                            ],
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
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'item_type',
                                        'operator' => '==',
                                        'value' => 'video',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'item_video_show_controls' => [
                        'type' => 'true_false',
                        'options' => [
                            'label' => __('Item video — show controls', 'culvers'),
                            'instructions' => __('Show native playback controls for this video item.', 'culvers'),
                            'default_value' => 0,
                            'ui' => 1,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'item_type',
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
        'tab_fonts' => [
            'type' => 'tab',
            'options' => ['label' => __('Fonts', 'culvers')],
        ],
        'scroller_msg_fonts_section' => [
            'type' => 'message',
            'options' => [
                'message' => __('<strong>Section header</strong>', 'culvers'),
                'new_lines' => 'br',
            ],
        ],
        'scroller_header_text_color' => [
            'type' => 'select',
            'options' => [
                'label' => __('Header text colour', 'culvers'),
                'choices' => [
                    'text-white' => __('White', 'culvers'),
                    'text-black' => __('Black', 'culvers'),
                    'text-brand-500' => __('Brand bright', 'culvers'),
                    'text-deep-moss' => __('Deep moss', 'culvers'),
                    'text-faded-olive' => __('Faded olive', 'culvers'),
                ],
                'default_value' => 'text-white',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_header_text_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Header size', 'culvers'),
                'choices' => Typography::getHeaderSizeChoices(),
                'default_value' => 'text-8xl',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_header_text_weight' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Header weight (Canela)', 'culvers'),
                'choices' => Typography::getCanelaHeadingWeightChoices(),
                'layout' => 'horizontal',
                'default_value' => 'font-normal',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_subheading_text_color' => [
            'type' => 'select',
            'options' => [
                'label' => __('Subheading text colour', 'culvers'),
                'choices' => [
                    'text-white' => __('White', 'culvers'),
                    'text-black' => __('Black', 'culvers'),
                    'text-brand-500' => __('Brand bright', 'culvers'),
                    'text-deep-moss' => __('Deep moss', 'culvers'),
                    'text-faded-olive' => __('Faded olive', 'culvers'),
                ],
                'default_value' => 'text-white',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_subheading_text_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Subheader size', 'culvers'),
                'choices' => Typography::getBodySizeChoices(),
                'default_value' => 'text-xl',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_subheading_text_weight' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Subheader weight', 'culvers'),
                'choices' => Typography::getWeightChoices(),
                'layout' => 'horizontal',
                'default_value' => 'font-medium',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_msg_fonts_body' => [
            'type' => 'message',
            'options' => [
                'message' => __('<strong>Body</strong>', 'culvers'),
                'new_lines' => 'br',
            ],
        ],
        'scroller_body_text_color' => [
            'type' => 'select',
            'options' => [
                'label' => __('Body text colour', 'culvers'),
                'choices' => [
                    'text-white' => __('White', 'culvers'),
                    'text-black' => __('Black', 'culvers'),
                    'text-brand-500' => __('Brand bright', 'culvers'),
                    'text-deep-moss' => __('Deep moss', 'culvers'),
                    'text-faded-olive' => __('Faded olive', 'culvers'),
                ],
                'default_value' => 'text-white',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_body_text_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Body size', 'culvers'),
                'choices' => Typography::getBodySizeChoices(),
                'default_value' => 'text-xl',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_body_text_weight' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Body weight', 'culvers'),
                'choices' => Typography::getWeightChoices(),
                'layout' => 'horizontal',
                'default_value' => 'font-medium',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_msg_fonts_items' => [
            'type' => 'message',
            'options' => [
                'message' => __('<strong>Scroll items</strong>', 'culvers'),
                'new_lines' => 'br',
            ],
        ],
        'scroller_item_kicker_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Kicker size', 'culvers'),
                'choices' => Typography::getBodySizeChoices(),
                'default_value' => 'text-xs',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_kicker_weight' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Kicker weight', 'culvers'),
                'choices' => Typography::getWeightChoices(),
                'layout' => 'horizontal',
                'default_value' => 'font-semibold',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_heading_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Item heading size', 'culvers'),
                'choices' => Typography::getHeaderSizeChoices(),
                'default_value' => 'text-2xl',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_heading_weight' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Item heading weight (Canela)', 'culvers'),
                'choices' => Typography::getCanelaHeadingWeightChoices(),
                'layout' => 'horizontal',
                'default_value' => 'font-normal',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_body_size' => [
            'type' => 'select',
            'options' => [
                'label' => __('Item body size', 'culvers'),
                'choices' => Typography::getBodySizeChoices(),
                'default_value' => 'text-lg',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_body_weight' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Item body weight', 'culvers'),
                'choices' => Typography::getWeightChoices(),
                'layout' => 'horizontal',
                'default_value' => 'font-normal',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_msg_padding_section' => [
            'type' => 'message',
            'options' => [
                'message' => __('<strong>Section header</strong>', 'culvers'),
                'new_lines' => 'br',
            ],
        ],
        'scroller_header_padding_top' => [
            'type' => 'select',
            'options' => [
                'label' => __('Header padding above', 'culvers'),
                'choices' => Padding::getHeaderSubheaderPaddingChoices(),
                'default_value' => 'none',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_header_padding_bottom' => [
            'type' => 'select',
            'options' => [
                'label' => __('Header padding below', 'culvers'),
                'choices' => Padding::getHeaderSubheaderPaddingChoices(),
                'default_value' => 'none',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_msg_padding_subheader' => [
            'type' => 'message',
            'options' => [
                'message' => __('<strong>Subheader</strong>', 'culvers'),
                'new_lines' => 'br',
            ],
        ],
        'scroller_subheader_padding_top' => [
            'type' => 'select',
            'options' => [
                'label' => __('Subheader padding above', 'culvers'),
                'choices' => Padding::getHeaderSubheaderPaddingChoices(),
                'default_value' => 'none',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_subheader_padding_bottom' => [
            'type' => 'select',
            'options' => [
                'label' => __('Subheader padding below', 'culvers'),
                'choices' => Padding::getHeaderSubheaderPaddingChoices(),
                'default_value' => 'none',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_msg_padding_body' => [
            'type' => 'message',
            'options' => [
                'message' => __('<strong>Body</strong>', 'culvers'),
                'new_lines' => 'br',
            ],
        ],
        'scroller_body_padding_top' => [
            'type' => 'select',
            'options' => [
                'label' => __('Body padding above', 'culvers'),
                'choices' => Padding::getHeaderSubheaderPaddingChoices(),
                'default_value' => 'none',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_body_padding_bottom' => [
            'type' => 'select',
            'options' => [
                'label' => __('Body padding below', 'culvers'),
                'choices' => Padding::getHeaderSubheaderPaddingChoices(),
                'default_value' => 'none',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_msg_padding_items' => [
            'type' => 'message',
            'options' => [
                'message' => __('<strong>Scroll items</strong>', 'culvers'),
                'new_lines' => 'br',
            ],
        ],
        'scroller_item_kicker_padding_top' => [
            'type' => 'select',
            'options' => [
                'label' => __('Kicker padding above', 'culvers'),
                'choices' => Padding::getHeaderSubheaderPaddingChoices(),
                'default_value' => 'none',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_kicker_padding_bottom' => [
            'type' => 'select',
            'options' => [
                'label' => __('Kicker padding below', 'culvers'),
                'choices' => Padding::getHeaderSubheaderPaddingChoices(),
                'default_value' => 'none',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_heading_padding_top' => [
            'type' => 'select',
            'options' => [
                'label' => __('Item heading padding above', 'culvers'),
                'choices' => Padding::getHeaderSubheaderPaddingChoices(),
                'default_value' => 'none',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_heading_padding_bottom' => [
            'type' => 'select',
            'options' => [
                'label' => __('Item heading padding below', 'culvers'),
                'choices' => Padding::getHeaderSubheaderPaddingChoices(),
                'default_value' => 'none',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_body_padding_top' => [
            'type' => 'select',
            'options' => [
                'label' => __('Item body padding above', 'culvers'),
                'choices' => Padding::getHeaderSubheaderPaddingChoices(),
                'default_value' => 'none',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'scroller_item_body_padding_bottom' => [
            'type' => 'select',
            'options' => [
                'label' => __('Item body padding below', 'culvers'),
                'choices' => Padding::getHeaderSubheaderPaddingChoices(),
                'default_value' => 'none',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
    ],
];
