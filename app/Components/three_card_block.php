<?php

/**
 * Three card block — hero-style row with optional blog category tabs + View all.
 */

use App\Helpers\Component;

$onlyWhen = static function (string $value): array {
    return [[['field' => 'cards_source', 'operator' => '==', 'value' => $value]]];
};
$onlyWhenAny = static function (array $values): array {
    $groups = [];
    foreach ($values as $v) {
        $groups[] = [['field' => 'cards_source', 'operator' => '==', 'value' => $v]];
    }
    return $groups;
};

return [
    'label' => __('Three card block', 'culvers'),
    'display' => 'block',
    'main' => [
        'msg_intro' => Component::sectionDivider(__('Intro copy', 'culvers')),
        'cards_subheading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Subheading', 'culvers'),
                'instructions' => __('Optional line above the heading (sans, uppercase styling in the theme).', 'culvers'),
            ],
        ],
        'cards_heading' => [
            'type' => 'text',
            'options' => [
                'label' => __('Heading', 'culvers'),
                'wrapper' => ['width' => '70'],
            ],
        ],
        'cards_heading_level' => Component::headingLevelField(allowH1: true, width: '30'),
        'cards_body' => [
            'type' => 'wysiwyg',
            'options' => [
                'label' => __('Body', 'culvers'),
                'instructions' => __('Supporting copy below the heading.', 'culvers'),
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            ],
        ],
        'msg_source' => Component::sectionDivider(__('Card source', 'culvers')),
        'cards_source' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Card source', 'culvers'),
                'instructions' => __(
                    'Manual entries, blog posts grouped by category tabs, '
                        . 'or the latest items from a single directory CPT '
                        . '(events, offers, news, shops, eat & drink, careers).',
                    'culvers'
                ),
                'choices' => [
                    'manual' => __('Manual (up to three cards)', 'culvers'),
                    'blog' => __('Blog posts (category tabs)', 'culvers'),
                    'cpt' => __('Directory CPT (latest items)', 'culvers'),
                ],
                'default_value' => 'manual',
                'layout' => 'horizontal',
                'return_format' => 'value',
            ],
        ],
        'cards_blog_categories' => [
            'type' => 'taxonomy',
            'options' => [
                'label' => __('Category tabs', 'culvers'),
                'instructions' => __(
                    'Order of selected categories defines tab order. Each tab lists recent posts for that category.',
                    'culvers'
                ),
                'taxonomy' => 'category',
                'field_type' => 'multi_select',
                'return_format' => 'id',
                'allow_null' => 1,
                'conditional_logic' => $onlyWhen('blog'),
            ],
        ],
        'cards_blog_per_category' => [
            'type' => 'number',
            'options' => [
                'label' => __('Posts per tab', 'culvers'),
                'instructions' => __('How many posts to show when a tab is active (up to three columns).', 'culvers'),
                'default_value' => 3,
                'min' => 1,
                'max' => 12,
                'step' => 1,
                'conditional_logic' => $onlyWhen('blog'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'cards_cpt_post_type' => [
            /* Multi-select so a single block can power a "What are you looking for today?"
               toggle that flips between News / Events / Offers cards. One tab per selected
               CPT (tab label = the human label below). Order of selection = tab order. */
            'type' => 'select',
            'options' => [
                'label' => __('Directory CPTs', 'culvers'),
                'instructions' => __(
                    'Pick one or more directories. Each becomes a toggle tab above the cards; '
                        . 'switching tabs fades the row to the latest items from that directory.',
                    'culvers'
                ),
                'choices' => [
                    'culvers_event' => __('Events', 'culvers'),
                    'culvers_offer' => __('Offers', 'culvers'),
                    'culvers_news' => __('News', 'culvers'),
                    'culvers_shop' => __('Shops', 'culvers'),
                    'culvers_eat_drink' => __('Eat & Drink', 'culvers'),
                    'culvers_career' => __('Careers', 'culvers'),
                ],
                'multiple' => 1,
                'ui' => 1,
                'ajax' => 0,
                'default_value' => ['culvers_event'],
                'allow_null' => 0,
                'return_format' => 'value',
                'conditional_logic' => $onlyWhen('cpt'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'cards_cpt_count' => [
            'type' => 'number',
            'options' => [
                'label' => __('Cards to show', 'culvers'),
                'instructions' => __('Up to three columns; extra items wrap on smaller breakpoints.', 'culvers'),
                'default_value' => 3,
                'min' => 1,
                'max' => 12,
                'step' => 1,
                'conditional_logic' => $onlyWhen('cpt'),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'msg_view_all' => Component::sectionDivider(__('View-all link', 'culvers')),
        'cards_view_all_url' => [
            'type' => 'url',
            'options' => [
                'label' => __('View all URL', 'culvers'),
                'instructions' => __(
                    'Typically your blog index, the matching CPT archive, or any landing page. '
                        . 'Leave blank when source is "Directory CPT" to auto-link to that CPT\'s archive.',
                    'culvers'
                ),
                'default_value' => '',
                'conditional_logic' => $onlyWhenAny(['blog', 'cpt']),
                'wrapper' => ['width' => '50'],
            ],
        ],
        'cards_view_all_label' => [
            'type' => 'text',
            'options' => [
                'label' => __('View all label', 'culvers'),
                'default_value' => __('View all', 'culvers'),
                'conditional_logic' => $onlyWhenAny(['blog', 'cpt']),
                'wrapper' => ['width' => '50'],
            ],
        ],
    ],
    'items' => [
        'cards_items_help' => [
            'type' => 'message',
            'options' => [
                'message' => __(
                    'These manual cards are only rendered when <strong>Card source</strong> on the '
                    . '<em>Main</em> tab is set to <strong>Manual</strong>. For blog or directory CPT '
                    . 'sources, the row builds itself from those queries and ignores this list.',
                    'culvers'
                ),
                'esc_html' => 0,
                'wrapper' => ['class' => 'culvers-acf-help'],
            ],
        ],
        'cards_items' => [
            'type' => 'repeater',
            'options' => [
                'label' => __('Cards (manual)', 'culvers'),
                'instructions' => __(
                    'Exactly three cards recommended. Video plays while hovered (respects reduced motion). '
                        . 'Only used when source is "Manual".',
                    'culvers'
                ),
                'min' => 0,
                'max' => 3,
                'layout' => 'block',
                'button_label' => __('Add card', 'culvers'),
                'collapsed' => 'card_title',
                'conditional_logic' => $onlyWhen('manual'),
                'sub_fields' => [
                    'card_title' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Card title', 'culvers'),
                            'required' => 1,
                            'wrapper' => ['width' => '70'],
                        ],
                    ],
                    'card_media_type' => [
                        'type' => 'radio',
                        'options' => [
                            'label' => __('Media', 'culvers'),
                            'choices' => [
                                'image' => __('Image', 'culvers'),
                                'video' => __('Video', 'culvers'),
                            ],
                            'default_value' => 'image',
                            'layout' => 'horizontal',
                            'return_format' => 'value',
                            'wrapper' => ['width' => '30'],
                        ],
                    ],
                    'card_url' => [
                        'type' => 'url',
                        'options' => [
                            'label' => __('Link URL', 'culvers'),
                            'required' => 1,
                        ],
                    ],
                    'card_image' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Image', 'culvers'),
                            'instructions' => __('Used when media is Image.', 'culvers'),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'library' => 'all',
                            'conditional_logic' => [[[
                                'field' => 'card_media_type',
                                'operator' => '==',
                                'value' => 'image',
                            ]]],
                        ],
                    ],
                    'card_image_alt' => [
                        'type' => 'text',
                        'options' => [
                            'label' => __('Image alt text', 'culvers'),
                            'instructions' => __('Important for screen readers when using an image.', 'culvers'),
                            'conditional_logic' => [[[
                                'field' => 'card_media_type',
                                'operator' => '==',
                                'value' => 'image',
                            ]]],
                        ],
                    ],
                    'card_video' => [
                        'type' => 'file',
                        'options' => [
                            'label' => __('Video file', 'culvers'),
                            'instructions' => __('Used when media is Video.', 'culvers'),
                            'mime_types' => 'mp4,webm',
                            'return_format' => 'array',
                            'library' => 'all',
                            'conditional_logic' => [[[
                                'field' => 'card_media_type',
                                'operator' => '==',
                                'value' => 'video',
                            ]]],
                        ],
                    ],
                    'card_video_poster' => [
                        'type' => 'image',
                        'options' => [
                            'label' => __('Video poster (optional)', 'culvers'),
                            'instructions' => __(
                                'Not used on the live card — the first frame of the video file is shown until hover.',
                                'culvers'
                            ),
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'library' => 'all',
                            'conditional_logic' => [[[
                                'field' => 'card_media_type',
                                'operator' => '==',
                                'value' => 'video',
                            ]]],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
