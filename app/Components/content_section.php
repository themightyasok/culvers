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
