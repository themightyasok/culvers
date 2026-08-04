<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Hero logo display hints for {@see resources/views/components/image-hero.blade.php}.
 *
 * Buckets drive unlayered `.image-hero__logo*` rules in app.css:
 * - wide: long wordmarks (shared target height, generous max-width)
 * - emblem: circular / stacked / squarish marks (much larger square stage so
 *   they match the visual weight of wide wordmarks)
 * - default: medium wordmarks (same height as wide)
 */
final class ImageHeroLogoPresentation
{
    /**
     * Width÷height at or above this → wide wordmark sizing.
     * (e.g. Snow's Collectables ≈ 2.55, Pandora ≈ 4.8)
     */
    private const WIDE_LOCKUP_MIN_RATIO = 2.0;

    /**
     * Width÷height at or below this → emblem staging.
     * Catches circles, stacked lockups, and marks with modest side padding
     * (e.g. Colchester Aesthetics ≈ 1.86).
     */
    private const EMBLEM_MAX_RATIO = 1.9;

    /**
     * @param array<string, mixed>|null $logo ACF / attachment array passed to {@see Image::render}
     */
    public static function isEmblemLockup(?int $postId, ?array $logo): bool
    {
        [$width, $height] = self::dimensions($logo);

        if ($width > 0 && $height > 0) {
            $ratio = $width / $height;

            if ($ratio <= self::EMBLEM_MAX_RATIO) {
                return true;
            }
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
     * Resolve intrinsic size from the ACF array, attachment meta, the on-disk
     * raster, or an SVG viewBox / width / height when WP metadata is 0×0.
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
        if ($id <= 0) {
            return [0, 0];
        }

        $meta = wp_get_attachment_metadata($id);
        if (is_array($meta)) {
            $width = isset($meta['width']) ? self::positiveInt($meta['width']) : 0;
            $height = isset($meta['height']) ? self::positiveInt($meta['height']) : 0;
            if ($width > 0 && $height > 0) {
                return [$width, $height];
            }
        }

        $path = get_attached_file($id);
        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            return [0, 0];
        }

        $size = @getimagesize($path);
        if (is_array($size)) {
            $width = isset($size[0]) ? self::positiveInt($size[0]) : 0;
            $height = isset($size[1]) ? self::positiveInt($size[1]) : 0;
            if ($width > 0 && $height > 0) {
                return [$width, $height];
            }
        }

        $mime = get_post_mime_type($id);
        if (is_string($mime) && str_contains($mime, 'svg')) {
            return self::svgDimensions($path);
        }

        return [0, 0];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function svgDimensions(string $path): array
    {
        $svg = @file_get_contents($path);
        if (! is_string($svg) || $svg === '') {
            return [0, 0];
        }

        if (preg_match('/viewBox\s*=\s*["\']\s*([-\d.]+)\s+([-\d.]+)\s+([-\d.]+)\s+([-\d.]+)\s*["\']/i', $svg, $m) === 1) {
            $width = self::positiveInt((float) $m[3]);
            $height = self::positiveInt((float) $m[4]);
            if ($width > 0 && $height > 0) {
                return [$width, $height];
            }
        }

        $width = 0;
        $height = 0;
        if (preg_match('/\bwidth\s*=\s*["\']\s*([-\d.]+)(px)?\s*["\']/i', $svg, $m) === 1) {
            $width = self::positiveInt((float) $m[1]);
        }
        if (preg_match('/\bheight\s*=\s*["\']\s*([-\d.]+)(px)?\s*["\']/i', $svg, $m) === 1) {
            $height = self::positiveInt((float) $m[1]);
        }

        if ($width > 0 && $height > 0) {
            return [$width, $height];
        }

        return [0, 0];
    }

    private static function positiveInt(mixed $value): int
    {
        if (! is_numeric($value)) {
            return 0;
        }

        $int = (int) round((float) $value);

        return $int > 0 ? $int : 0;
    }
}
