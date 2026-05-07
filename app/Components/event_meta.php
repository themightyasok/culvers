<?php

/**
 * Event meta — compact "When / Where / Tickets" panel for single event pages
 * (date(s), start/end time, venue, accessible-info note, primary CTA). Sits
 * directly below the event hero so visitors can scan the practical details
 * before the long-form description. Date / time fields are plain text so
 * editors can write "Thu 12 Jun 2026" / "10:00–16:00" without fighting a
 * picker — for ranges and multi-day events this is the simplest source of
 * truth. Single CTA covers the typical "Book tickets" / "Reserve a spot"
 * flow.
 */

return [
    'label' => __('Event meta', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],
        'event_meta_date_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Date label', 'culvers'),
                'default_value' => __('Date', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'event_meta_date_value' => [
            'type' => 'text',
            'options' => [
                'label' => __('Date value', 'culvers'),
                'instructions' => __('e.g. "Thursday 12 June 2026" or "Thu 12 – Sun 15 June 2026".', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'event_meta_time_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Time label', 'culvers'),
                'default_value' => __('Time', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'event_meta_time_value' => [
            'type' => 'text',
            'options' => [
                'label' => __('Time value', 'culvers'),
                'instructions' => __('e.g. "10:00–16:00" or "Drop-in all day".', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'event_meta_location_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('Location label', 'culvers'),
                'default_value' => __('Location', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'event_meta_location_value' => [
            'type' => 'text',
            'options' => [
                'label' => __('Location value', 'culvers'),
                'instructions' => __('e.g. "Centre square" / "Outside Pandora" / "Upper level near M&S".', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'event_meta_accessibility_note' => [
            'type' => 'textarea',
            'options' => [
                'label' => __('Accessibility note (optional)', 'culvers'),
                'instructions' => __(
                    'Short note about accessibility (e.g. "Sensory-friendly hour 10:00–11:00. BSL interpreter available."). Renders below the meta rows.',
                    'culvers'
                ),
                'rows' => 2,
                'new_lines' => 'br',
            ],
        ],
        'event_meta_cta_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('CTA label (optional)', 'culvers'),
                'instructions' => __('e.g. "Book tickets", "Reserve a spot", "Add to calendar".', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'event_meta_cta_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('CTA URL', 'culvers'),
                'instructions' => __('External booking link or internal page.', 'culvers'),
                'wrapper' => ['width' => '50'],
            ],
        ],
    ],
];
