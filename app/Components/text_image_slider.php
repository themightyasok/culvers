<?php

/**
 * Text-image slider — vertical stack of large Canela headlines that expand
 * in place to reveal a body paragraph plus two polaroid-style images that
 * pop in (left + right) with a staggered scale/rotate animation. Inactive
 * headlines fade to a muted tone while one is open. Figma ref: 51:8074
 * (closed) / 51:8114 (open with images).
 */

use App\Helpers\Component;

return [
    'label' => __('Text-image slider', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'tis_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Optional section heading', 'culvers'),
                'instructions' => __('Leave blank to render the headline stack only.', 'culvers'),
            ],
        ],
        'tis_heading_level' => Component::headingLevelField(),
        'tis_open_mode' => [
            'type' => 'select',
            'options' => [
                'label' => __('Open mode', 'culvers'),
                'choices' => [
                    'single' => __('Single (one row open at a time)', 'culvers'),
                    'multi' => __('Multi (multiple rows can be open)', 'culvers'),
                ],
                'default_value' => 'single',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'tis_initial_open_index' => [
            'type' => 'number',
            'options' => [
                'label' => __('Pre-open row (0-based)', 'culvers'),
                'instructions' => __(
                    'Set to 0 to open the first row on load, 1 for the second, etc. ' .
                    'Leave blank or set to -1 for no row open.',
                    'culvers'
                ),
                'default_value' => -1,
                'min' => -1,
                'max' => 30,
                'step' => 1,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'tis_items' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Slider rows', 'culvers'),
                'instructions' => __(
                    'Each row renders one large clickable headline that expands to reveal the body + side images.',
                    'culvers'
                ),
                'min' => 1,
                'max' => 12,
                'layout' => 'block',
                'button_label' => __('Add row', 'culvers'),
                'sub_fields' => [
                    'item_label' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Headline', 'culvers'),
                            'instructions' => __('Large Canela serif label (e.g. Security).', 'culvers'),
                        ],
                    ],
                    'item_body' => [
                        'type' => 'wysiwyg',
                        'options' => [
                            'label' => __('Body copy', 'culvers'),
                            'tabs' => 'all',
                            'toolbar' => 'basic',
                            'media_upload' => 0,
                        ],
                    ],
                    'item_image_left' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Left image', 'culvers'),
                            'instructions' => __(
                                'Polaroid-style crop. Pops in from the left when the row opens.',
                                'culvers'
                            ),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'item_image_right' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Right image', 'culvers'),
                            'instructions' => __(
                                'Polaroid-style crop. Pops in from the right when the row opens.',
                                'culvers'
                            ),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'item_image_left_tilt' => [
                        'type' => 'range',
                        'options' => [
                            'label' => __('Left image tilt (°)', 'culvers'),
                            'instructions' => __('Negative tilts left, positive tilts right.', 'culvers'),
                            'default_value' => -8,
                            'min' => -20,
                            'max' => 20,
                            'step' => 1,
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'item_image_right_tilt' => [
                        'type' => 'range',
                        'options' => [
                            'label' => __('Right image tilt (°)', 'culvers'),
                            'default_value' => 6,
                            'min' => -20,
                            'max' => 20,
                            'step' => 1,
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
