<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Stable in-page fragment ids for mega-menu deep links.
 */
final class PageSectionAnchor
{
    public const SCROLL_MARGIN_CLASS = 'scroll-mt-32';

    /** @var array<string, string> Normalised menu / heading label → fragment id */
    private const MENU_SLUGS = [
        'getting here' => 'getting-here',
        'centre map' => 'centre-map',
        'opening hours' => 'opening-hours',
        'accessible guide' => 'accessible-guide',
        'accessible access' => 'accessible-guide',
        'about colchester' => 'about-colchester',
        'click & collect' => 'click-collect',
        'click and collect' => 'click-collect',
        'security' => 'security',
        'facilities' => 'facilities',
        "faq's" => 'faqs',
        'faqs' => 'faqs',
        'frequently asked questions' => 'faqs',
    ];

    public static function fromHeading(string $heading): string
    {
        $key = self::canonicalKey($heading);
        if ($key !== '' && isset(self::MENU_SLUGS[$key])) {
            return self::MENU_SLUGS[$key];
        }

        return self::slugify($heading);
    }

    public static function scrollMarginClass(string $extra = ''): string
    {
        return trim(self::SCROLL_MARGIN_CLASS . ($extra !== '' ? ' ' . $extra : ''));
    }

    private static function canonicalKey(string $title): string
    {
        $decoded = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalised = strtr($decoded, [
            "\u{2019}" => "'",
            "\u{2018}" => "'",
            "\u{2013}" => '-',
            "\u{2014}" => '-',
        ]);

        return strtolower(trim((string) preg_replace('/\s+/', ' ', $normalised)));
    }

    private static function slugify(string $heading): string
    {
        $slug = sanitize_title($heading);

        return $slug !== '' ? $slug : 'section';
    }
}
