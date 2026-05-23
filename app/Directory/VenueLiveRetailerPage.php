<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Scrapes culversquare.co.uk `/retailers/{slug}/` pages (shops + dining listings).
 */
final class VenueLiveRetailerPage
{
    private const RETAILER_BASE = 'https://www.culversquare.co.uk/retailers/';

    /**
     * @return array{
     *     title: string,
     *     phone: string,
     *     website: string,
     *     logo_url: string,
     *     paras: list<string>,
     *     lists: list<list<string>>,
     *     opening_hours_rows: list<array{day_label: string, time_range: string, weekday_highlight: string}>
     * }|null
     */
    public static function fetch(string $liveSlug): ?array
    {
        $url = self::RETAILER_BASE . rawurlencode($liveSlug) . '/';
        $html = self::fetchHtml($url);
        if ($html === '') {
            return null;
        }

        $html = (string) preg_replace('#<script[^>]*>.*?</script>#is', '', $html);
        $html = (string) preg_replace('#<style[^>]*>.*?</style>#is', '', $html);

        if (! preg_match('#<h2[^>]*>([^<]+)</h2>#i', $html, $titleMatch)) {
            return null;
        }

        $title = self::plainText($titleMatch[1]);

        $phone = self::extractPhone($html);

        $website = '';
        if (
            preg_match(
                '#retailer_buttons.*?href="(https?://[^"]+)"[^>]*target="_blank"[^>]*class="button"[^>]*>([^<]+)</a>#is',
                $html,
                $webMatch
            )
        ) {
            $website = esc_url_raw(trim($webMatch[1]));
        }

        $logoUrl = '';
        if (
            preg_match(
                '#<h2[^>]*>[^<]+</h2>.*?<img[^>]+src="([^"]+)"#is',
                $html,
                $logoMatch
            )
        ) {
            $logoUrl = esc_url_raw(trim($logoMatch[1]));
        }

        $chunk = self::contentChunk($html);

        /** @var list<string> $paras */
        $paras = [];
        if ($chunk !== '' && preg_match_all('#<p[^>]*>(.*?)</p>#is', $chunk, $pMatches)) {
            foreach ($pMatches[1] as $inner) {
                if (preg_match('#<img\b#i', (string) $inner) && self::plainText((string) $inner) === '') {
                    continue;
                }
                $text = self::plainText((string) $inner);
                if ($text === '' || strlen($text) < 25) {
                    continue;
                }
                if (self::isBoilerplateLine($text)) {
                    continue;
                }
                $paras[] = $text;
            }
        }

        /** @var list<list<string>> $lists */
        $lists = [];
        if ($chunk !== '' && preg_match_all('#<ul[^>]*>(.*?)</ul>#is', $chunk, $ulMatches)) {
            foreach ($ulMatches[1] as $ulInner) {
                /** @var list<string> $items */
                $items = [];
                if (preg_match_all('#<li[^>]*>(.*?)</li>#is', (string) $ulInner, $liMatches)) {
                    foreach ($liMatches[1] as $li) {
                        $t = self::plainText((string) $li);
                        if ($t !== '') {
                            $items[] = $t;
                        }
                    }
                }
                if ($items !== []) {
                    $lists[] = $items;
                }
            }
        }

        return [
            'title' => $title,
            'phone' => self::formatDisplayPhone($phone),
            'website' => $website,
            'logo_url' => $logoUrl,
            'paras' => ShopLiveIntroCopy::filterPromoLinesPublic($paras),
            'lists' => $lists,
            'opening_hours_rows' => VenueOpeningHours::rowsFromHtml($html),
        ];
    }

    public static function formatDisplayPhone(string $phone): string
    {
        $phone = trim(html_entity_decode($phone, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($phone === '') {
            return '';
        }

        if (preg_match('/\s/', $phone) === 1 && ! str_starts_with($phone, '+')) {
            return $phone;
        }

        $digits = (string) preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return $phone;
        }

        if (str_starts_with($digits, '44') && strlen($digits) >= 12) {
            $national = '0' . substr($digits, 2);
            $formatted = self::formatUkNationalNumber($national);

            return $formatted !== '' ? $formatted : $phone;
        }

        if (str_starts_with($digits, '0')) {
            $formatted = self::formatUkNationalNumber($digits);

            return $formatted !== '' ? $formatted : $phone;
        }

        return $phone;
    }

    private static function formatUkNationalNumber(string $digits): string
    {
        if (strlen($digits) === 11 && str_starts_with($digits, '01')) {
            return substr($digits, 0, 5) . ' ' . substr($digits, 5, 3) . ' ' . substr($digits, 8);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '07')) {
            return substr($digits, 0, 5) . ' ' . substr($digits, 5);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '02')) {
            return substr($digits, 0, 4) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7);
        }

        return '';
    }

    public static function sideloadLogo(string $url, string $slug): int
    {
        $url = trim($url);
        if ($url === '') {
            return 0;
        }

        EatDrinkDirectoryPopulate::loadDependencies();

        return EatDrinkDirectoryPopulate::sideloadFromUrlPublic($url, sanitize_title($slug) . '-live-logo');
    }

    private static function plainText(string $html): string
    {
        $text = wp_strip_all_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    private static function extractPhone(string $html): string
    {
        if (
            preg_match(
                '#Store Contact Number[^<]*<a href="tel:([^"]+)"[^>]*>([^<]*)</a>#is',
                $html,
                $phoneMatch
            )
        ) {
            return self::plainText($phoneMatch[2] !== '' ? $phoneMatch[2] : $phoneMatch[1]);
        }

        if (preg_match('#href="tel:([^"]+)"#i', $html, $telMatch)) {
            return self::plainText($telMatch[1]);
        }

        return '';
    }

    private static function contentChunk(string $html): string
    {
        $chunk = '';

        if (
            preg_match(
                '#<h2[^>]*>[^<]+</h2>\s*<h3[^>]*>Store Contact Number:.*?</h3>#is',
                $html,
                $headerMatch,
                PREG_OFFSET_CAPTURE
            )
        ) {
            $start = $headerMatch[0][1] + strlen($headerMatch[0][0]);
            $chunk = substr($html, $start, 35000) ?: '';
        } elseif (
            preg_match(
                '#<h2[^>]*>[^<]+</h2>#is',
                $html,
                $headerMatch,
                PREG_OFFSET_CAPTURE
            )
        ) {
            $start = $headerMatch[0][1] + strlen($headerMatch[0][0]);
            $chunk = substr($html, $start, 35000) ?: '';
        }

        foreach (
            [
                '<footer',
                'class="related',
                'retailer_container opening',
                'retailer_share',
                'Get In Touch',
                'Opening Hours',
                'Opening hours',
                'Share with a friend',
                "<h2",
                'class="offers',
            ] as $stop
        ) {
            $pos = stripos($chunk, $stop);
            if ($pos !== false && $pos > 80) {
                $chunk = substr($chunk, 0, $pos);
                break;
            }
        }

        return $chunk;
    }

    private static function isBoilerplateLine(string $text): bool
    {
        $lower = strtolower($text);

        return str_contains($lower, 'culver square shopping centre')
            || str_contains($lower, 'shopping centre information')
            || str_contains($lower, 'cookie policy')
            || str_contains($lower, 'email address');
    }

    private static function fetchHtml(string $url): string
    {
        $response = wp_remote_get($url, [
            'timeout' => 25,
            'user-agent' => 'CulversTheme/1.0 (venue-live-sync)',
        ]);

        if (is_wp_error($response)) {
            return '';
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        return $code >= 200 && $code < 300 ? (string) wp_remote_retrieve_body($response) : '';
    }
}
