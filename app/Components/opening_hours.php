<?php

/**
 * Opening hours list with optional side illustrations, intro copy, and "today" highlight (site timezone).
 */

declare(strict_types=1);

use App\Helpers\Component;

return [
    'label' => __('Opening hours', 'culvers'),
    'display' => 'block',
    'main' => [
        'msg_context' => Component::sectionDivider(__('Context', 'culvers')),
        'hours_context' => [
            'type' => 'select',
            'options' => [
                'label' => __('Hours context', 'culvers'),
                'instructions' => __(
                    'Retailer: this venue\'s own opening hours (shop / eat & drink singles). '
                        . 'Centre: site-wide centre hours (homepage, Plan My Visit, Guest Services). '
                        . 'The day rows below are always saved on this page — they are not shared globally.',
                    'culvers'
                ),
                'choices' => \App\Helpers\OpeningHoursContext::choices(),
                'default_value' => \App\Helpers\OpeningHoursContext::CENTRE,
                'allow_null' => 0,
                'return_format' => 'value',
                'wrapper' => ['width' => '50'],
            ],
        ],
        'msg_intro' => Component::sectionDivider(__('Intro copy', 'culvers')),
        'hours_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'hours_heading_level' => Component::headingLevelField(null, false, 2, '30'),
        'hours_subheading' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Subheading', 'culvers'),
                'rows' => 2,
                'new_lines' => 'br',
            ],
        ],
        'hours_body' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Body', 'culvers'),
                'tabs' => 'all',
                'toolbar' => 'basic',
                'media_upload' => 0,
            ],
        ],
        'hours_footnote' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Footnote', 'culvers'),
                'instructions' => __('Small note below the list (e.g. holiday hours).', 'culvers'),
                'rows' => 2,
                'new_lines' => 'br',
            ],
        ],
    ],
    'items' => [
        'msg_decorations' => Component::sectionDivider(__('Side illustrations', 'culvers')),
        'hours_graphic_left' => [
            'type' => 'image',
            'options' => [
                'label' => __('Illustration — left', 'culvers'),
                'instructions' => __(
                    'Optional artwork on the left of the hours list (large screens). Each Opening hours block on this page can use its own image.',
                    'culvers'
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
                'wrapper' => ['width' => '50'],
            ],
        ],
        'hours_graphic_left_alt' => [
            'type' => 'text',
            'options' => [
                'label' => __('Left illustration alt text', 'culvers'),
                'instructions' => __('Optional. Overrides the media library alt text when set.', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'hours_graphic_right' => [
            'type' => 'image',
            'options' => [
                'label' => __('Illustration — right', 'culvers'),
                'instructions' => __(
                    'Optional artwork on the right of the hours list (large screens). Each Opening hours block on this page can use its own image.',
                    'culvers'
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
                'wrapper' => ['width' => '50'],
            ],
        ],
        'hours_graphic_right_alt' => [
            'type' => 'text',
            'options' => [
                'label' => __('Right illustration alt text', 'culvers'),
                'instructions' => __('Optional. Overrides the media library alt text when set.', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'msg_centre_hours' => [
            'type' => 'message',
            'options' => [
                'label' => '',
                'message' => sprintf(
                    /* translators: %s admin URL of the Centre Hours options page */
                    __(
                        'Centre-wide hours are managed in one place so every centre page stays in sync. '
                            . 'Edit them under <a href="%s">Centre Hours</a>. The day rows below are only used '
                            . 'for individual shops / eat &amp; drink venues (set the context to “Retailer”).',
                        'culvers'
                    ),
                    esc_url(\App\Directory\CentreOpeningHours::adminUrl())
                ),
                'esc_html' => 0,
                'new_lines' => 'wpautop',
                'conditional_logic' => [[[
                    'field' => 'hours_context',
                    'operator' => '==',
                    'value' => \App\Helpers\OpeningHoursContext::CENTRE,
                ]]],
            ],
        ],
        'hours_rows' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Hours', 'culvers'),
                'instructions' => __(
                    'Add rows in any order. Set "Match weekday for highlight" so the correct row is highlighted on '
                        . 'that day (e.g. label "Easter Sunday" can still match Sunday).',
                    'culvers'
                ),
                'min' => 0,
                'max' => 14,
                'layout' => 'table',
                'button_label' => __('Add row', 'culvers'),
                'conditional_logic' => [[[
                    'field' => 'hours_context',
                    'operator' => '==',
                    'value' => \App\Helpers\OpeningHoursContext::RETAILER,
                ]]],
                'sub_fields' => [
                    'day_label' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Day / title', 'culvers'),
                            'instructions' => __('Displayed label (e.g. Monday, Easter Sunday).', 'culvers'),
                            'required' => 1,
                            'wrapper' => ['width' => '34'],
                        ],
                    ],
                    'time_range' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Hours', 'culvers'),
                            'instructions' => __('e.g. 9am – 5:30pm or Closed', 'culvers'),
                            'required' => 1,
                            'wrapper' => ['width' => '33'],
                        ],
                    ],
                    'weekday_highlight' => [
                        'type' => 'select',
                        'options' => [
                            'label' => __('Match weekday for highlight', 'culvers'),
                            'instructions' => __(
                                'Uses the site timezone. Choose "None" for special rows that should never highlight.',
                                'culvers'
                            ),
                            'choices' => [
                                'none' => __('None', 'culvers'),
                                'sun' => __('Sunday', 'culvers'),
                                'mon' => __('Monday', 'culvers'),
                                'tue' => __('Tuesday', 'culvers'),
                                'wed' => __('Wednesday', 'culvers'),
                                'thu' => __('Thursday', 'culvers'),
                                'fri' => __('Friday', 'culvers'),
                                'sat' => __('Saturday', 'culvers'),
                            ],
                            'default_value' => 'none',
                            'allow_null' => 0,
                            'return_format' => 'value',
                            'wrapper' => ['width' => '33'],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
