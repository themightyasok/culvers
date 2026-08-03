<?php

declare(strict_types=1);

namespace App\Directory;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Editor fields for individual events — short summary date / time / location
 * lines used on the archive cards. The single template uses flexible content
 * (typically `event_meta` for the full panel) so this stays focussed on the
 * minimum needed to render a card on /whats-on/.
 */
final class EventFields
{
    public static function register(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        $group = new FieldsBuilder('group_culvers_event_directory', [
            'title' => __('Event listing fields', 'culvers'),
        ]);

        $group->addImage('event_card_image', [
            'label' => __('Card image', 'culvers'),
            'instructions' => __(
                'Portrait photo for three-card blocks and the events archive grid. '
                . 'Falls back to the featured image, then the single-page hero image when empty.',
                'culvers'
            ),
            'return_format' => 'array',
            'preview_size' => 'medium',
            'library' => 'all',
        ]);

        $group->addText('event_card_date', [
            'label' => __('Date line (card)', 'culvers'),
            'instructions' => __('Short date for the card, e.g. "Sat 12 July" or "Thu 12 – Sun 15 July".', 'culvers'),
            'placeholder' => __('Sat 12 July', 'culvers'),
        ]);

        $group->addDatePicker('event_ends_on', [
            'label' => __('Event end date', 'culvers'),
            'instructions' => __(
                'Last day of the event (or the only day for one-offs). The event is automatically '
                . 'unpublished the following day. Leave empty for ongoing or recurring listings.',
                'culvers'
            ),
            'display_format' => 'j F Y',
            'return_format' => 'Ymd',
            'first_day' => 1,
            'required' => 0,
        ]);

        $group->addText('event_card_time', [
            'label' => __('Time line (card)', 'culvers'),
            'instructions' => __('Short time for the card, e.g. "10:00–16:00" or "Drop-in all day".', 'culvers'),
            'placeholder' => __('10:00–16:00', 'culvers'),
        ]);

        $group->addText('event_card_location', [
            'label' => __('Location (card)', 'culvers'),
            'instructions' => __('Short location for the card, e.g. "Centre square" or "Upper level".', 'culvers'),
            'placeholder' => __('Centre square', 'culvers'),
        ]);

        $group->setLocation('post_type', '==', 'culvers_event');

        acf_add_local_field_group($group->build());
    }
}
