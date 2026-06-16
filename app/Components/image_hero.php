<?php

/**
 * Image hero — full-bleed page header used across the site (Contact, About,
 * brand-lockup pages, etc.). Pair the alternative `hero_slider` for looping
 * homepage heroes; this static component covers the "header hero" Figma
 * pattern (51:9360) with configurable image, title text or logo lockup,
 * subtitle, title colour and overlay strength.
 */

declare(strict_types=1);

use App\Helpers\Component;

return [
    'label' => __('Hero — image', 'culvers'),
    'display' => 'block',
    'main' => array_merge(
        Component::responsiveImagePair('hero', [
            'desktop' => __('Hero image (tablet / desktop)', 'culvers'),
            'mobile' => __('Hero image (mobile, optional)', 'culvers'),
        ]),
        [
            'hero_logo' => [
                'type' => 'image',
                'options' => [
                    'label' => __('Logo (optional)', 'culvers'),
                    'instructions' => __(
                        'Centre a brand lockup over the photo (knock-out / white artwork reads best). ' .
                        'When set, the large title line is skipped; the subtitle line still renders. ' .
                        'Leave empty to use “Title line” or a logo source below.',
                        'culvers'
                    ),
                    'return_format' => 'array',
                    'preview_size' => 'medium',
                    'library' => 'all',
                ],
            ],
            'hero_logo_preserve_colors' => [
                'type' => 'true_false',
                'options' => array_merge(
                    \App\Directory\LogoPreserveColors::acfFieldDefinition(),
                    ['wrapper' => ['width' => '50']]
                ),
            ],
            'hero_logo_source' => [
                'type' => 'select',
                'options' => [
                    'label' => __('Logo source (when upload empty)', 'culvers'),
                    'instructions' => __(
                        'Directory singles typically use “Directory listing logo”. ' .
                        'Eat & drink venues without a listing logo may use “Featured image”.',
                        'culvers'
                    ),
                    'choices' => \App\Helpers\ImageHeroLogoSource::choices(),
                    'default_value' => \App\Helpers\ImageHeroLogoSource::UPLOADED,
                    'allow_null' => 0,
                    'return_format' => 'value',
                    'wrapper' => ['width' => '50'],
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
            'hero_overlay_opacity' => Component::overlayOpacityRangeField(
                __('Image overlay darkness', 'culvers'),
                __(
                    'Black overlay on the hero image. Figma default is 20% — raise only when text needs more contrast.',
                    'culvers'
                ),
                20,
                '50'
            ),
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
                    'wrapper' => ['width' => '50'],
                ],
            ],
        ]
    ),
];
