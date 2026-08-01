<?php

/**
 * Contained media band: self-hosted video (poster + play CTA) or a static image
 * in the same branded frame. Editors choose image OR video — not both.
 */

declare(strict_types=1);

$videoOnly = [[['field' => 'video_media_type', 'operator' => '==', 'value' => 'video']]];
$imageOnly = [[['field' => 'video_media_type', 'operator' => '==', 'value' => 'image']]];

return [
    'label' => __('Video block', 'culvers'),
    'display' => 'block',
    'main' => [
        'video_instructions' => [
            'type' => 'message',
            'options' => [
                'message' => __(
                    'Choose Image or Video. Both use the same branded frame on the front end. '
                    . 'Use Image when you do not have a finished video yet.',
                    'culvers'
                ),
                'new_lines' => 'wpautop',
                'wrapper' => ['class' => 'culvers-acf-help'],
            ],
        ],
        'video_media_type' => [
            'type' => 'button_group',
            'options' => [
                'label' => __('Media type', 'culvers'),
                'instructions' => __('Image shows a still only (no play button). Video uses the file + optional poster.', 'culvers'),
                'choices' => [
                    'image' => __('Image', 'culvers'),
                    'video' => __('Video', 'culvers'),
                ],
                'default_value' => 'video',
                'return_format' => 'value',
                'layout' => 'horizontal',
            ],
        ],
        'video_image' => [
            'type' => 'image',
            'options' => [
                'label' => __('Image', 'culvers'),
                'instructions' => __('Shown inside the branded frame. Prefer a 16:9 landscape.', 'culvers'),
                'required' => 1,
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
                'conditional_logic' => $imageOnly,
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
                'conditional_logic' => $videoOnly,
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
                'conditional_logic' => $videoOnly,
            ],
        ],
        'video_play_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Play button label', 'culvers'),
                'placeholder' => __('Play video', 'culvers'),
                'conditional_logic' => $videoOnly,
            ],
        ],
    ],
];
