<?php

/**
 * Shop detail — centred intro copy with optional CTA (cream band + geometric texture).
 */

return [
    'label' => __('Shop — intro block', 'culvers'),
    'display' => 'block',
    'main' => [
        'intro_body' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Intro copy', 'culvers'),
                'instructions' => __('Single centred column (sans body in designs).', 'culvers'),
                'tabs' => 'all',
                'toolbar' => 'basic',
                'media_upload' => 0,
            ],
        ],
        'intro_cta_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('CTA label', 'culvers'),
                'instructions' => __('Leave blank to hide.', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'intro_cta_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('CTA URL', 'culvers'),
                'instructions' => __('Required when a label is set.', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
    ],
];
