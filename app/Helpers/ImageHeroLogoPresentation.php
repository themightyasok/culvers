<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Hero logo display hints for {@see resources/views/components/image-hero.blade.php}.
 *
 * Wide horizontal wordmarks, square emblems, and default lockups pick size buckets
 * in unlayered `.image-hero__logo*` rules (shared target height — see app.css).
 */
final class ImageHeroLogoPresentation
{
    /** Lockups taller than this height÷width ratio use emblem sizing. */
    private const EMBLEM_MIN_ASPECT = 0.55;

    /** Width÷height above this uses wide wordmark sizing (e.g. Snow's Collectables). */
    private const WIDE_LOCKUP_MIN_RATIO = 2.0;

    /**
     * @param array<string, mixed>|null $logo ACF / attachment array passed to {@see Image::render}
     */
    public static function isEmblemLockup(?int $postId, ?array $logo): bool
    {
        [$width, $height] = self::dimensions($logo);

        if ($width > 0 && $height > 0 && ($height / $width) >= self::EMBLEM_MIN_ASPECT) {
            return true;
        }

        if ($postId !== null && $postId > 0) {
            $slug = get_post_field('post_name', $postId);

            return is_string($slug) && $slug === 'cosmic-tattoo';
        }

        return false;
    }

    /**
     * @param array<string, mixed>|null $logo
     */
    public static function isWideLockup(?array $logo): bool
    {
        [$width, $height] = self::dimensions($logo);

        if ($width <= 0 || $height <= 0) {
            return false;
        }

        return ($width / $height) >= self::WIDE_LOCKUP_MIN_RATIO;
    }

    /**
     * Resolve intrinsic size from the ACF array, attachment meta, or the on-disk file
     * when WP metadata is missing / 0×0 (common after SVG→PNG swaps or broken sideloads).
     *
     * @param array<string, mixed>|null $logo
     * @return array{0: int, 1: int}
     */
    private static function dimensions(?array $logo): array
    {
        if ($logo === null) {
            return [0, 0];
        }

        $width = self::positiveInt($logo['width'] ?? null);
        $height = self::positiveInt($logo['height'] ?? null);
        if ($width > 0 && $height > 0) {
            return [$width, $height];
        }

        $id = self::positiveInt($logo['ID'] ?? $logo['id'] ?? null);
        if ($id > 0) {
            $meta = wp_get_attachment_metadata($id);
            if (is_array($meta)) {
                $width = self::positiveInt($meta['width']);
                $height = self::positiveInt($meta['height']);
                if ($width > 0 && $height > 0) {
                    return [$width, $height];
                }
            }

            $path = get_attached_file($id);
            if (is_string($path) && $path !== '' && is_readable($path)) {
                $size = @getimagesize($path);
                if (is_array($size)) {
                    $width = self::positiveInt($size[0]);
                    $height = self::positiveInt($size[1]);
                    if ($width > 0 && $height > 0) {
                        return [$width, $height];
                    }
                }
            }
        }

        return [0, 0];
    }

    private static function positiveInt(mixed $value): int
    {
        if (! is_numeric($value)) {
            return 0;
        }

        $int = (int) $value;

        return $int > 0 ? $int : 0;
    }
}
