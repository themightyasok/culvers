<?php

/**
 * Shop detail — split content + image band. Choose a 60/40 or 50/50 ratio.
 * Optionally swap the static copy for a tabbed deck whose panels cross-fade
 * inside the olive column.
 */

return [
    'label' => __('Shop — split highlight', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'split_ratio' => [
            'type' => 'select',
            'options' => [
                'label' => __('Column ratio', 'culvers'),
                'instructions' => __(
                    'Width split between the olive copy column and the image column on large screens.',
                    'culvers'
                ),
                'choices' => [
                    '60-40' => __('60 / 40 (copy / image)', 'culvers'),
                    '50-50' => __('50 / 50', 'culvers'),
                ],
                'default_value' => '60-40',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'split_use_tabs' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Use tabs in the copy column', 'culvers'),
                'instructions' => __(
                    'Show pill tabs above the copy and cross-fade between per-tab content.',
                    'culvers'
                ),
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'split_kicker' => [
            'type' => 'text',
            'options' => [
                'label' => __('Kicker line', 'culvers'),
                'instructions' => __('First line in Glowleaf serif (e.g. Piercing Parlour).', 'culvers'),
                'wrapper' => ['width' => '50'],
                'conditional_logic' => [
                    [
                        ['field' => 'split_use_tabs', 'operator' => '!=', 'value' => '1'],
                    ],
                ],
            ],
        ],
        'split_headline' => [
            'type' => 'text',
            'options' => [
                'label' => __('Headline line', 'culvers'),
                'instructions' => __('Second serif line (e.g. Now Open).', 'culvers'),
                'wrapper' => ['width' => '50'],
                'conditional_logic' => [
                    [
                        ['field' => 'split_use_tabs', 'operator' => '!=', 'value' => '1'],
                    ],
                ],
            ],
        ],
        'split_body' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Body', 'culvers'),
                'instructions' => __('White sans body copy and lists.', 'culvers'),
                'tabs' => 'all',
                'toolbar' => 'basic',
                'media_upload' => 0,
                'conditional_logic' => [
                    [
                        ['field' => 'split_use_tabs', 'operator' => '!=', 'value' => '1'],
                    ],
                ],
            ],
        ],
        'split_cta_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('CTA label', 'culvers'),
                'instructions' => __('Leave blank to hide.', 'culvers'),
                'wrapper' => ['width' => '50'],
                'conditional_logic' => [
                    [
                        ['field' => 'split_use_tabs', 'operator' => '!=', 'value' => '1'],
                    ],
                ],
            ],
        ],
        'split_cta_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('CTA URL', 'culvers'),
                'wrapper' => ['width' => '50'],
                'conditional_logic' => [
                    [
                        ['field' => 'split_use_tabs', 'operator' => '!=', 'value' => '1'],
                    ],
                ],
            ],
        ],
        'split_tabs' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Tabs', 'culvers'),
                'instructions' => __(
                    'Each row renders as a pill button above the copy and a cross-faded panel below.',
                    'culvers'
                ),
                'min' => 0,
                'max' => 8,
                'layout' => 'block',
                'button_label' => __('Add tab', 'culvers'),
                'conditional_logic' => [
                    [
                        ['field' => 'split_use_tabs', 'operator' => '==', 'value' => '1'],
                    ],
                ],
                'sub_fields' => [
                    'tab_label' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Pill label', 'culvers'),
                            'instructions' => __(
                                'Short uppercase label shown in the pill (e.g. History).',
                                'culvers'
                            ),
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'tab_headline' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Panel headline', 'culvers'),
                            'instructions' => __('Glowleaf serif headline shown in the panel.', 'culvers'),
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'tab_kicker' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Optional kicker line', 'culvers'),
                            'instructions' => __('Sits above the headline; leave blank to hide.', 'culvers'),
                        ],
                    ],
                    'tab_body' => [
                        'type' => 'wysiwyg',
                        'options' => [
                            'label' => __('Panel body', 'culvers'),
                            'instructions' => __('White sans body copy.', 'culvers'),
                            'tabs' => 'all',
                            'toolbar' => 'basic',
                            'media_upload' => 0,
                        ],
                    ],
                    'tab_cta_label' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('CTA label', 'culvers'),
                            'instructions' => __('Leave blank to hide.', 'culvers'),
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'tab_cta_url' => [
                        'type' => 'url',
                        'options' => [
                            'label' => __('CTA URL', 'culvers'),
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                ],
            ],
        ],
        'split_image' => [
            'type' => 'image',
            'options' => [
                'label' => __('Right column image', 'culvers'),
                'instructions' => __('Lifestyle crop; fills the image column.', 'culvers'),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
        ],
        'tab_padding' => [
            'type' => 'tab',
            'options' => ['label' => __('Padding', 'culvers')],
        ],
    ],
];
