<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * How {@see image_hero} resolves a brand lockup when the row logo field is empty.
 */
final class ImageHeroLogoSource
{
    /** Use only the hero row's uploaded logo field. */
    public const UPLOADED = 'uploaded';

    /** Pull from the directory listing logo (shop / eat & drink / career fields). */
    public const DIRECTORY_LOGO = 'directory_logo';

    /** Use the post featured image. */
    public const FEATURED = 'featured';

    /** Never auto-fill a logo. */
    public const NONE = 'none';

    /**
     * @param array<string, mixed> $row
     */
    public static function resolve(array $row): string
    {
        $raw = is_string($row['hero_logo_source'] ?? null)
            ? trim($row['hero_logo_source'])
            : '';

        return match ($raw) {
            self::DIRECTORY_LOGO, self::FEATURED, self::NONE => $raw,
            default => self::UPLOADED,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return [
            self::UPLOADED => __('Uploaded logo only', 'culvers'),
            self::DIRECTORY_LOGO => __('Directory listing logo', 'culvers'),
            self::FEATURED => __('Featured image', 'culvers'),
            self::NONE => __('None — do not auto-fill', 'culvers'),
        ];
    }
}
