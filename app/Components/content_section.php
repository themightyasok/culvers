<?php

/**
 * Starter flexible layout: heading + rich text.
 */

return [
    'label' => 'Content section',
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => 'Content'],
        ],
        'heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'required' => 0,
            ],
        ],
        'heading_semantic_level' => [
            'type' => 'select',
            'options' => [
                'label' => __('Heading level', 'culvers'),
                'instructions' => __(
                    'Use one H1 per page (typically the hero). Other sections should stay H2–H6 for a logical outline.',
                    'culvers'
                ),
                'choices' => [
                    '2' => __('H2 — section title (default)', 'culvers'),
                    '1' => __('H1 — main page title (use once)', 'culvers'),
                    '3' => __('H3 — subsection', 'culvers'),
                    '4' => __('H4', 'culvers'),
                    '5' => __('H5', 'culvers'),
                    '6' => __('H6', 'culvers'),
                ],
                'default_value' => '2',
                'return_format' => 'value',
            ],
        ],
        'body' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Body', 'culvers'),
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            ],
        ],
        'tab_padding' => [
            'type' => 'tab',
            'options' => ['label' => 'Padding'],
        ],
    ],
];
