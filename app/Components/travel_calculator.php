<?php

/**
 * Travel Calculator — faded-olive band (Canela title + Halyard subtitle, 3-up
 * destination/mode/search controls), inline result strip, and a route-preview
 * map below. Backed by the Distance Matrix endpoint
 * `wp-json/culvers/v1/travel-calculator` and the Maps Embed API.
 */

declare(strict_types=1);

use App\Helpers\Component;

return [
    'label' => __('Travel Calculator', 'culvers'),
    'display' => 'block',
    'main' => [
        'msg_intro' => Component::sectionDivider(__('Heading', 'culvers')),
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
        'msg_api' => [
            'type' => 'message',
            'options' => [
                'message' => __(
                    'Live distances and the route map need a Google Maps API key plus destination '
                    . '(address and Place ID) at <strong>Appearance → Customize → Google Maps</strong>. '
                    . 'Visitors always travel <em>to</em> that fixed destination; the first field on the '
                    . 'form is where they are travelling <em>from</em>.',
                    'culvers'
                ),
                'esc_html' => 0,
                'wrapper' => ['class' => 'culvers-acf-help'],
            ],
        ],
        'msg_options' => Component::sectionDivider(__('Options', 'culvers')),
        'tc_show_map' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Show route map', 'culvers'),
                'instructions' => __(
                    'Render the Google Maps Embed below the form (uses the same Customizer → Google Maps settings). '
                    . 'Disable to render only the form + result strip.',
                    'culvers'
                ),
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'msg_map' => Component::sectionDivider(__('Map placeholder', 'culvers')),
        'tc_map_initial_image' => [
            'type' => 'image',
            'options' => [
                'label' => __('Map placeholder image', 'culvers'),
                'instructions' => __(
                    'Optional static image shown before the user runs a search '
                    . '(or as a fallback if the API key is missing).',
                    'culvers'
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
            ],
        ],
    ],
    'items' => [
        'tc_modes' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Available travel modes', 'culvers'),
                'instructions' => __(
                    'Order matches the order shown in the "travel by" select. '
                        . 'At least one mode is required.',
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
    ],
];
