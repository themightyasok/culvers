<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Resolves shop / venue intro-block CTA label + brand website URL.
 */
final class ShopIntroCta
{
    /**
     * @return array{url: string, label: string}|null
     */
    public static function resolve(
        string $localSlug,
        ?string $liveSlug = null,
        string $brandTitle = ''
    ): ?array {
        $url = '';

        if ($liveSlug !== null && $liveSlug !== '') {
            $page = VenueLiveRetailerPage::fetch($liveSlug);
            if ($page !== null && $page['website'] !== '') {
                $url = self::normalizeUrl($page['website']);
            }
        }

        if ($url === '') {
            $url = self::fallbackUrl($localSlug);
        }

        if ($url === '') {
            return null;
        }

        $title = html_entity_decode(trim($brandTitle), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($title === '') {
            $title = self::titleFromSlug($localSlug);
        }

        return [
            'url' => $url,
            'label' => self::labelForBrand($title),
        ];
    }

    public static function labelForBrand(string $brandTitle): string
    {
        return sprintf(
            /* translators: %s: retailer or venue brand name */
            __('Visit %s online', 'culvers'),
            $brandTitle
        );
    }

    /**
     * @return array<string, string> Local post_name → brand website (when live page has no button).
     */
    public static function fallbackUrls(): array
    {
        return [
            'ecovape' => 'https://www.ecovape.uk/',
            'skechers' => 'https://www.skechers.co.uk/',
        ];
    }

    public static function fallbackUrl(string $localSlug): string
    {
        $catalog = self::fallbackUrls();

        return isset($catalog[$localSlug]) ? self::normalizeUrl($catalog[$localSlug]) : '';
    }

    /**
     * @return array<string, string> local slug → live retailer slug
     */
    public static function localToLiveMap(): array
    {
        return array_flip(ShopLiveIntroCopy::liveToLocalMap());
    }

    public static function liveSlugForLocal(string $localSlug): ?string
    {
        $map = self::localToLiveMap();

        return $map[$localSlug] ?? null;
    }

    private static function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $url = esc_url_raw($url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, 'http://')) {
            $https = 'https://' . substr($url, 7);

            return esc_url_raw($https) ?: $url;
        }

        return $url;
    }

    private static function titleFromSlug(string $slug): string
    {
        $title = str_replace('-', ' ', $slug);

        return ucwords($title);
    }
}
