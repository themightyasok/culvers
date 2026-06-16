<?php

/**
 * Text-image slider — vertical stack of large Canela headlines that expand
 * in place to reveal a body paragraph plus two polaroid-style images that
 * beside the body when a row is open (desktop: tilted polaroids; mobile: one flat image).
 */

declare(strict_types=1);

use App\Helpers\Component;

return [
    'label' => __('Text-image slider', 'culvers'),
    'display' => 'block',
    'main' => [
        'tis_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Optional section heading', 'culvers'),
                'instructions' => __('Leave blank to render the headline stack only.', 'culvers'),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'tis_heading_level' => Component::headingLevelField(null, false, 2, '30'),
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
    ],
    'items' => [
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
                'collapsed' => 'item_label',
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
                    'item_cta_label' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('CTA label', 'culvers'),
                            'instructions' => __(
                                'Optional button below the body (e.g. Contact us). Shown on mobile and desktop when the row is open.',
                                'culvers'
                            ),
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'item_cta_url' => [
                        'type' => 'url',
                        'options' => [
                            'label' => __('CTA URL', 'culvers'),
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'item_cta_new_tab' => [
                        'type' => 'true_false',
                        'options' => [
                            'label' => __('Open CTA in new tab', 'culvers'),
                            'default_value' => 0,
                            'ui' => 1,
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'item_image_left' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Left image', 'culvers'),
                            'instructions' => __(
                                'Polaroid-style crop shown to the left of the body when the row is open (desktop).',
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
                                'Polaroid-style crop shown to the right of the body when the row is open (desktop).',
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
                            // Figma 51:8145 — left polaroid tilts +7.24° clockwise.
                            'default_value' => 7,
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
                            // Figma 51:8144 — right polaroid tilts -5.99° (counter-clockwise).
                            'default_value' => -6,
                            'min' => -20,
                            'max' => 20,
                            'step' => 1,
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
