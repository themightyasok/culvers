<?php

/**
 * Shop detail — split content + image band. Choose a 60/40 or 50/50 ratio.
 * Optionally swap the static copy for a tabbed deck whose panels cross-fade
 * inside the olive column.
 */

use App\Helpers\Component;

$staticOnly = [[['field' => 'split_use_tabs', 'operator' => '!=', 'value' => '1']]];
$tabsOnly = [[['field' => 'split_use_tabs', 'operator' => '==', 'value' => '1']]];

return [
    'label' => __('Shop — split highlight', 'culvers'),
    'display' => 'block',
    'main' => [
        'msg_layout' => Component::sectionDivider(__('Layout', 'culvers')),
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
        'msg_static' => Component::sectionDivider(__('Static copy (when tabs are off)', 'culvers')),
        'split_kicker' => [
            'type' => 'text',
            'options' => [
                'label' => __('Kicker line', 'culvers'),
                'instructions' => __('First line in Glowleaf serif (e.g. Piercing Parlour).', 'culvers'),
                'wrapper' => ['width' => '50'],
                'conditional_logic' => $staticOnly,
            ],
        ],
        'split_headline' => [
            'type' => 'text',
            'options' => [
                'label' => __('Headline line', 'culvers'),
                'instructions' => __('Second serif line (e.g. Now Open).', 'culvers'),
                'wrapper' => ['width' => '50'],
                'conditional_logic' => $staticOnly,
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
                'conditional_logic' => $staticOnly,
            ],
        ],
        'split_cta_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('CTA label', 'culvers'),
                'instructions' => __('Leave blank to hide.', 'culvers'),
                'wrapper' => ['width' => '50'],
                'conditional_logic' => $staticOnly,
            ],
        ],
        'split_cta_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('CTA URL', 'culvers'),
                'wrapper' => ['width' => '50'],
                'conditional_logic' => $staticOnly,
            ],
        ],
        'msg_image' => Component::sectionDivider(__('Image column', 'culvers')),
        'split_image' => [
            'type' => 'image',
            'options' => [
                'label' => __('Right column image', 'culvers'),
                'instructions' => __(
                    'Lifestyle crop; fills the image column. With tabs, used as the default '
                    . 'right-hand image; override per tab with "Panel image" on each tab row.',
                    'culvers'
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
        ],
    ],
    'items' => [
        'split_tabs_help' => [
            'type' => 'message',
            'options' => [
                'message' => __(
                    'These tab rows are only rendered when <strong>Use tabs in the copy column</strong> '
                    . 'on the <em>Main</em> tab is on. Otherwise the static copy block on the Main tab '
                    . 'is used and this list is ignored.',
                    'culvers'
                ),
                'esc_html' => 0,
                'wrapper' => ['class' => 'culvers-acf-help'],
            ],
        ],
        'split_tabs' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Tabs (when tabs are on)', 'culvers'),
                'instructions' => __(
                    'Each row renders as a pill button above the copy and a cross-faded panel below. '
                        . 'Only used when "Use tabs in the copy column" is enabled.',
                    'culvers'
                ),
                'min' => 0,
                'max' => 8,
                'layout' => 'block',
                'button_label' => __('Add tab', 'culvers'),
                'collapsed' => 'tab_label',
                'conditional_logic' => $tabsOnly,
                'sub_fields' => [
                    'tab_label' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Pill label', 'culvers'),
                            'instructions' => __('Short uppercase label shown in the pill (e.g. History).', 'culvers'),
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
                    'tab_image' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Panel image', 'culvers'),
                            'instructions' => __(
                                'Optional. Right-column image while this tab is active. ' .
                                'If empty, the component\'s main right column image is used.',
                                'culvers'
                            ),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'wrapper' => ['width' => '100'],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
