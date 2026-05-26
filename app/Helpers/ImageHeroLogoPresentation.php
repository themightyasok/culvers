<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Hero logo display hints for {@see resources/views/components/image-hero.blade.php}.
 *
 * Wide wordmarks use the default `.image-hero__logo` caps. Emblem / stacked marks
 * (roughly square aspect) need taller max-height so they read at hero scale.
 */
final class ImageHeroLogoPresentation
{
    /** Lockups taller than this height÷width ratio use emblem sizing. */
    private const EMBLEM_MIN_ASPECT = 0.55;

    /**
     * @param array<string, mixed>|null $logo ACF / attachment array passed to {@see Image::render}
     */
    public static function isEmblemLockup(?int $postId, ?array $logo): bool
    {
        $width = self::positiveInt($logo['width'] ?? null);
        $height = self::positiveInt($logo['height'] ?? null);

        if ($width > 0 && $height > 0 && ($height / $width) >= self::EMBLEM_MIN_ASPECT) {
            return true;
        }

        if ($postId !== null && $postId > 0) {
            $slug = get_post_field('post_name', $postId);

            return is_string($slug) && $slug === 'cosmic-tattoo';
        }

        return false;
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
