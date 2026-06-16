<?php

/**
 * Social share — centred heading + Instagram / Facebook / WhatsApp row (Figma 51:6411).
 */

declare(strict_types=1);

use App\Helpers\Component;

return [
    'label' => __('Social share', 'culvers'),
    'display' => 'block',
    'main' => [
        'share_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'default_value' => __('Share with a friend', 'culvers'),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'share_heading_level' => Component::headingLevelField(
            __('Use H2 for the default offer / event pattern.', 'culvers'),
            false,
            2,
            '30'
        ),
    ],
];
