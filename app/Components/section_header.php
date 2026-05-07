<?php

/**
 * Section header — small text-only intro band: optional eyebrow line, an
 * optional heading (with configurable level + alignment), and an optional
 * short body paragraph. Use as the opener for a content section (Plan My
 * Visit "Getting Here", About Colchester, single event intro, leasing
 * pitch, etc.). Distinct from `info_block` (heading + body + CTA) and
 * `content_section` (long-form WYSIWYG).
 */

use App\Helpers\Component;

return [
    'label' => __('Section header', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'header_eyebrow' => [
            'type' => 'text',
            'options' => [
                'label' => __('Eyebrow (optional)', 'culvers'),
                'instructions' => __(
                    'Small uppercase line above the heading (e.g. "Visit", "About"). Renders in faded olive.',
                    'culvers'
                ),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'header_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading (optional)', 'culvers'),
                'instructions' => __(
                    'Serif headline. Leave blank for body-only intros.',
                    'culvers'
                ),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'header_heading_level' => Component::headingLevelField(
            __('Default H2 — use H1 only when the page above does not already host one.', 'culvers'),
            allowH1: true,
        ),
        'header_body' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Body (optional)', 'culvers'),
                'instructions' => __(
                    'Short paragraph (1–3 lines) under the heading. Use Content section for long-form copy.',
                    'culvers'
                ),
                'rows' => 3,
                'new_lines' => 'br',
            ],
        ],
        'header_align' => [
            'type' => 'select',
            'options' => [
                'label' => __('Alignment', 'culvers'),
                'instructions' => __('Figma uses centred for most page intros.', 'culvers'),
                'choices' => [
                    'center' => __('Center (default)', 'culvers'),
                    'left' => __('Left', 'culvers'),
                ],
                'default_value' => 'center',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
        'header_max_width' => [
            'type' => 'select',
            'options' => [
                'label' => __('Body max width', 'culvers'),
                'instructions' => __(
                    'Constrains the body paragraph for legibility (Figma defaults to ~700px / "narrow").',
                    'culvers'
                ),
                'choices' => [
                    'narrow' => __('Narrow (~52rem / Figma default)', 'culvers'),
                    'medium' => __('Medium (~64rem)', 'culvers'),
                    'full' => __('Full width', 'culvers'),
                ],
                'default_value' => 'narrow',
                'allow_null' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
    ],
];
