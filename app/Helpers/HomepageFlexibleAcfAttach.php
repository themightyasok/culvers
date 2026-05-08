<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Turns remote URLs in homepage flexible payloads into Media Library attachment IDs
 * so ACF file/image fields persist (raw URLs are stripped on save).
 */
final class HomepageFlexibleAcfAttach
{
    /** @var array<string, int> attachment ID or 0 when sideload failed */
    private static array $urlCache = [];

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public static function attachFlexibleRows(array $rows): array
    {
        return self::withExpandedUploadMimes(function () use ($rows): array {
            self::$urlCache = [];

            $out = [];
            foreach ($rows as $row) {
                $layout = isset($row['acf_fc_layout']) ? Cast::toString($row['acf_fc_layout']) : '';
                $out[] = match ($layout) {
                    'hero_slider' => self::heroSliderRow($row),
                    'three_card_block' => self::threeCardBlockRow($row),
                    'horizontal_scroller' => self::horizontalScrollerRow($row),
                    'video_block' => self::videoBlockRow($row),
                    'opening_hours' => self::openingHoursRow($row),
                    'image_hero' => self::imageHeroRow($row),
                    'shop_split_highlight' => self::shopSplitHighlightRow($row),
                    'info_block' => self::infoBlockRow($row),
                    'text_image_slider' => self::textImageSliderRow($row),
                    'faq' => self::faqRow($row),
                    'centre_map' => self::centreMapRow($row),
                    'travel_calculator' => self::travelCalculatorRow($row),
                    default => $row,
                };
            }

            return $out;
        });
    }

