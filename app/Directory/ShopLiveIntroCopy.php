<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Fetches retailer intro copy from culversquare.co.uk and splits it for
 * shop_intro_block + shop_split_highlight ACF rows.
 */
final class ShopLiveIntroCopy
{
    private const SHOPPING_URL = 'https://www.culversquare.co.uk/shopping/';

    /** @var array<string, string> Live retailer slug → local culvers_shop post_name */
    private const LIVE_TO_LOCAL = [
        'accessorize' => 'accessorize-london',
        'ann-summers' => 'ann-summers',
        'colchester-aesthetics-beauty' => 'all-4-u-care',
        'cosmic-tattoo' => 'cosmic-tattoo',
        'eco-vape' => 'ecovape',
        'ernest-jones' => 'ernest-jones',
        'hm' => 'hm',
        'hmv' => 'hmv',
        'hotel-chocolat' => 'hotel-chocolat',
        'istore' => 'istore',
        'love-reform-pilates-studio' => 'love-reform',
        'monsoon' => 'monsoon',
        'nerd-base' => 'menkind',
        'pandora' => 'pandora',
        'schuh' => 'schuh',
        'skechers' => 'skechers',
        'smiggle' => 'smiggle',
        'sostrene-grene' => 'sostrene-grene',
        'the-fragrance-shop' => 'the-fragrance-shop',
        'tiger' => 'flying-tiger-copenhagen',
        'tk-maxx' => 'tk-maxx',
        'topgift' => 'wye-mobility',
    ];

    /**
     * @return array<string, string>
     */
    public static function liveToLocalMap(): array
    {
        return self::LIVE_TO_LOCAL;
    }

    /**
     * @return list<string>
     */
    public static function discoverLiveSlugs(): array
    {
        $html = self::fetch(self::SHOPPING_URL);
        if ($html === '') {
            return [];
        }

        if (! preg_match_all('#href="/retailers/([a-z0-9-]+)/?"#i', $html, $matches)) {
            return [];
        }

        /** @var list<string> $slugs */
        $slugs = array_values(array_unique(array_map('strval', $matches[1])));

        return $slugs;
    }

    /**
     * @return array{title: string, paras: list<string>, lists: list<list<string>>}|null
     */
    public static function fetchRetailerContent(string $liveSlug): ?array
    {
        $page = VenueLiveRetailerPage::fetch($liveSlug);
        if ($page === null) {
            return null;
        }

        return [
            'title' => $page['title'],
            'paras' => $page['paras'],
            'lists' => $page['lists'],
        ];
    }

    /**
     * @param list<string> $paras
     *
     * @return list<string>
     */
    public static function filterPromoLinesPublic(array $paras): array
    {
        return self::filterPromoLines($paras);
    }

    /**
     * @param  list<string>              $paras
     * @param  list<list<string>>        $lists
     * @return array{intro_html: string, split_kicker: string, split_headline: string, split_body_html: string}
     */
    public static function splitForBlocks(string $shopTitle, array $paras, array $lists): array
    {
        if ($paras === []) {
            return [
                'intro_html' => '',
                'split_kicker' => '',
                'split_headline' => sprintf(__('Visit %s', 'culvers'), $shopTitle),
                'split_body_html' => '',
            ];
        }

        if (count($paras) === 1 && strlen($paras[0]) > 380) {
            return self::splitSingleLongParagraph($shopTitle, $paras[0], $lists);
        }

        $introCount = 1;
        if (count($paras) >= 2 && ! self::isHeadlineLike($paras[1])) {
            $introCount = 2;
        }

        $introParas = array_slice($paras, 0, $introCount);
        $rest = array_slice($paras, $introCount);

        $kicker = '';
        $headline = '';
        if ($rest !== [] && self::isHeadlineLike($rest[0])) {
            [$kicker, $headline] = self::parseHeadlineLine($rest[0]);
            $rest = array_slice($rest, 1);
        } else {
            $headline = self::defaultSplitHeadline($shopTitle, $rest);
        }

        return [
            'intro_html' => self::paragraphsToHtml($introParas),
            'split_kicker' => $kicker,
            'split_headline' => $headline,
            'split_body_html' => self::paragraphsToHtml($rest, $lists),
        ];
    }

