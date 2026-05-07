<?php

/**
 * Shop detail — Store Details band: contact | address | optional social (Instagram handle + link).
 */

use App\Helpers\Component;

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
        'details_heading_level' => Component::headingLevelField(),
        'details_contact_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Contact column label', 'culvers'),
                'default_value' => __('Contact Number', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'details_contact_phone' => [
            'type' => 'text',
            'options' => [
                'label' => __('Phone number', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'details_address_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Address column label', 'culvers'),
                'default_value' => __('Address', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'details_address' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Address', 'culvers'),
                'rows' => 3,
                'new_lines' => 'br',
                'wrapper' => ['width' => '50'],
            ],
        ],
        'details_social_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Social column label', 'culvers'),
                'instructions' => __('Leave Instagram fields blank to switch to a two-column layout.', 'culvers'),
                'default_value' => __('Social Media', 'culvers'),
            ],
        ],
        'details_instagram_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('Instagram URL', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'details_instagram_handle' => [
            'type' => 'text',
            'options' => [
                'label' => __('Instagram handle', 'culvers'),
                'instructions' => __('Include @ if desired.', 'culvers'),
                'placeholder' => '@ACCESSORIZE_LDN',
                'wrapper' => ['width' => '50'],
            ],
        ],
    ],
];
