<?php

/**
 * Leasing / lettings — three agent columns (logo, name, phone, website).
 *
 * Mirrors the shop Store Details rhythm (lighter-cream band, vertical rules);
 * typography aligned to Figma 51:6524–51:6527 (lettings trio).
 */

declare(strict_types=1);

use App\Helpers\Component;

return [
    'label' => __('Leasing — agent grid', 'culvers'),
    'display' => 'block',
    'main' => [
        'msg_intro' => Component::sectionDivider(__('Intro', 'culvers')),
        'agents_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'default_value' => __('Lettings', 'culvers'),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'agents_heading_level' => Component::headingLevelField(null, false, 2, '30'),
        'agents_intro' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Intro copy', 'culvers'),
                'instructions' => __('Centered below the heading (Halyard ~20px on the front end).', 'culvers'),
                'rows' => 3,
                'new_lines' => 'wpautop',
            ],
        ],
    ],
    'items' => [
        'leasing_agents' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Agents', 'culvers'),
                'instructions' => __('Up to three columns; order matches left-to-right on desktop.', 'culvers'),
                'min' => 0,
                'max' => 3,
                'layout' => 'block',
                'button_label' => __('Add agent', 'culvers'),
                'collapsed' => 'agent_name',
                'sub_fields' => [
                    'agent_logo' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Logo', 'culvers'),
                            'instructions' => __('~150×39 art from brand guidelines; scales down proportionally.', 'culvers'),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'library' => 'all',
                        ],
                    ],
                    'agent_name' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Agent name', 'culvers'),
                            'required' => 1,
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'agent_phone' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Phone', 'culvers'),
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'agent_website_url' => [
                        'type' => 'url',
                        'options' => [
                            'label' => __('Website URL', 'culvers'),
                            'instructions' => __('Full URL including https://', 'culvers'),
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                    'agent_website_label' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Website label', 'culvers'),
                            'instructions' => __('Optional display text (e.g. domain only). Uses the hostname when blank.', 'culvers'),
                            'wrapper' => ['width' => '50'],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