    /**
     * @param  list<list<string>> $lists
     * @return array{intro_html: string, split_kicker: string, split_headline: string, split_body_html: string}
     */
    private static function splitSingleLongParagraph(string $shopTitle, string $text, array $lists): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($sentences) || $sentences === []) {
            $sentences = [$text];
        }

        $splitAt = min(2, max(1, (int) ceil(count($sentences) / 2)));
        $introSentences = array_slice($sentences, 0, $splitAt);
        $restSentences = array_slice($sentences, $splitAt);

        return [
            'intro_html' => self::paragraphsToHtml([implode(' ', $introSentences)]),
            'split_kicker' => '',
            'split_headline' => self::defaultSplitHeadline($shopTitle, $restSentences !== [] ? [implode(' ', $restSentences)] : []),
            'split_body_html' => $restSentences !== []
                ? self::paragraphsToHtml([implode(' ', $restSentences)], $lists)
                : self::paragraphsToHtml([], $lists),
        ];
    }

    /**
     * @param  list<string> $rest
     */
    private static function defaultSplitHeadline(string $shopTitle, array $rest): string
    {
        if ($rest !== [] && preg_match('/\b(now open|new collection|discover|visit us)\b/i', $rest[0]) === 1) {
            return self::truncateHeadline($rest[0]);
        }

        return sprintf(__('More from %s', 'culvers'), $shopTitle);
    }

    private static function isHeadlineLike(string $text): bool
    {
        $len = strlen($text);
        if ($len > 130) {
            return false;
        }

        if (preg_match('/\b(now open|new in|just landed|book now)\b/i', $text) === 1) {
            return true;
        }

        if (str_ends_with($text, '!') && $len < 95) {
            return true;
        }

        if (preg_match('/\s[–—-]\s/u', $text) === 1 && $len < 100) {
            return true;
        }

        $upperRatio = self::uppercaseLetterRatio($text);

        return $len < 85 && $upperRatio > 0.55;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function parseHeadlineLine(string $line): array
    {
        if (preg_match('/^(.+?)\s[–—-]\s+(.+)$/u', $line, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        if (preg_match('/^(.+?)\s*:\s*(.+)$/u', $line, $m) && strlen($m[1]) < 60) {
            return [trim($m[1]), trim($m[2])];
        }

        return ['', self::truncateHeadline($line)];
    }

    private static function truncateHeadline(string $text): string
    {
        if (strlen($text) <= 72) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, 69)) . '…';
    }

    /**
     * @param  list<string>       $paras
     * @param  list<list<string>> $lists
     */
    private static function paragraphsToHtml(array $paras, array $lists = []): string
    {
        $html = '';
        foreach ($paras as $para) {
            $html .= '<p>' . esc_html($para) . '</p>';
        }
        foreach ($lists as $items) {
            if ($items === []) {
                continue;
            }
            $html .= '<ul>';
            foreach ($items as $item) {
                $html .= '<li>' . esc_html($item) . '</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * @param list<string> $paras
     * @return list<string>
     */
    private static function filterPromoLines(array $paras): array
    {
        return array_values(array_filter(
            $paras,
            static function (string $line): bool {
                if (strlen($line) < 90 && ! str_contains($line, '.')) {
                    if (preg_match('/\b(discount|return shoes|% off|student)\b/i', $line) === 1) {
                        return false;
                    }
                }

                return true;
            }
        ));
    }

    private static function uppercaseLetterRatio(string $text): float
    {
        $letters = preg_replace('/[^A-Za-z]/', '', $text) ?? '';
        if ($letters === '') {
            return 0.0;
        }
        $upper = preg_replace('/[^A-Z]/', '', $letters) ?? '';

        return strlen($upper) / strlen($letters);
    }

    private static function fetch(string $url): string
    {
        $response = wp_remote_get($url, [
            'timeout' => 25,
            'user-agent' => 'CulversTheme/1.0 (intro-copy-sync)',
        ]);

        if (is_wp_error($response)) {
            return '';
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        return $code >= 200 && $code < 300 ? (string) wp_remote_retrieve_body($response) : '';
    }
}
