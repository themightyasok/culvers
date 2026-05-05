<?php

/**
 * Horizontal Scroller Component Configuration
 *
 * GSAP-based seamless infinite horizontal scrolling with drag interaction
 * Based on MWG 008 pattern with best practices from influencer-scroll
 */

use App\Helpers\Padding;
use App\Helpers\Typography;

$componentFields = [
    'tab_general' => [
        'type' => 'tab',
        'options' => ['label' => __('Content', 'culvers')],
    ],
    'header_text' => [
        'type' => 'wysiwyg',
        'options' => [
            'label' => 'Header Text',
            'instructions' => 'Enter the main heading text for the horizontal scroller section. HTML is supported.',
            'required' => 0,
            'media_upload' => 0,
            'toolbar' => 'basic',
        ],
    ],
    'header_alignment' => [
        'type' => 'select',
        'options' => [
            'label' => 'Header Vertical Alignment',
            'instructions' => 'Choose the vertical alignment of the header content.',
            'choices' => [
                'top' => 'Top',
                'middle' => 'Middle',
                'bottom' => 'Bottom',
            ],
            'default_value' => 'top',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'header_text_alignment' => [
        'type' => 'select',
        'options' => [
            'label' => 'Header & Subheader Text Alignment',
            'instructions' => 'Choose the horizontal alignment for both the header and subheader (they move together).',
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
    'subheading_text' => [
        'type' => 'wysiwyg',
        'options' => [
            'label' => 'Subheading Text',
            'instructions' => 'Enter optional subheading text below the header.',
            'required' => 0,
            'default_value' => '',
            'media_upload' => 0,
            'toolbar' => 'basic',
        ],
    ],
    'body_text' => [
        'type' => 'wysiwyg',
        'options' => [
            'label' => 'Body Text',
            'instructions' => 'Optional body content below the subheader. HTML is supported.',
            'required' => 0,
            'default_value' => '',
            'media_upload' => 1,
            'toolbar' => 'full',
        ],
    ],
    'intro_flush_to_content' => [
        'type' => 'true_false',
        'options' => [
            'label' => 'Tight header to cards',
            'instructions' => 'Remove the default gap below the header block so the intro sits directly above the card row (e.g. “Over X artists…” as the title for the scroller).',
            'default_value' => 0,
            'ui' => 1,
        ],
    ],
    'button_text' => [
        'type' => 'text',
        'options' => [
            'label' => 'Button Text',
            'instructions' => 'Enter the text for the optional button.',
            'required' => 0,
            'default_value' => '',
            'wrapper' => ['width' => '50'],
        ],
    ],
    'button_link' => [
        'type' => 'link',
        'options' => [
            'label' => 'Button Link',
            'instructions' => 'Select the link for the button.',
            'required' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'button_variant' => [
        'type' => 'select',
        'options' => [
            'label' => 'Button Variant',
            'instructions' => 'Choose the button style variant.',
            'choices' => [
                'primary' => 'Primary',
                'secondary' => 'Secondary',
                'outline' => 'Outline',
            ],
            'default_value' => 'primary',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'button_size' => [
        'type' => 'select',
        'options' => [
            'label' => 'Button Size',
            'instructions' => 'Choose the button size.',
            'choices' => [
                'sm' => 'Small',
                'md' => 'Medium',
                'lg' => 'Large',
            ],
            'default_value' => 'md',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'button_show_arrow' => [
        'type' => 'true_false',
        'options' => [
            'label' => 'Show Arrow Icon',
            'instructions' => 'Display an arrow icon on the button.',
            'default_value' => 1,
            'wrapper' => ['width' => '33'],
        ],
    ],
    'scroll_speed' => [
        'type' => 'select',
        'options' => [
            'label' => 'Scroll Speed',
            'instructions' => 'Choose the automatic scroll speed.',
            'choices' => [
                'slow' => 'Slow',
                'medium' => 'Medium',
                'fast' => 'Fast',
            ],
            'default_value' => 'medium',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'disable_scroll' => [
        'type' => 'true_false',
        'options' => [
            'label' => 'Disable scroll',
            'instructions' => 'When enabled, cards are shown in a single centered row with no auto-scroll, drag, or infinite loop.',
            'default_value' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'scroll_strip_item_spacing' => [
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
    'msg_scroll_cards_media' => [
        'type' => 'message',
        'options' => [
            'message' => '<strong>Floating row</strong><br>Strip media is always flat (no gradient overlay). '
                . 'The row spans the full viewport width. Settings below apply to each item.',
            'new_lines' => 'br',
        ],
    ],
    'scroll_cards' => [
        'type' => 'repeater',
        'options' => [
            'label' => 'Floating Content Items',
            'instructions' => 'Add mixed content items (image, video, text, image + text). '
                . 'Items scroll horizontally and can be staggered vertically.',
            'required' => 0,
            'min' => 1,
            'max' => 50,
            'layout' => 'block',
            'button_label' => 'Add Item',
            'sub_fields' => [
                'tab_item_desktop' => [
                    'type' => 'tab',
                    'options' => ['label' => 'Desktop'],
                ],
                'item_type' => [
                    'type' => 'select',
                    'options' => [
                        'label' => 'Item Type',
                        'choices' => [
                            'image' => 'Image',
                            'video' => 'Video',
                            'text' => 'Text',
                            'image_text' => 'Image + Text',
                        ],
                        'default_value' => 'image',
                        'allow_null' => 0,
                        'wrapper' => ['width' => '50'],
                    ],
                ],
                'item_size' => [
                    'type' => 'select',
                    'options' => [
                        'label' => 'Item Size',
                        'choices' => [
                            'small' => 'Small',
                            'medium' => 'Medium',
                            'large' => 'Large',
                            'xlarge' => 'Extra Large',
                        ],
                        'default_value' => 'medium',
                        'allow_null' => 0,
                        'wrapper' => ['width' => '50'],
                    ],
                ],
                'item_vertical_offset' => [
                    'type' => 'select',
                    'options' => [
                        'label' => 'Vertical Offset',
                        'choices' => [
                            'high-up' => 'High Up',
                            'up' => 'Up',
                            'center' => 'Center',
                            'down' => 'Down',
                            'low-down' => 'Low Down',
                        ],
                        'default_value' => 'center',
                        'allow_null' => 0,
                        'wrapper' => ['width' => '50'],
                    ],
                ],
                'item_aspect_ratio' => [
                    'type' => 'select',
                    'options' => [
                        'label' => 'Media Aspect Ratio',
                        'choices' => [
                            'portrait' => 'Portrait (4:5)',
                            'square' => 'Square (1:1)',
                            'landscape' => 'Landscape (16:10)',
                            'tall' => 'Tall (3:4)',
                        ],
                        'default_value' => 'landscape',
                        'allow_null' => 0,
                        'wrapper' => ['width' => '50'],
                    ],
                ],
                'item_kicker' => [
                    'type' => 'text',
                    'options' => [
                        'label' => 'Kicker (Optional)',
                        'instructions' => 'Small label above the item, e.g. "BARCELONA, 2024".',
                        'required' => 0,
                        'placeholder' => 'BARCELONA, 2024',
                    ],
                ],
                'item_heading' => [
                    'type' => 'text',
                    'options' => [
                        'label' => 'Heading (Optional)',
                        'required' => 0,
                        'placeholder' => 'Headline or quote',
                    ],
                ],
                'item_body' => [
                    'type' => 'textarea',
                    'options' => [
                        'label' => 'Body Text (Optional)',
                        'required' => 0,
                        'rows' => 4,
                        'placeholder' => 'Additional supporting text',
                    ],
                ],
                'image' => [
                    'type' => 'image',
                    'options' => [
                        'label' => 'Item Image',
                        'instructions' => 'Upload an image for image-based items.',
                        'required' => 0,
                        'return_format' => 'array',
                        'preview_size' => 'medium',
                        'library' => 'all',
                    ],
                ],
                'image_alt_text' => [
                    'type' => 'text',
                    'options' => [
                        'label' => 'Image Alt Text',
                        'instructions' => 'Enter descriptive alt text for accessibility. ' .
                            'Leave empty for decorative images.',
                        'required' => 0,
                        'placeholder' => 'Description of the image',
                    ],
                ],
                'item_video' => [
                    'type' => 'file',
                    'options' => [
                        'label' => 'Item Video',
                        'instructions' => 'Upload an mp4 or webm video file for video items.',
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
                        'label' => 'Item Video YouTube URL / Embed',
                        'instructions' => 'Paste a YouTube URL or iframe embed code. Used when no file is selected.',
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
                        'label' => 'Video Poster',
                        'instructions' => 'Optional poster image shown before video loads.',
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
                        'label' => 'Item Video Show Controls',
                        'instructions' => 'Show native playback controls for this video item.',
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
        'options' => ['label' => 'Fonts'],
    ],
    'msg_fonts_section' => [
        'type' => 'message',
        'options' => [
            'message' => '<strong>Section header</strong>',
            'new_lines' => 'br',
        ],
    ],
    'header_text_color' => [
        'type' => 'select',
        'options' => [
            'label' => 'Header Text Color',
            'choices' => [
                'text-white' => 'White',
                'text-black' => 'Black',
                'text-brand-500' => __('Brand bright', 'culvers'),
                'text-deep-moss' => __('Deep moss', 'culvers'),
            ],
            'default_value' => 'text-white',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'header_text_size' => [
        'type' => 'select',
        'options' => [
            'label' => 'Header Size',
            'choices' => Typography::getHeaderSizeChoices(),
            'default_value' => 'text-6xl',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'header_text_weight' => [
        'type' => 'radio',
        'options' => [
            'label' => 'Header Weight',
            'choices' => Typography::getWeightChoices(),
            'layout' => 'horizontal',
            'default_value' => 'font-medium',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'subheading_text_color' => [
        'type' => 'select',
        'options' => [
            'label' => 'Subheading Text Color',
            'choices' => [
                'text-white' => 'White',
                'text-black' => 'Black',
                'text-brand-500' => __('Brand bright', 'culvers'),
                'text-deep-moss' => __('Deep moss', 'culvers'),
            ],
            'default_value' => 'text-white',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'subheading_text_size' => [
        'type' => 'select',
        'options' => [
            'label' => 'Subheader Size',
            'choices' => Typography::getBodySizeChoices(),
            'default_value' => 'text-lg',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'subheading_text_weight' => [
        'type' => 'radio',
        'options' => [
            'label' => 'Subheader Weight',
            'choices' => Typography::getWeightChoices(),
            'layout' => 'horizontal',
            'default_value' => 'font-medium',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'msg_fonts_body' => [
        'type' => 'message',
        'options' => [
            'message' => '<strong>Body</strong>',
            'new_lines' => 'br',
        ],
    ],
    'body_text_color' => [
        'type' => 'select',
        'options' => [
            'label' => 'Body Text Color',
            'choices' => [
                'text-white' => 'White',
                'text-black' => 'Black',
                'text-brand-500' => __('Brand bright', 'culvers'),
                'text-deep-moss' => __('Deep moss', 'culvers'),
            ],
            'default_value' => 'text-white',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'body_text_size' => [
        'type' => 'select',
        'options' => [
            'label' => 'Body Size',
            'choices' => Typography::getBodySizeChoices(),
            'default_value' => 'text-lg',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'body_text_weight' => [
        'type' => 'radio',
        'options' => [
            'label' => 'Body Weight',
            'choices' => Typography::getWeightChoices(),
            'layout' => 'horizontal',
            'default_value' => 'font-medium',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'msg_fonts_items' => [
        'type' => 'message',
        'options' => [
            'message' => '<strong>Scroll items</strong>',
            'new_lines' => 'br',
        ],
    ],
    'item_kicker_size' => [
        'type' => 'select',
        'options' => [
            'label' => 'Kicker Size',
            'choices' => Typography::getBodySizeChoices(),
            'default_value' => 'text-xs',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'item_kicker_weight' => [
        'type' => 'radio',
        'options' => [
            'label' => 'Kicker Weight',
            'choices' => Typography::getWeightChoices(),
            'layout' => 'horizontal',
            'default_value' => 'font-semibold',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'item_heading_size' => [
        'type' => 'select',
        'options' => [
            'label' => 'Item Heading Size',
            'choices' => Typography::getHeaderSizeChoices(),
            'default_value' => 'text-xl',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'item_heading_weight' => [
        'type' => 'radio',
        'options' => [
            'label' => 'Item Heading Weight',
            'choices' => Typography::getWeightChoices(),
            'layout' => 'horizontal',
            'default_value' => 'font-medium',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'item_body_size' => [
        'type' => 'select',
        'options' => [
            'label' => 'Item Body Size',
            'choices' => Typography::getBodySizeChoices(),
            'default_value' => 'text-base',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'item_body_weight' => [
        'type' => 'radio',
        'options' => [
            'label' => 'Item Body Weight',
            'choices' => Typography::getWeightChoices(),
            'layout' => 'horizontal',
            'default_value' => 'font-normal',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'tab_padding' => [
        'type' => 'tab',
        'options' => ['label' => 'Padding'],
    ],
    'remove_vertical_padding' => [
        'type' => 'true_false',
        'options' => [
            'label' => 'Remove vertical padding',
            'instructions' => __(
                'Removes outer section vertical padding (Top/Bottom Padding above) and the horizontal scroller strip’s '
                . 'vertical safe-area padding. Off by default — turn on for a flush block.',
                'culvers'
            ),
            'default_value' => 0,
            'ui' => 1,
        ],
    ],
    'msg_padding_section' => [
        'type' => 'message',
        'options' => [
            'message' => '<strong>Section header</strong>',
            'new_lines' => 'br',
        ],
    ],
    'header_padding_top' => [
        'type' => 'select',
        'options' => [
            'label' => 'Header Padding Above',
            'choices' => Padding::getHeaderSubheaderPaddingChoices(),
            'default_value' => 'none',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'header_padding_bottom' => [
        'type' => 'select',
        'options' => [
            'label' => 'Header Padding Below',
            'choices' => Padding::getHeaderSubheaderPaddingChoices(),
            'default_value' => 'none',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'msg_padding_subheader' => [
        'type' => 'message',
        'options' => [
            'message' => '<strong>Subheader</strong>',
            'new_lines' => 'br',
        ],
    ],
    'subheader_padding_top' => [
        'type' => 'select',
        'options' => [
            'label' => 'Subheader Padding Above',
            'choices' => Padding::getHeaderSubheaderPaddingChoices(),
            'default_value' => 'none',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'subheader_padding_bottom' => [
        'type' => 'select',
        'options' => [
            'label' => 'Subheader Padding Below',
            'choices' => Padding::getHeaderSubheaderPaddingChoices(),
            'default_value' => 'none',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'msg_padding_body' => [
        'type' => 'message',
        'options' => [
            'message' => '<strong>Body</strong>',
            'new_lines' => 'br',
        ],
    ],
    'body_padding_top' => [
        'type' => 'select',
        'options' => [
            'label' => 'Body Padding Above',
            'choices' => Padding::getHeaderSubheaderPaddingChoices(),
            'default_value' => 'none',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'body_padding_bottom' => [
        'type' => 'select',
        'options' => [
            'label' => 'Body Padding Below',
            'choices' => Padding::getHeaderSubheaderPaddingChoices(),
            'default_value' => 'none',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'msg_padding_items' => [
        'type' => 'message',
        'options' => [
            'message' => '<strong>Scroll items</strong>',
            'new_lines' => 'br',
        ],
    ],
    'item_kicker_padding_top' => [
        'type' => 'select',
        'options' => [
            'label' => 'Kicker Padding Above',
            'choices' => Padding::getHeaderSubheaderPaddingChoices(),
            'default_value' => 'none',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'item_kicker_padding_bottom' => [
        'type' => 'select',
        'options' => [
            'label' => 'Kicker Padding Below',
            'choices' => Padding::getHeaderSubheaderPaddingChoices(),
            'default_value' => 'none',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'item_heading_padding_top' => [
        'type' => 'select',
        'options' => [
            'label' => 'Item Heading Padding Above',
            'choices' => Padding::getHeaderSubheaderPaddingChoices(),
            'default_value' => 'none',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'item_heading_padding_bottom' => [
        'type' => 'select',
        'options' => [
            'label' => 'Item Heading Padding Below',
            'choices' => Padding::getHeaderSubheaderPaddingChoices(),
            'default_value' => 'none',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'item_body_padding_top' => [
        'type' => 'select',
        'options' => [
            'label' => 'Item Body Padding Above',
            'choices' => Padding::getHeaderSubheaderPaddingChoices(),
            'default_value' => 'none',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
    'item_body_padding_bottom' => [
        'type' => 'select',
        'options' => [
            'label' => 'Item Body Padding Below',
            'choices' => Padding::getHeaderSubheaderPaddingChoices(),
            'default_value' => 'none',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ],
    ],
];

return [
    'label' => __('Horizontal scroller', 'culvers'),
    'display' => 'block',
    'fields' => $componentFields,
];
