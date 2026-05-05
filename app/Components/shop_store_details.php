<?php

/**
 * Shop detail — Store Details band: contact | address | optional social (Instagram handle + link).
 */

return [
    'label' => __('Shop — store details', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'details_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Section heading', 'culvers'),
                'default_value' => __('Store Details', 'culvers'),
            ],
        ],
        'details_heading_level' => [
            'type' => 'select',
            'options' => [
                'label' => __('Heading level', 'culvers'),
                'instructions' => __('Keep one H1 on the page (typically the hero).', 'culvers'),
                'choices' => [
                    '2' => __('H2 — default', 'culvers'),
                    '3' => __('H3', 'culvers'),
                    '4' => __('H4', 'culvers'),
                ],
                'default_value' => '2',
                'return_format' => 'value',
                'wrapper' => ['width' => '33'],
            ],
        ],
        'contact_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Contact column label', 'culvers'),
                'default_value' => __('Contact Number', 'culvers'),
                'wrapper' => ['width' => '34'],
            ],
        ],
        'contact_phone' => [
            'type' => 'text',
            'options' => [
                'label' => __('Phone number', 'culvers'),
                'wrapper' => ['width' => '33'],
            ],
        ],
        'address_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Address column label', 'culvers'),
                'default_value' => __('Address', 'culvers'),
                'wrapper' => ['width' => '34'],
            ],
        ],
        'address_text' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Address', 'culvers'),
                'rows' => 3,
                'new_lines' => 'br',
                'wrapper' => ['width' => '33'],
            ],
        ],
        'social_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Social column label', 'culvers'),
                'instructions' => __('Leave Instagram fields blank to switch to a two-column layout.', 'culvers'),
                'default_value' => __('Social Media', 'culvers'),
                'wrapper' => ['width' => '34'],
            ],
        ],
        'social_instagram_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('Instagram URL', 'culvers'),
                'wrapper' => ['width' => '33'],
            ],
        ],
        'social_instagram_handle' => [
            'type' => 'text',
            'options' => [
                'label' => __('Instagram handle', 'culvers'),
                'instructions' => __('Include @ if desired.', 'culvers'),
                'placeholder' => '@ACCESSORIZE_LDN',
                'wrapper' => ['width' => '33'],
            ],
        ],
        'tab_padding' => [
            'type' => 'tab',
            'options' => ['label' => __('Padding', 'culvers')],
        ],
    ],
];
