<?php

/**
 * Starter flexible layout: heading + rich text.
 */

use App\Helpers\Component;

return [
    'label' => __('Content section', 'culvers'),
    'display' => 'block',
    'main' => [
        'content_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'required' => 0,
                'wrapper' => ['width' => '70'],
            ],
        ],
        'content_heading_level' => Component::headingLevelField(allowH1: true, width: '30'),
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
