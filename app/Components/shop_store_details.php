<?php

/**
 * Shop detail — Store Details band: contact | address | optional social (Instagram handle + link).
 */

use App\Helpers\Component;

return [
    'label' => __('Shop — store details', 'culvers'),
    'display' => 'block',
    'main' => [
        'msg_intro' => Component::sectionDivider(__('Section heading', 'culvers')),
        'details_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Section heading', 'culvers'),
                'default_value' => __('Store Details', 'culvers'),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'details_heading_level' => Component::headingLevelField(null, false, 2, '30'),
        'msg_contact' => Component::sectionDivider(__('Contact column', 'culvers')),
        'details_contact_phone' => [
            'type' => 'text',
            'options' => [
                'label' => __('Phone number', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'msg_address' => Component::sectionDivider(__('Address column', 'culvers')),
        'details_address' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Address', 'culvers'),
                'rows' => 3,
                'new_lines' => 'br',
                'wrapper' => ['width' => '50'],
            ],
        ],
        'msg_social' => Component::sectionDivider(__('Social column (optional)', 'culvers')),
        'details_show_social_column' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Show social column', 'culvers'),
                'instructions' => __('Turn off to use two columns even if Instagram URL or handle is filled.', 'culvers'),
                'ui' => 1,
                'default_value' => 1,
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
