<?php

/**
 * Career detail — split job-header band: left sidebar with the job title,
 * meta rows (Contract Type / Location / Pay) separated by hairline rules,
 * and an Apply CTA; right column with stacked role sections.
 */

use App\Helpers\Component;

return [
    'label' => __('Career — detail', 'culvers'),
    'display' => 'block',
    'main' => [
        'msg_title' => Component::sectionDivider(__('Job title', 'culvers')),
        'career_job_title' => [
            'type' => 'text',
            'options' => [
                'label' => __('Job title', 'culvers'),
                'instructions' => __('Display serif headline shown top-left (e.g. Senior Supervisor).', 'culvers'),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'career_job_title_level' => Component::headingLevelField(
            __('Use H1 only when the page above does not already host one (typically the image hero).', 'culvers'),
            allowH1: true,
            default: 1,
            width: '30',
        ),
        'msg_meta' => Component::sectionDivider(__('Meta rows', 'culvers')),
        'career_meta' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Meta rows', 'culvers'),
                'instructions' => __(
                    'Stacked rows of small uppercase label + value, separated by hairline rules ' .
                    '(e.g. Contract Type / Full-Time, Location / Culver Square, Pay / £12.40 per hour).',
                    'culvers'
                ),
                'min' => 0,
                'max' => 6,
                'layout' => 'table',
                'button_label' => __('Add meta row', 'culvers'),
                'sub_fields' => [
                    'item_label' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Label', 'culvers'),
                            'wrapper' => ['width' => '40'],
                        ],
                    ],
                    'item_value' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Value', 'culvers'),
                            'wrapper' => ['width' => '60'],
                        ],
                    ],
                ],
            ],
        ],
        'msg_apply' => Component::sectionDivider(__('Apply call to action', 'culvers')),
        'career_apply_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Apply button label', 'culvers'),
                'default_value' => __('Apply Now', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'career_apply_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('Apply URL', 'culvers'),
                'instructions' => __('Where the apply button takes the candidate (e.g. employer ATS).', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'msg_section_levels' => Component::sectionDivider(__('Role sections — defaults', 'culvers')),
        'career_section_heading_level' => Component::headingLevelField(
            __('Default H2 — applies to every role section heading on the right.', 'culvers'),
        ),
    ],
    'items' => [
        'career_sections' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Role sections', 'culvers'),
                'instructions' => __(
                    'Each row renders as heading + body in the right-hand column ' .
                    '(e.g. About the role, Work Schedule, Key Responsibilities, Qualifications). ' .
                    'Use bullet lists for responsibilities / qualifications.',
                    'culvers'
                ),
                'min' => 0,
                'max' => 12,
                'layout' => 'block',
                'button_label' => __('Add section', 'culvers'),
                'collapsed' => 'item_heading',
                'sub_fields' => [
                    'item_heading' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Section heading', 'culvers'),
                        ],
                    ],
                    'item_body' => [
                        'type' => 'wysiwyg',
                        'options' => [
                            'label' => __('Section body', 'culvers'),
                            'tabs' => 'all',
                            'toolbar' => 'basic',
                            'media_upload' => 0,
                        ],
                    ],
                ],
            ],
        ],
    ],
];
