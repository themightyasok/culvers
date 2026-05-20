<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Share URLs for the social_share flexible layout (Figma 51:6411–51:6425).
 */
final class SocialShare
{
    public static function whatsappShareUrl(string $pageUrl, string $title): string
    {
        $pageUrl = trim($pageUrl);
        $title = trim($title);
        $text = $title !== '' ? $title . ' — ' . $pageUrl : $pageUrl;

        return 'https://wa.me/?text=' . rawurlencode($text);
    }

    public static function isRenderableUrl(string $url): bool
    {
        $url = trim($url);

        return $url !== '' && $url !== '#';
    }
}
