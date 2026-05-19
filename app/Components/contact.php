<?php

/**
 * Contact — two-column band: left "Getting here / Contact Us" panel sourced
 * from FooterCustomizer (single source of truth for address, phone, email,
 * social URLs); right contact form (first/last/email/reason/message) posting
 * to `wp-json/culvers/v1/contact-form`. Optional Maps Embed below the band.
 *
 * Form copy defaults live in {@see \App\Contact\ContactFormCopy}; fields below
 * override those defaults for this block instance.
 */

use App\Helpers\Component;

return [
    'label' => __('Contact', 'culvers'),
    'display' => 'block',
    'main' => [
        'msg_intro' => Component::sectionDivider(__('Section heading', 'culvers')),
        'contact_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading (optional)', 'culvers'),
                'instructions' => __('Renders above the two-column band when set.', 'culvers'),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'contact_heading_level' => Component::headingLevelField(
            __('Optional band title only — the page hero usually owns the H1.', 'culvers'),
            false,
            2,
            '30'
        ),
        'msg_columns' => Component::sectionDivider(__('Column toggles', 'culvers')),
        'contact_show_panel' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Show "Getting here / Contact Us" panel', 'culvers'),
                'instructions' => __(
                    'When enabled, the left column shows the address, map link, phone, email and social ' .
                    'links from <strong>Appearance → Customize → Culver Square footer</strong>.',
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
                    'Renders the Google Maps Embed for the destination set at ' .
                    '<strong>Appearance → Customize → Google Maps</strong>.',
                    'culvers'
                ),
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'msg_form' => Component::sectionDivider(__('Form copy (optional overrides)', 'culvers')),
        'contact_form_first_name_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('First name — label', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_first_name_placeholder' => [
            'type' => 'text',
            'options' => [
                'label' => __('First name — placeholder', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_last_name_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Last name — label', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_last_name_placeholder' => [
            'type' => 'text',
            'options' => [
                'label' => __('Last name — placeholder', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_email_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Email — label', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_email_placeholder' => [
            'type' => 'text',
            'options' => [
                'label' => __('Email — placeholder', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_reason_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Reason — label', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_reason_placeholder' => [
            'type' => 'text',
            'options' => [
                'label' => __('Reason — placeholder', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_message_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Message — label', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_message_placeholder' => [
            'type' => 'text',
            'options' => [
                'label' => __('Message — placeholder', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_submit_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Submit button label', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_success_message' => [
            'type' => 'text',
            'options' => [
                'label' => __('Success message', 'culvers'),
                'instructions' => __('Shown next to the submit button after a successful send.', 'culvers'),
                'default_value' => __('Thanks — your message is on its way.', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'contact_form_reasons' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Enquiry reasons (dropdown)', 'culvers'),
                'instructions' => __(
                    'When this list has at least one row, the reason field renders as a dropdown. '
                    . 'When empty, visitors type their reason in a text field.',
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
                            'required' => 1,
                        ],
                    ],
                ],
            ],
        ],
    ],
];
