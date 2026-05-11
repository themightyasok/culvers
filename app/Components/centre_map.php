<?php

/**
 * Centre map — interactive "find your way around" panel: a centre-floor map
 * (image / SVG) with a category sidebar. Pins are an ACF repeater of (x%, y%,
 * label, category, link) so layout stays declarative.
 */

use App\Helpers\Component;

return [
    'label' => __('Centre map', 'culvers'),
    'display' => 'block',
    'main' => [
        'msg_intro' => Component::sectionDivider(__('Intro copy', 'culvers')),
        'centre_map_eyebrow' => [
            'type' => 'text',
            'options' => [
                'label' => __('Eyebrow (optional)', 'culvers'),
                'wrapper' => ['width' => '100'],
            ],
        ],
        'centre_map_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'default_value' => __('Find your way around', 'culvers'),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'centre_map_heading_level' => Component::headingLevelField(width: '30'),
        'centre_map_body' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Body (optional)', 'culvers'),
                'rows' => 3,
                'new_lines' => 'br',
            ],
        ],
        'msg_map' => Component::sectionDivider(__('Map asset', 'culvers')),
        'centre_map_image' => [
            'type' => 'image',
            'options' => [
                'label' => __('Map image / SVG', 'culvers'),
                'instructions' => __(
                    'Upload the centre-floor plan (SVG or PNG). Pins are positioned as a percentage of this image.',
                    'culvers'
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'mime_types' => 'svg,png,jpg,jpeg,webp',
            ],
        ],
        'msg_panel' => Component::sectionDivider(__('Filter panel', 'culvers')),
        'centre_map_panel_position' => [
            'type' => 'select',
            'options' => [
                'label' => __('Position', 'culvers'),
                'instructions' => __(
                    'Figma developer release puts the filter panel on the left with the map on the right. '
                    . 'Flip to "Right" only if the surrounding page rhythm calls for it.',
                    'culvers'
                ),
                'choices' => [
                    'left' => __('Left (Figma default)', 'culvers'),
                    'right' => __('Right', 'culvers'),
                ],
                'default_value' => 'left',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'centre_map_filter_button_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Filter button label', 'culvers'),
                'instructions' => __(
                    'Pill button at the top of the panel — Figma calls it "Hide filter".',
                    'culvers'
                ),
                'default_value' => __('Hide filter', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'centre_map_show_zoom_controls' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Show zoom controls', 'culvers'),
                'instructions' => __(
                    'Renders the +/− pill stack in the bottom-right corner of the map.',
                    'culvers'
                ),
                'ui' => 1,
                'ui_on_text' => __('Show', 'culvers'),
                'ui_off_text' => __('Hide', 'culvers'),
                'default_value' => 1,
            ],
        ],
    ],
    'items' => [
        'msg_categories' => Component::sectionDivider(__('Categories (filter panel)', 'culvers')),
        'centre_map_categories' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Categories', 'culvers'),
                'instructions' => __(
                    'Categories shown in the filter panel. Group sibling categories together by giving them '
                    . 'the same "Group" label (e.g. "Shop"); the panel renders one accordion per unique group. '
                    . 'Pins linked to a category light up on hover / focus.',
                    'culvers'
                ),
                'min' => 0,
                'max' => 48,
                'layout' => 'block',
                'button_label' => __('Add category', 'culvers'),
                'collapsed' => 'category_label',
                'sub_fields' => [
                    'category_group' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Group', 'culvers'),
                            'instructions' => __(
                                'Sibling categories with the same group label collapse into one accordion section.',
                                'culvers'
                            ),
                            'wrapper' => ['width' => '40'],
                        ],
                    ],
                    'category_label' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Label', 'culvers'),
                            'required' => 1,
                            'wrapper' => ['width' => '40'],
                        ],
                    ],
                    'category_slug' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Slug (used to link pins)', 'culvers'),
                            'instructions' => __(
                                'Lowercase, hyphenated. Pins reference this slug to highlight on hover.',
                                'culvers'
                            ),
                            'required' => 1,
                            'wrapper' => ['width' => '20'],
                        ],
                    ],
                    'category_url' => [
                        'type' => 'url',
                        'options' => [
                            'label' => __('Link URL (optional)', 'culvers'),
                            'instructions' => __(
                                'e.g. /shops/?category=fashion. Leave blank to make the row a hover-only target.',
                                'culvers'
                            ),
                        ],
                    ],
                ],
            ],
        ],
        'msg_pins' => Component::sectionDivider(__('Pins', 'culvers')),
        'centre_map_pins' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Pins', 'culvers'),
                'instructions' => __(
                    'Pin positions are a percentage of the map image (0–100). Use a category slug from above to group pins.',
                    'culvers'
                ),
                'min' => 0,
                'max' => 200,
                'layout' => 'table',
                'button_label' => __('Add pin', 'culvers'),
                'collapsed' => 'pin_label',
                'sub_fields' => [
                    'pin_x' => [
                        'type' => 'number',
                        'options' => [
                            'label' => __('X (%)', 'culvers'),
                            'min' => 0,
                            'max' => 100,
                            'step' => 0.1,
                            'default_value' => 50,
                        ],
                    ],
                    'pin_y' => [
                        'type' => 'number',
                        'options' => [
                            'label' => __('Y (%)', 'culvers'),
                            'min' => 0,
                            'max' => 100,
                            'step' => 0.1,
                            'default_value' => 50,
                        ],
                    ],
                    'pin_label' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Label', 'culvers'),
                            'required' => 0,
                        ],
                    ],
                    'pin_category_slug' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Category slug', 'culvers'),
                            'instructions' => __('Match a slug from the Categories repeater above.', 'culvers'),
                        ],
                    ],
                ],
            ],
        ],
    ],
];
