<?php

/**
 * Info grid — intro (heading, subheading, body, CTA) + square cells (icon, title, description).
 */

return [
    'label' => __('Info block', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'default_value' => __('A glimpse of what we have to offer', 'culvers'),
            ],
        ],
        'heading_semantic_level' => [
            'type' => 'select',
            'options' => [
                'label' => __('Heading level', 'culvers'),
                'instructions' => __('Use one H1 per page (typically the hero).', 'culvers'),
                'choices' => [
                    '2' => __('H2 — section title (default)', 'culvers'),
                    '3' => __('H3', 'culvers'),
                    '4' => __('H4', 'culvers'),
                ],
                'default_value' => '2',
                'return_format' => 'value',
            ],
        ],
        'subheading' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Subheading', 'culvers'),
                'rows' => 2,
                'new_lines' => 'br',
            ],
        ],
        'body' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Body', 'culvers'),
                'tabs' => 'all',
                'toolbar' => 'basic',
                'media_upload' => 0,
            ],
        ],
        'info_cta_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('CTA label', 'culvers'),
                'instructions' => __('Leave blank to hide the button.', 'culvers'),
                'default_value' => __('Plan my visit', 'culvers'),
            ],
        ],
        'info_cta_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('CTA URL', 'culvers'),
                'instructions' => __('Required if a label is set.', 'culvers'),
            ],
        ],
        'info_items' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Info cells', 'culvers'),
                'instructions' => __('Square tiles in a 4-column grid on large screens; one column on mobile.', 'culvers'),
                'min' => 0,
                'max' => 16,
                'layout' => 'block',
                'button_label' => __('Add cell', 'culvers'),
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
        'tab_padding' => [
            'type' => 'tab',
            'options' => ['label' => __('Padding', 'culvers')],
        ],
    ],
];
