<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Theme-bundled favicon and default social share image (`resources/images/`).
 */
final class SiteBranding
{
    private const FAVICON_REL = 'resources/images/Culver_Square_Favicon.jpg';

    private const SHARE_IMAGE_REL = 'resources/images/Culver_Square_Share_Image.jpg';

    private const SHARE_WIDTH = 1200;

    private const SHARE_HEIGHT = 630;

    public static function register(): void
    {
        add_action('wp_head', [self::class, 'renderHeadMeta'], 1);
    }

    public static function faviconUri(): string
    {
        return get_template_directory_uri() . '/' . self::FAVICON_REL;
    }

    public static function shareImageUri(): string
    {
        return get_template_directory_uri() . '/' . self::SHARE_IMAGE_REL;
    }

    public static function renderHeadMeta(): void
    {
        if (is_admin()) {
            return;
        }

        self::renderFaviconTags();
        self::renderShareImageTags();
    }

    private static function renderFaviconTags(): void
    {
        $abs = get_template_directory() . '/' . self::FAVICON_REL;
        if (! is_readable($abs)) {
            return;
        }

        $favicon = esc_url(self::faviconUri());
        echo '<link rel="icon" href="' . $favicon . '" sizes="32x32">' . "\n";
        echo '<link rel="shortcut icon" href="' . $favicon . '">' . "\n";
    }

    private static function renderShareImageTags(): void
    {
        $share = self::resolveShareImage();
        if ($share === null) {
            return;
        }

        $url = esc_url($share['url']);
        echo '<meta property="og:image" content="' . $url . '">' . "\n";
        echo '<meta property="og:image:width" content="' . esc_attr((string) $share['width']) . '">' . "\n";
        echo '<meta property="og:image:height" content="' . esc_attr((string) $share['height']) . '">' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:image" content="' . $url . '">' . "\n";
    }

    /** @return array{url: string, width: int, height: int}|null */
    private static function resolveShareImage(): ?array
    {
        if (is_singular() && has_post_thumbnail()) {
            $src = wp_get_attachment_image_src((int) get_post_thumbnail_id(), 'full');
            if (is_array($src) && $src[0] !== '') {
                return [
                    'url' => (string) $src[0],
                    'width' => (int) $src[1],
                    'height' => (int) $src[2],
                ];
            }
        }

        $abs = get_template_directory() . '/' . self::SHARE_IMAGE_REL;
        if (! is_readable($abs)) {
            return null;
        }

        return [
            'url' => self::shareImageUri(),
            'width' => self::SHARE_WIDTH,
            'height' => self::SHARE_HEIGHT,
        ];
    }
}
