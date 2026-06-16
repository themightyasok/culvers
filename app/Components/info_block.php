<?php

/**
 * Info grid — intro (heading, subheading, body, CTA) + square cells (icon, title, description).
 */

declare(strict_types=1);

use App\Helpers\Component;

return [
    'label' => __('Info block', 'culvers'),
    'display' => 'block',
    'main' => [
        'msg_intro' => Component::sectionDivider(__('Intro copy', 'culvers')),
        'info_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'info_heading_level' => Component::headingLevelField(null, false, 2, '30'),
        'info_subheading' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Subheading', 'culvers'),
                'rows' => 2,
                'new_lines' => 'br',
            ],
        ],
        'info_body' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Body', 'culvers'),
                'tabs' => 'all',
                'toolbar' => 'basic',
                'media_upload' => 0,
            ],
        ],
        'msg_cta' => Component::sectionDivider(__('Call to action', 'culvers')),
        'info_cta_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('CTA label', 'culvers'),
                'instructions' => __('Leave blank to hide the button.', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'info_cta_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('CTA URL', 'culvers'),
                'instructions' => __('Required if a label is set.', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
    ],
    'items' => [
        'info_items' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Info cells', 'culvers'),
                'instructions' => __(
                    'Square tiles in a 4-column grid on large screens; one column on mobile.',
                    'culvers'
                ),
                'min' => 0,
                'max' => 16,
                'layout' => 'block',
                'button_label' => __('Add cell', 'culvers'),
                'collapsed' => 'item_heading',
                'sub_fields' => [
                    'item_image' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Image / icon', 'culvers'),
                            'instructions' => __('Line art or illustration; centred in the cell.', 'culvers'),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'library' => 'all',
                        ],
                    ],
                    'item_heading' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Title', 'culvers'),
                            'required' => 1,
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'item_description' => [
                        'type' => 'textarea',
                        'options' => [
                            'label' => __('Description', 'culvers'),
                            'instructions' => __('Shown in uppercase styling (small sans).', 'culvers'),
                            'rows' => 2,
                            'new_lines' => 'br',
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
