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
        'msg_title' => Component::sectionDivider(__('Job header', 'culvers')),
        'career_sidebar_brand_logo' => [
            'type' => 'image',
            'options' => [
                'label' => __('Employer logo (sidebar)', 'culvers'),
                'instructions' => __(
                    'Shown above the job title in this band. Omit when the hero already carries the retailer lockup.',
                    'culvers'
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
        ],
        'career_job_title' => [
            'type' => 'text',
            'options' => [
                'label' => __('Job title', 'culvers'),
                'instructions' => __(
                    'Large serif headline (e.g. Senior Supervisor). Renders as the page H1 when the hero '
                    . 'does not already carry the title.',
                    'culvers'
                ),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'career_job_title_level' => Component::headingLevelField(
            __('Usually H1 for the job title on a career single.', 'culvers'),
            true,
            1,
            '30'
        ),
        'career_section_heading_level' => Component::headingLevelField(
            __('Heading level for each role section in the right column.', 'culvers'),
            false,
            2,
            '50'
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
        'career_apply_email' => [
            'type' => 'email',
            'options' => [
                'label' => __('Apply-to email', 'culvers'),
                'instructions' => __(
                    'Opens the visitor\'s email client with the job title and listing URL pre-filled. '
                    . 'Falls back to the job-level Apply-to email when left blank.',
                    'culvers'
                ),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'career_apply_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('Apply URL (optional fallback)', 'culvers'),
                'instructions' => __(
                    'External application link when no apply-to email is set (e.g. employer ATS).',
                    'culvers'
                ),
                'wrapper' => ['width' => '50'],
            ],
        ],
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
