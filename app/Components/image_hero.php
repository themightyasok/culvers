<?php

/**
 * Image hero — full-bleed page header used across the site (Contact, About,
 * brand-lockup pages, etc.). Pair the alternative `hero_slider` for looping
 * homepage heroes; this static component covers the "header hero" Figma
 * pattern (51:9360) with configurable image, title text or logo lockup,
 * subtitle, title colour and overlay strength.
 */

return [
    'label' => __('Hero — image', 'culvers'),
    'display' => 'block',
    'main' => [
        'hero_image' => [
            'type' => 'image',
            'options' => [
                'label' => __('Hero image', 'culvers'),
                'instructions' => __(
                    'Wide lifestyle / storefront shot from md upward (tablet + desktop). Figma band is 1440×646.',
                    'culvers'
                ),
                'return_format' => 'array',
                'preview_size' => 'large',
                'library' => 'all',
            ],
        ],
        'hero_logo' => [
            'type' => 'image',
            'options' => [
                'label' => __('Logo (optional)', 'culvers'),
                'instructions' => __(
                    'Centre a brand lockup over the photo (knock-out / white artwork reads best). ' .
                    'When set, the large title line is skipped; the subtitle line still renders. ' .
                    'Leave empty to use “Title line” instead.',
                    'culvers'
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
        ],
        'hero_title_line' => [
            'type' => 'text',
            'options' => [
                'label' => __('Title line', 'culvers'),
                'instructions' => __(
                    'Large serif headline when no logo is set (e.g. "Get In Touch.", "About Us"). ' .
                    'Renders at 96px on desktop in the brand glowleaf colour to match Figma.',
                    'culvers'
                ),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'hero_subtitle_line' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Subtitle line', 'culvers'),
                'instructions' => __(
                    'Spaced uppercase sans line under the title (e.g. "Fill in the form below to get in touch!"). ' .
                    'Renders at 20px / SemiBold / 4px tracking on desktop.',
                    'culvers'
                ),
                'rows' => 2,
                'new_lines' => 'br',
                'wrapper' => ['width' => '50'],
            ],
        ],
        'hero_overlay_opacity' => [
            'type' => 'number',
            'options' => [
                'label' => __('Image overlay darkness', 'culvers'),
                'instructions' => __(
                    'Solid black overlay opacity on the hero image (0–85). Figma default is 20 — push higher only ' .
                    'when text contrast on a busy photo demands it.',
                    'culvers'
                ),
                'default_value' => 20,
                'min' => 0,
                'max' => 85,
                'step' => 1,
                'append' => '%',
                'wrapper' => ['width' => '50'],
            ],
        ],
        'hero_title_in_image' => [
            'type' => 'true_false',
            'options' => [
                'label' => __('Title text is part of the image', 'culvers'),
                'instructions' => __(
                    'Tick when the supplied photo already has the page title (and any subtitle) burnt in — ' .
                    'common for Figma developer-handover assets. The component then renders only a screen-reader ' .
                    'h1 (taken from "Title line" or post title) and skips the visible text overlay so it never ' .
                    'appears twice.',
                    'culvers'
                ),
                'ui' => 1,
                'ui_on_text' => __('Yes, baked in', 'culvers'),
                'ui_off_text' => __('Render via component', 'culvers'),
                'default_value' => 0,
                'wrapper' => ['width' => '50'],
            ],
        ],
    ],
    'typography' => [
        'hero_title_tone' => [
            'type' => 'button_group',
            'options' => [
                'label' => __('Title colour', 'culvers'),
                'instructions' => __(
                    'Defaults to glowleaf (Figma standard). Use white only when the photo behind ' .
                    'pushes glowleaf out of contrast.',
                    'culvers'
                ),
                'choices' => [
                    'glowleaf' => __('Glowleaf', 'culvers'),
                    'white' => __('White', 'culvers'),
                    'lighter-cream' => __('Lighter cream', 'culvers'),
                ],
                'default_value' => 'glowleaf',
                'allow_null' => 0,
                'return_format' => 'value',
                'layout' => 'horizontal',
            ],
        ],
    ],
    'mobile' => [
        'hero_image_mobile' => [
            'type' => 'image',
            'options' => [
                'label' => __('Hero image (mobile)', 'culvers'),
                'instructions' => __(
                    'Optional tighter crop shown only below the md breakpoint (768px). ' .
                    'Leave blank to reuse the desktop image.',
                    'culvers'
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
        ],
    ],
];