    /**
     * @template T
     * @param  callable(): T  $fn
     * @return T
     */
    private static function withExpandedUploadMimes(callable $fn): mixed
    {
        $filter = static function (array $mimes): array {
            $mimes['webp'] ??= 'image/webp';
            $mimes['svg'] ??= 'image/svg+xml';
            $mimes['avif'] ??= 'image/avif';

            return $mimes;
        };
        add_filter('upload_mimes', $filter, 999);
        try {
            return $fn();
        } finally {
            remove_filter('upload_mimes', $filter, 999);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function heroSliderRow(array $row): array
    {
        $slides = $row['hero_slides'] ?? [];
        if (! is_array($slides)) {
            return $row;
        }
        foreach ($slides as $i => $slide) {
            if (! is_array($slide)) {
                continue;
            }
            $slides[$i]['slide_image'] = self::acfImageValue($slide['slide_image'] ?? null);
            $slides[$i]['slide_image_mobile'] = self::acfImageValue($slide['slide_image_mobile'] ?? null);
        }
        $row['hero_slides'] = $slides;

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function threeCardBlockRow(array $row): array
    {
        $cards = $row['cards_items'] ?? [];
        if (! is_array($cards)) {
            return $row;
        }
        foreach ($cards as $i => $card) {
            if (! is_array($card)) {
                continue;
            }
            $cards[$i]['card_video'] = self::acfFileValue($card['card_video'] ?? null);
            $cards[$i]['card_video_poster'] = self::acfImageValue($card['card_video_poster'] ?? null);
            $cards[$i]['card_image'] = self::acfImageValue($card['card_image'] ?? null);
        }
        $row['cards_items'] = $cards;

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function horizontalScrollerRow(array $row): array
    {
        $cards = $row['scroller_items'] ?? [];
        if (! is_array($cards)) {
            return $row;
        }
        foreach ($cards as $i => $item) {
            if (! is_array($item)) {
                continue;
            }
            $cards[$i]['item_image'] = self::acfImageValue($item['item_image'] ?? null);
        }
        $row['scroller_items'] = $cards;

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function videoBlockRow(array $row): array
    {
        $row['video_file'] = self::acfFileValue($row['video_file'] ?? null);
        $row['video_poster'] = self::acfImageValue($row['video_poster'] ?? null);

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function openingHoursRow(array $row): array
    {
        $row['hours_graphic_left'] = self::acfImageValue($row['hours_graphic_left'] ?? null);
        $row['hours_graphic_right'] = self::acfImageValue($row['hours_graphic_right'] ?? null);

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function imageHeroRow(array $row): array
    {
        $row['hero_image'] = self::acfImageValue($row['hero_image'] ?? null);
        $row['hero_image_mobile'] = self::acfImageValue($row['hero_image_mobile'] ?? null);
        $row['hero_logo'] = self::acfImageValue($row['hero_logo'] ?? null);

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function shopSplitHighlightRow(array $row): array
    {
        $row['split_image'] = self::acfImageValue($row['split_image'] ?? null);
        $tabs = $row['split_tabs'] ?? [];
        if (is_array($tabs)) {
            foreach ($tabs as $i => $tab) {
                if (! is_array($tab)) {
                    continue;
                }
                $tabs[$i]['tab_image'] = self::acfImageValue($tab['tab_image'] ?? null);
            }
            $row['split_tabs'] = $tabs;
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function infoBlockRow(array $row): array
    {
        $items = $row['info_items'] ?? [];
        if (! is_array($items)) {
            return $row;
        }
        foreach ($items as $i => $item) {
            if (! is_array($item)) {
                continue;
            }
            $items[$i]['item_image'] = self::acfImageValue($item['item_image'] ?? null);
        }
        $row['info_items'] = $items;

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function textImageSliderRow(array $row): array
    {
        $items = $row['tis_items'] ?? [];
        if (! is_array($items)) {
            return $row;
        }
        foreach ($items as $i => $item) {
            if (! is_array($item)) {
                continue;
            }
            $items[$i]['item_image_left'] = self::acfImageValue($item['item_image_left'] ?? null);
            $items[$i]['item_image_right'] = self::acfImageValue($item['item_image_right'] ?? null);
        }
        $row['tis_items'] = $items;

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function faqRow(array $row): array
    {
        foreach (['faq_decorations_left', 'faq_decorations_right'] as $key) {
            $items = $row[$key] ?? [];
            if (! is_array($items)) {
                continue;
            }
            foreach ($items as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $items[$i]['item_image'] = self::acfImageValue($item['item_image'] ?? null);
            }
            $row[$key] = $items;
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function centreMapRow(array $row): array
    {
        $row['centre_map_image'] = self::acfImageValue($row['centre_map_image'] ?? null);

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function travelCalculatorRow(array $row): array
    {
        $row['tc_map_initial_image'] = self::acfImageValue($row['tc_map_initial_image'] ?? null);

        return $row;
    }

    /**
     * @param  array<string, mixed>|mixed  $field
     */
    private static function acfImageValue(mixed $field): ?int
    {
        if (is_numeric($field) && (int) $field > 0) {
            return (int) $field;
        }
        $url = self::extractUrl($field);
        if ($url === null || $url === '') {
            return null;
        }
        $id = self::attachmentIdFromUrl($url);

        return $id > 0 ? $id : null;
    }

    /**
     * @param  array<string, mixed>|mixed  $field
     */
    private static function acfFileValue(mixed $field): ?int
    {
        return self::acfImageValue($field);
    }

    /**
     * @param  array<string, mixed>|mixed  $field
     */
    private static function extractUrl(mixed $field): ?string
    {
        if (! is_array($field)) {
            return null;
        }
        $url = isset($field['url']) ? trim((string) $field['url']) : '';

        return $url !== '' ? $url : null;
    }

    private static function guessExtension(string $mime, string $url): string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '');
        if (preg_match('/\.([a-z0-9]{2,5})$/i', $path, $m)) {
            return strtolower($m[1]);
        }

        return match (true) {
            str_contains($mime, 'jpeg') => 'jpg',
            str_contains($mime, 'png') => 'png',
            str_contains($mime, 'webp') => 'webp',
            str_contains($mime, 'gif') => 'gif',
            str_contains($mime, 'mp4') => 'mp4',
            str_contains($mime, 'webm') => 'webm',
            str_contains($mime, 'svg') => 'svg',
            default => 'jpg',
        };
    }

    private static function mimeFromHead(string $url): string
    {
        $response = wp_remote_head($url, [
            'timeout' => 15,
            'redirection' => 5,
            'reject_unsafe_urls' => false,
        ]);
        if (is_wp_error($response)) {
            return '';
        }
        $ct = wp_remote_retrieve_header($response, 'content-type');
        if (! is_string($ct) || $ct === '') {
            return '';
        }
        $parts = explode(';', $ct, 2);

        return strtolower(trim($parts[0]));
    }

    /**
     * Download once per URL (per process), sideload as attachment.
     */
    private static function attachmentIdFromUrl(string $url): int
    {
        if (array_key_exists($url, self::$urlCache)) {
            return self::$urlCache[$url];
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($url, 120);
        if (is_wp_error($tmp)) {
            self::cliWarn('download_url failed for ' . $url . ' — ' . $tmp->get_error_message());
            self::$urlCache[$url] = 0;

            return 0;
        }

        $mime = '';
        if (is_readable($tmp) && function_exists('mime_content_type')) {
            $mime = @mime_content_type($tmp) ?: '';
        }
        if ($mime === '' || $mime === 'application/octet-stream') {
            $headMime = self::mimeFromHead($url);
            if ($headMime !== '') {
                $mime = $headMime;
            }
        }

        $filename = 'culvers-homepage-' . substr(md5($url), 0, 16) . '.' . self::guessExtension((string) $mime, $url);
        $file_array = [
            'name' => $filename,
            'tmp_name' => $tmp,
        ];

        $attach_id = media_handle_sideload($file_array, 0);
        if (is_wp_error($attach_id)) {
            self::cliWarn('media_handle_sideload failed for ' . $url . ' — ' . $attach_id->get_error_message());
            @unlink($tmp);
            self::$urlCache[$url] = 0;

            return 0;
        }

        $id = (int) $attach_id;
        self::$urlCache[$url] = $id > 0 ? $id : 0;

        return $id > 0 ? $id : 0;
    }

    private static function cliWarn(string $message): void
    {
        if (defined('WP_CLI') && WP_CLI && class_exists(\WP_CLI::class)) {
            \WP_CLI::warning($message);
        }
    }
}
