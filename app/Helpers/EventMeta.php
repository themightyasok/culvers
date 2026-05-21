<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Default row labels for the event_meta flexible layout (per directory CPT).
 * Legacy values saved before label fields were removed from ACF are still honoured.
 */
final class EventMeta
{
    /**
     * @param array<string, mixed> $component
     */
    public static function rowLabel(string $key, array $component): string
    {
        $legacy = trim((string) ($component['event_meta_' . $key . '_label'] ?? ''));
        if ($legacy !== '') {
            return $legacy;
        }

        return self::defaultRowLabel($key);
    }

    public static function defaultRowLabel(string $key): string
    {
        $postType = is_string(get_post_type()) ? get_post_type() : '';

        return match ($postType) {
            'culvers_offer' => match ($key) {
                'date' => __('Offer valid', 'culvers'),
                'time' => __('Open today', 'culvers'),
                'location' => __('Where', 'culvers'),
                default => '',
            },
            'culvers_news' => match ($key) {
                'date' => __('Published', 'culvers'),
                'time' => __('Reading time', 'culvers'),
                'location' => __('Category', 'culvers'),
                default => '',
            },
            'culvers_event' => match ($key) {
                'date' => __('When', 'culvers'),
                'time' => __('Time', 'culvers'),
                'location' => __('Where', 'culvers'),
                default => '',
            },
            default => match ($key) {
                'date' => __('Date', 'culvers'),
                'time' => __('Time', 'culvers'),
                'location' => __('Location', 'culvers'),
                default => '',
            },
        };
    }
}
