<?php

/**
 * Contact — two-column band: left "Getting here / Contact Us" panel sourced
 * from FooterCustomizer (single source of truth for address, phone, email,
 * social URLs); right contact form (first/last/email/reason/message) posting
 * to `wp-json/culvers/v1/contact-form`. Optional Maps Embed below the band.
 * Figma ref: 51:9378.
 */

use App\Helpers\Component;

return [
    'label' => __('Contact', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'contact_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Optional section heading', 'culvers'),
                'instructions' => __('Renders above the two-column band when set.', 'culvers'),
            ],
        ],
        'contact_heading_level' => Component::headingLevelField(),

        'contact_show_panel' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Show "Getting here / Contact Us" panel', 'culvers'),
                'instructions' => __(
                    'When enabled, the left column shows the address, map link, phone, email and social ' .
                    'links from Customizer → Culver Square footer.',
                    'culvers'
                ),
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_show_map' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Show map below the form', 'culvers'),
                'instructions' => __(
                    'Renders the Google Maps Embed for the destination set at Customizer → Google Maps.',
                    'culvers'
                ),
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
        ],

        'contact_form_first_name_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Form — first name label', 'culvers'),
                'default_value' => __('First name*', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_first_name_placeholder' => [
            'type' => 'text',
            'options' => [
                'label' => __('First name placeholder', 'culvers'),
                'default_value' => __('Name', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_last_name_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Form — last name label', 'culvers'),
                'default_value' => __('Last name*', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_last_name_placeholder' => [
            'type' => 'text',
            'options' => [
                'label' => __('Last name placeholder', 'culvers'),
                'default_value' => __('Last name', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_email_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Form — email label', 'culvers'),
                'default_value' => __('Email*', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_email_placeholder' => [
            'type' => 'text',
            'options' => [
                'label' => __('Email placeholder', 'culvers'),
                'default_value' => __('Email address', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_reason_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Form — reason label', 'culvers'),
                'default_value' => __('Reason for enquiry*', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_reason_placeholder' => [
            'type' => 'text',
            'options' => [
                'label' => __('Reason placeholder', 'culvers'),
                'default_value' => __('Select', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_message_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Form — message label', 'culvers'),
                'default_value' => __('Message', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_message_placeholder' => [
            'type' => 'text',
            'options' => [
                'label' => __('Message placeholder', 'culvers'),
                'default_value' => __('Type message here', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_submit_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Form — submit button label', 'culvers'),
                'default_value' => __('Submit', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_success_message' => [
            'type' => 'text',
            'options' => [
                'label' => __('Success message', 'culvers'),
                'instructions' => __('Shown after a successful submission.', 'culvers'),
                'default_value' => __('Thanks — your message is on its way.', 'culvers'),
            ],
        ],
        'contact_form_reasons' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Reason for enquiry — choices', 'culvers'),
                'instructions' => __(
                    'Options shown in the "reason for enquiry" dropdown. Leave empty to render a free-text input instead.',
                    'culvers'
                ),
                'min' => 0,
                'max' => 12,
                'layout' => 'table',
                'button_label' => __('Add reason', 'culvers'),
                'sub_fields' => [
                    'item_reason' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Reason', 'culvers'),
                        ],
                    ],
                ],
            ],
        ],
        'tab_padding' => [
            'type' => 'tab',
            'options' => ['label' => __('Padding', 'culvers')],
        ],
    ],
];
