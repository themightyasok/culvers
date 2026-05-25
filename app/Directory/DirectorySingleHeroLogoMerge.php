<?php

declare(strict_types=1);

namespace App\Directory;

use App\Helpers\ImageHeroLogoSource;

/**
 * Injects listing/CPT logo fields into the first `image_hero` flexible row
 * when the hero has no explicit logo yet and {@see ImageHeroLogoSource} allows it.
 *
 * Editors can still override via the hero row Logo field — we only merge when
 * `hero_logo` resolves empty and `hero_title_in_image` is unset.
 *
 * Runs after {@see DirectoryFlexibleDefaults}. Must hook `acf/format_value` — flexible
 * content rehydrates subfields after `load_value`, so a `load_value`-only merge never
 * reaches `get_field()` / the front end.
 */
final class DirectorySingleHeroLogoMerge
{
    public static function register(): void
    {
        add_filter('acf/format_value/name=components', [self::class, 'mergeIntoFirstHero'], 20, 3);
    }

    /**
     * @param  mixed               $value
     * @param  string|int|false    $postId
     * @param  array<string,mixed> $field
     * @return mixed
     */
    public static function mergeIntoFirstHero($value, $postId, array $field)
    {
        unset($field);

        if (! is_array($value) || ! is_numeric($postId)) {
            return $value;
        }

        $pid = (int) $postId;
        $postType = get_post_type($pid);
        if ($postType === false) {
            return $value;
        }

        foreach ($value as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            if (($row['acf_fc_layout'] ?? '') !== 'image_hero') {
                continue;
            }

            if (! empty($row['hero_title_in_image'])) {
                return $value;
            }

            if (self::rowHasUsableHeroLogo($row)) {
                return $value;
            }

            $source = ImageHeroLogoSource::resolve($row);
            if ($source === ImageHeroLogoSource::NONE || $source === ImageHeroLogoSource::UPLOADED) {
                return $value;
            }

            $logoArr = self::logoImageArrayForSource($pid, $postType, $source);
            if ($logoArr === null) {
                return $value;
            }

            $row['hero_logo'] = $logoArr;
            $row['hero_title_line'] = '';
            $value[$i] = $row;

            break;
        }

        return $value;
    }

    /**
     * @param array<string,mixed> $row
     */
    private static function rowHasUsableHeroLogo(array $row): bool
    {
        $logo = $row['hero_logo'] ?? null;
        if (is_numeric($logo) && (int) $logo > 0) {
            return true;
        }
        if (! is_array($logo)) {
            return false;
        }
        if ((int) ($logo['ID'] ?? $logo['id'] ?? 0) > 0) {
            return true;
        }

        return trim((string) ($logo['url'] ?? '')) !== '';
    }

    /**
     * @return array<string,mixed>|null  ACF image-array shape for `Image::render`
     */
    private static function logoImageArrayForSource(int $postId, string $postType, string $source): ?array
    {
        if ($source === ImageHeroLogoSource::FEATURED) {
            $thumbId = (int) get_post_thumbnail_id($postId);

            return self::normalizeAcfImageField($thumbId > 0 ? $thumbId : null);
        }

        if ($source !== ImageHeroLogoSource::DIRECTORY_LOGO) {
            return null;
        }

        if (! function_exists('get_field')) {
            return null;
        }

        $raw = match ($postType) {
            'culvers_shop' => get_field('shop_logo', $postId),
            'culvers_eat_drink' => get_field('eat_drink_logo', $postId),
            'culvers_career' => get_field('career_employer_logo', $postId),
            default => null,
        };

        return self::normalizeAcfImageField($raw);
    }

    /**
     * @param  mixed  $field  ACF image (array|int) or attachment ID
     * @return array<string,mixed>|null
     */
    private static function normalizeAcfImageField(mixed $field): ?array
    {
        if ($field === null || $field === false || $field === '') {
            return null;
        }

        if (is_numeric($field)) {
            $id = (int) $field;

            return $id > 0 ? self::attachmentToImageArray($id) : null;
        }

        if (! is_array($field)) {
            return null;
        }

        $url = trim((string) ($field['url'] ?? ''));
        if ($url !== '') {
            return $field;
        }

        $id = (int) ($field['ID'] ?? $field['id'] ?? 0);

        return $id > 0 ? self::attachmentToImageArray($id) : null;
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function attachmentToImageArray(int $attachmentId): ?array
    {
        if ($attachmentId <= 0) {
            return null;
        }

        $src = wp_get_attachment_image_src($attachmentId, 'full');
        if (! is_array($src)) {
            return null;
        }
        $url = (string) $src[0];
        if ($url === '') {
            return null;
        }

        return [
            'ID' => $attachmentId,
            'id' => $attachmentId,
            'url' => $url,
            'width' => (int) $src[1],
            'height' => (int) $src[2],
            'alt' => (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true),
        ];
    }
}
