<?php

/**
 * Three card block — hero-style row with optional blog category tabs + View all.
 * Cards always come from published posts (directory CPTs or blog categories).
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
        'cards_heading_level' => Component::headingLevelField(
            __('Use H1 when this row is the main page title (e.g. homepage strip).', 'culvers'),
            true,
            2,
            '30'
        ),
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
        'cards_media_overlay_opacity' => Component::overlayOpacityRangeField(
            __('Card media overlay darkness', 'culvers'),
            __(
                'Black overlay on each card image (0% = none). '
                    . 'Helps white title text stay readable over bright photography.',
                'culvers'
            ),
            25,
            '50'
        ),
        'msg_source' => Component::sectionDivider(__('Card source', 'culvers')),
        'cards_source' => [
            'type' => 'radio',
            'options' => [
                'label' => __('Card source', 'culvers'),
                'instructions' => __(
                    'Pick published posts from directory CPTs (events, offers, news, shops, eat & drink, careers) '
                        . 'or from blog categories. Card title, image, and link always come from the post.',
                    'culvers'
                ),
                'choices' => [
                    'cpt' => __('Directory posts (latest items)', 'culvers'),
                    'blog' => __('Blog posts (category tabs)', 'culvers'),
                ],
                'default_value' => 'cpt',
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
                    'Typically the matching CPT archive or blog index. '
                        . 'Leave blank for directory CPT mode to auto-link to that CPT\'s archive.',
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
];
