<?php

/**
 * Starter flexible layout: heading + rich text.
 */

use App\Helpers\Component;

return [
    'label' => __('Content section', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'content_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'required' => 0,
            ],
        ],
        'content_heading_level' => Component::headingLevelField(allowH1: true),
        'content_body' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Body', 'culvers'),
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            ],
        ],
    ],
];
