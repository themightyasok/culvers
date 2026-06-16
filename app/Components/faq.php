<?php

/**
 * FAQ — centred Canela heading + accordion of question/answer disclosure rows.
 * disclosure rows. Optional decorative line-art SVGs/PNGs flank the column on
 * desktop. Figma ref: 51:7998.
 */

declare(strict_types=1);

use App\Helpers\Component;

return [
    'label' => __('FAQ', 'culvers'),
    'display' => 'block',
    'main' => [
        'msg_intro' => Component::sectionDivider(__('Heading', 'culvers')),
        'faq_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'instructions' => __('Centred Canela serif heading above the rows.', 'culvers'),
                'default_value' => __('Frequently Asked Questions', 'culvers'),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'faq_heading_level' => Component::headingLevelField(null, false, 2, '30'),
        'faq_open_mode' => [
            'type' => 'select',
            'options' => [
                'label' => __('Open mode', 'culvers'),
                'instructions' => __(
                    'Single — opening one row closes the others. Multi — rows toggle independently.',
                    'culvers'
                ),
                'choices' => [
                    'single' => __('Single (one row open at a time)', 'culvers'),
                    'multi' => __('Multi (multiple rows can be open)', 'culvers'),
                ],
                'default_value' => 'single',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'msg_decorations' => Component::sectionDivider(__('Side decorations (large screens)', 'culvers')),
        'faq_decorations_left' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Left-side decorations', 'culvers'),
                'instructions' => __(
                    'Optional line-art images that float to the left of the column on large screens.',
                    'culvers'
                ),
                'min' => 0,
                'max' => 4,
                'layout' => 'block',
                'button_label' => __('Add left decoration', 'culvers'),
                'sub_fields' => [
                    'item_image' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Image', 'culvers'),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                        ],
                    ],
                ],
            ],
        ],
        'faq_decorations_right' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Right-side decorations', 'culvers'),
                'instructions' => __(
                    'Optional line-art images that float to the right of the column on large screens.',
                    'culvers'
                ),
                'min' => 0,
                'max' => 4,
                'layout' => 'block',
                'button_label' => __('Add right decoration', 'culvers'),
                'sub_fields' => [
                    'item_image' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Image', 'culvers'),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'items' => [
        'faq_items' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('FAQ rows', 'culvers'),
                'instructions' => __('Each row renders as an expandable disclosure.', 'culvers'),
                'min' => 1,
                'max' => 30,
                'layout' => 'block',
                'button_label' => __('Add FAQ row', 'culvers'),
                'collapsed' => 'item_question',
                'sub_fields' => [
                    'item_question' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Question', 'culvers'),
                        ],
                    ],
                    'item_answer' => [
                        'type' => 'wysiwyg',
                        'options' => [
                            'label' => __('Answer', 'culvers'),
                            'tabs' => 'all',
                            'toolbar' => 'basic',
                            'media_upload' => 0,
                        ],
                    ],
                    'item_open_default' => [
                        'type' => 'true_false',
                        'options' => [
                            'label' => __('Open by default', 'culvers'),
                            'instructions' => __(
                                'Pre-expand this row on first render. In single-open mode only the first ' .
                                'pre-expanded row wins.',
                                'culvers'
                            ),
                            'default_value' => 0,
                            'ui' => 1,
                        ],
                    ],
                ],
            ],
        ],
    ],
];
