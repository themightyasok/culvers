<?php

/**
 * Contained video block: poster or first frame, branded frame, hover scale, custom play CTA.
 */

declare(strict_types=1);

return [
    'label' => __('Video block', 'culvers'),
    'display' => 'block',
    'main' => [
        'video_instructions' => [
            'type' => 'message',
            'options' => [
                'message' => __(
                    'Upload an MP4 or WebM. An optional poster image is shown until play; without one, '
                    . 'the theme primes the first decoded frame when supported.',
                    'culvers'
                ),
                'new_lines' => 'wpautop',
                'wrapper' => ['class' => 'culvers-acf-help'],
            ],
        ],
        'video_file' => [
            'type' => 'file',
            'options' => [
                'label' => __('Video file', 'culvers'),
                'required' => 1,
                'return_format' => 'array',
                'mime_types' => 'mp4,webm',
                'library' => 'all',
            ],
        ],
        'video_poster' => [
            'type' => 'image',
            'options' => [
                'label' => __('Poster image (optional)', 'culvers'),
                'instructions' => __('Shown until play. Leave empty to use the first frame of the video.', 'culvers'),
                'required' => 0,
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
        ],
        'video_play_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Play button label', 'culvers'),
                'placeholder' => __('Play video', 'culvers'),
            ],
        ],
    ],
];
