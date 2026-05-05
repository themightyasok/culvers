<?php

/**
 * Opening hours list with optional side illustrations, intro copy, and “today” highlight (site timezone).
 */

return [
    'label' => __('Opening hours', 'culvers'),
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
                'default_value' => __('Opening hours', 'culvers'),
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
        'graphic_left' => [
            'type' => 'image',
            'options' => [
                'label' => __('Graphic — left', 'culvers'),
                'instructions' => __('Optional line art or illustration (SVG/PNG). Shown beside the hours on large screens.', 'culvers'),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
        ],
        'graphic_right' => [
            'type' => 'image',
            'options' => [
                'label' => __('Graphic — right', 'culvers'),
                'instructions' => __('Optional line art or illustration (SVG/PNG).', 'culvers'),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
        ],
        'hours_rows' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Hours', 'culvers'),
                'instructions' => __(
                    'Add rows in any order. Set “Match weekday for highlight” so the correct row is highlighted on that day (e.g. label “Easter Sunday” can still match Sunday).',
                    'culvers'
                ),
                'min' => 0,
                'max' => 14,
                'layout' => 'table',
                'button_label' => __('Add row', 'culvers'),
                'sub_fields' => [
                    'day_label' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Day / title', 'culvers'),
                            'instructions' => __('Displayed label (e.g. Monday, Easter Sunday).', 'culvers'),
                            'required' => 1,
                            'wrapper' => ['width' => '34'],
                        ],
                    ],
                    'time_range' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Hours', 'culvers'),
                            'instructions' => __('e.g. 9am – 5:30pm or Closed', 'culvers'),
                            'required' => 1,
                            'wrapper' => ['width' => '33'],
                        ],
                    ],
                    'weekday_highlight' => [
                        'type' => 'select',
                        'options' => [
                            'label' => __('Match weekday for highlight', 'culvers'),
                            'instructions' => __(
                                'Uses the site timezone. Choose “None” for special rows that should never highlight.',
                                'culvers'
                            ),
                            'choices' => [
                                'none' => __('None', 'culvers'),
                                'sun' => __('Sunday', 'culvers'),
                                'mon' => __('Monday', 'culvers'),
                                'tue' => __('Tuesday', 'culvers'),
                                'wed' => __('Wednesday', 'culvers'),
                                'thu' => __('Thursday', 'culvers'),
                                'fri' => __('Friday', 'culvers'),
                                'sat' => __('Saturday', 'culvers'),
                            ],
                            'default_value' => 'none',
                            'allow_null' => 0,
                            'return_format' => 'value',
                            'wrapper' => ['width' => '33'],
                        ],
                    ],
                ],
            ],
        ],
        'hours_footnote' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Footnote', 'culvers'),
                'instructions' => __('Small note below the list (e.g. holiday hours).', 'culvers'),
                'rows' => 2,
                'new_lines' => 'br',
            ],
        ],
        'tab_padding' => [
            'type' => 'tab',
            'options' => ['label' => __('Padding', 'culvers')],
        ],
    ],
];
