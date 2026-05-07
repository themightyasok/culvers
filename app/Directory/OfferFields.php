<?php

declare(strict_types=1);

namespace App\Directory;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Editor fields for an individual offer — small summary lines used on the
 * `/latest-offers/` archive cards. Mirrors {@see EventFields}; offer
 * single templates use flexible content for the full panel.
 */
final class OfferFields
{
    public static function register(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        $group = new FieldsBuilder('group_culvers_offer_directory', [
            'title' => __('Offer listing fields', 'culvers'),
        ]);

        $group->addText('offer_card_validity', [
            'label' => __('Validity line (card)', 'culvers'),
            'instructions' => __('Short validity window for the card, e.g. "Until 14 Feb" or "While stocks last".', 'culvers'),
            'placeholder' => __('Until 14 Feb', 'culvers'),
        ]);

        $group->addText('offer_card_venue', [
            'label' => __('Venue / brand (card)', 'culvers'),
            'instructions' => __('Short venue or brand line, e.g. "Hotel Chocolat" or "Lower Level".', 'culvers'),
            'placeholder' => __('Hotel Chocolat', 'culvers'),
        ]);

        $group->setLocation('post_type', '==', 'culvers_offer');

        acf_add_local_field_group($group->build());
    }
}
