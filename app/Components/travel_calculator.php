<?php

/**
 * Travel Calculator — faded-olive band (Canela title + Halyard subtitle, 3-up
 * destination/mode/search controls), inline result strip, and a route-preview
 * map below. Backed by the Distance Matrix endpoint
 * `wp-json/culvers/v1/travel-calculator` and the Maps Embed API. Configure the
 * API key + destination at Appearance → Customize → Google Maps. Figma ref:
 * 51:7970, 51:5929 (band) + 51:5952 (map context).
 */

return [
    'label' => __('Travel Calculator', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'tc_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'default_value' => __('Travel Calculator', 'culvers'),
            ],
        ],
        'tc_intro' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Intro / strapline', 'culvers'),
                'rows' => 2,
                'default_value' => __(
                    'Find out how close Culver is to your work or any point of interest',
                    'culvers'
                ),
            ],
        ],
        'tc_destination_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('“Your destination” field label', 'culvers'),
                'default_value' => __('Your destination', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'tc_destination_placeholder' => [
            'type' => 'text',
            'options' => [
                'label' => __('“Your destination” placeholder', 'culvers'),
                'default_value' => __('Type your destination here', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'tc_mode_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('“Travel by” field label', 'culvers'),
                'default_value' => __('Travel by', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'tc_mode_placeholder' => [
            'type' => 'text',
            'options' => [
                'label' => __('“Travel by” placeholder', 'culvers'),
                'default_value' => __('Select', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'tc_modes' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Available travel modes', 'culvers'),
                'instructions' => __(
                    'Order matches the order shown in the “travel by” select. ' .
                    'At least one mode is required.',
                    'culvers'
                ),
                'min' => 1,
                'max' => 4,
                'layout' => 'table',
                'button_label' => __('Add mode', 'culvers'),
                'sub_fields' => [
                    'item_mode' => [
                        'type' => 'select',
                        'options' => [
                            'label' => __('Mode', 'culvers'),
                            'choices' => [
                                'driving' => __('Car (driving)', 'culvers'),
                                'transit' => __('Public transport (transit)', 'culvers'),
                                'walking' => __('Walking', 'culvers'),
                                'bicycling' => __('Cycling', 'culvers'),
                            ],
                            'default_value' => 'driving',
                            'allow_null' => 0,
                        ],
                    ],
                    'item_label' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Display label', 'culvers'),
                            'instructions' => __('Shown to users in the dropdown.', 'culvers'),
                        ],
                    ],
                ],
            ],
        ],
        'tc_button_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Search button label', 'culvers'),
                'default_value' => __('Search', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'tc_show_map' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Show route map', 'culvers'),
                'instructions' => __(
                    'Render the Google Maps Embed below the form. Disable to render only the form + result strip.',
                    'culvers'
                ),
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'tc_map_initial_image' => [
            'type' => 'image',
            'options' => [
                'label' => __('Map placeholder image', 'culvers'),
                'instructions' => __(
                    'Optional static image shown before the user runs a search ' .
                    '(or as a fallback if the API key is missing).',
                    'culvers'
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
            ],
        ],
    ],
];
