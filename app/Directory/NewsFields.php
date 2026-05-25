<?php

declare(strict_types=1);

namespace App\Directory;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Editor fields for an individual news article — short summary lines used
 * on the `/latest-news/` archive cards. Mirrors {@see OfferFields};
 * news single templates use flexible content for the full article panel.
 */
final class NewsFields
{
    public static function register(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        $group = new FieldsBuilder('group_culvers_news_directory', [
            'title' => __('News listing fields', 'culvers'),
        ]);

        $group->addImage('news_card_image', [
            'label' => __('Card image', 'culvers'),
            'instructions' => __(
                'Portrait photo for three-card blocks and the news archive grid. '
                . 'Falls back to the featured image, then the single-page hero image when empty.',
                'culvers'
            ),
            'return_format' => 'array',
            'preview_size' => 'medium',
            'library' => 'all',
        ]);

        $group->addText('news_card_eyebrow', [
            'label' => __('Eyebrow line (card)', 'culvers'),
            'instructions' => __(
                'Short eyebrow shown above the article title on the card, e.g. "Centre news" or "Press release". '
                . 'Falls back to the primary News category when empty.',
                'culvers'
            ),
            'placeholder' => __('Centre news', 'culvers'),
        ]);

        $group->addDatePicker('news_card_published_on', [
            'label' => __('Display publish date (card)', 'culvers'),
            'instructions' => __(
                'Optional override for the date shown under the article title on the card. '
                . 'Defaults to the WordPress publish date when empty.',
                'culvers'
            ),
            'display_format' => 'j F Y',
            'return_format' => 'j F Y',
            'first_day' => 1,
        ]);

        $group->setLocation('post_type', '==', 'culvers_news');

        acf_add_local_field_group($group->build());
    }
}
