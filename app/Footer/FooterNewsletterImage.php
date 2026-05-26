<?php

declare(strict_types=1);

namespace App\Footer;

use App\Customizer\FooterCustomizer;
use App\Directory\CareerArchiveFields;
use App\Directory\EatDrinkArchiveFields;
use App\Directory\EventArchiveFields;
use App\Directory\NewsArchiveFields;
use App\Directory\OfferArchiveFields;
use App\Directory\ShopArchiveFields;

/**
 * Resolves the footer newsletter background using:
 *
 *   1. Per-post override (`footer_newsletter_image` / `_mobile` on the singular).
 *   2. Directory default for the CPT (same ACF options screen as the archive hero).
 *   3. Site-wide Customizer images ({@see FooterCustomizer}).
 *
 * At each tier the mobile field falls back to that tier’s desktop image; empty tiers
 * defer to the next tier (same as archive hero imagery).
 */
final class FooterNewsletterImage
{
    /** @var array<string, string> */
    private const POST_TYPE_TO_ARCHIVE_PREFIX = [
        'culvers_shop' => ShopArchiveFields::FIELD_PREFIX,
        'culvers_eat_drink' => EatDrinkArchiveFields::FIELD_PREFIX,
        'culvers_event' => EventArchiveFields::FIELD_PREFIX,
        'culvers_offer' => OfferArchiveFields::FIELD_PREFIX,
        'culvers_news' => NewsArchiveFields::FIELD_PREFIX,
        'culvers_career' => CareerArchiveFields::FIELD_PREFIX,
    ];

    public const SINGULAR_FIELD = 'footer_newsletter_image';

    public const SINGULAR_FIELD_MOBILE = 'footer_newsletter_image_mobile';

    public const ARCHIVE_FIELD_SUFFIX = '_footer_newsletter_image';

    public const ARCHIVE_FIELD_MOBILE_SUFFIX = '_footer_newsletter_image_mobile';

    public static function attachmentIdForCurrentView(): int
    {
        return self::attachmentIdsForCurrentView()['desktop'];
    }

    /**
     * @return array{desktop: int, mobile: int}
     */
    public static function attachmentIdsForCurrentView(): array
    {
        $siteDesktop = (int) get_theme_mod(FooterCustomizer::MOD_NEWSLETTER_IMAGE_ID, 0);
        $siteMobile = (int) get_theme_mod(FooterCustomizer::MOD_NEWSLETTER_IMAGE_MOBILE_ID, 0);
        $sitePair = self::normalizePair($siteDesktop, $siteMobile);

        if (is_singular()) {
            $postId = self::queriedPostId();
            if ($postId > 0) {
                $singularPair = self::singularPair($postId);
                if ($singularPair !== null) {
                    return $singularPair;
                }
            }

            $postType = $postId > 0 ? get_post_type($postId) : get_post_type();
            if (is_string($postType) && $postType !== '') {
                $archivePair = self::archivePair(self::prefixForPostType($postType));
                if ($archivePair !== null) {
                    return $archivePair;
                }
            }

            return $sitePair;
        }

        if (is_post_type_archive()) {
            $archivePair = self::archivePair(self::prefixForPostType(self::postTypeForPostTypeArchive()));
            if ($archivePair !== null) {
                return $archivePair;
            }
        }

        return $sitePair;
    }

    /**
     * ACF-shaped image arrays for {@see \App\Helpers\Image::renderResponsiveCover()}.
     *
     * @return array{desktop: array<string, mixed>|null, mobile: array<string, mixed>|null}
     */
    public static function imagesForCurrentView(): array
    {
        $ids = self::attachmentIdsForCurrentView();
        $desktop = self::attachmentIdToImageArray($ids['desktop']);
        $mobile = $ids['mobile'] !== $ids['desktop']
            ? self::attachmentIdToImageArray($ids['mobile'])
            : null;

        return [
            'desktop' => $desktop,
            'mobile' => $mobile,
        ];
    }

    public static function prefixForPostType(string $postType): ?string
    {
        return self::POST_TYPE_TO_ARCHIVE_PREFIX[$postType] ?? null;
    }

    /**
     * `post_type` query var is not always populated on pretty Permalink CPT archives — fall back
     * to the queried {@see \WP_Post_Type} object.
     */
    private static function postTypeForPostTypeArchive(): string
    {
        $pt = get_query_var('post_type');
        if (is_array($pt)) {
            return (string) ($pt[0] ?? '');
        }
        if (is_string($pt) && $pt !== '') {
            return $pt;
        }

        if (function_exists('get_queried_object')) {
            $qo = get_queried_object();
            if ($qo instanceof \WP_Post_Type) {
                return $qo->name;
            }
        }

        return '';
    }

    private static function queriedPostId(): int
    {
        $postId = (int) get_queried_object_id();
        if ($postId <= 0) {
            $postId = (int) get_the_ID();
        }

        return max(0, $postId);
    }

    /**
     * @return array{desktop: int, mobile: int}|null
     */
    private static function singularPair(int $postId): ?array
    {
        if (! function_exists('get_field')) {
            return null;
        }

        $desktop = self::acfImageFieldToId(get_field(self::SINGULAR_FIELD, $postId));
        $mobile = self::acfImageFieldToId(get_field(self::SINGULAR_FIELD_MOBILE, $postId));

        return self::pairOrNull($desktop, $mobile);
    }

    /**
     * @return array{desktop: int, mobile: int}|null
     */
    private static function archivePair(?string $prefix): ?array
    {
        if ($prefix === null || $prefix === '' || ! function_exists('get_field')) {
            return null;
        }

        $desktop = self::acfImageFieldToId(get_field($prefix . self::ARCHIVE_FIELD_SUFFIX, 'option'));
        $mobile = self::acfImageFieldToId(get_field($prefix . self::ARCHIVE_FIELD_MOBILE_SUFFIX, 'option'));

        return self::pairOrNull($desktop, $mobile);
    }

    /**
     * @return array{desktop: int, mobile: int}|null
     */
    private static function pairOrNull(int $desktop, int $mobile): ?array
    {
        if ($desktop <= 0 && $mobile <= 0) {
            return null;
        }

        return self::normalizePair($desktop, $mobile);
    }

    /**
     * @return array{desktop: int, mobile: int}
     */
    private static function normalizePair(int $desktop, int $mobile): array
    {
        if ($desktop <= 0 && $mobile > 0) {
            $desktop = $mobile;
        }

        if ($mobile <= 0 && $desktop > 0) {
            $mobile = $desktop;
        }

        return [
            'desktop' => max(0, $desktop),
            'mobile' => max(0, $mobile),
        ];
    }

    private static function acfImageFieldToId(mixed $value): int
    {
        if (is_int($value)) {
            return max(0, $value);
        }
        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }
        if (is_array($value) && isset($value['ID'])) {
            return (int) $value['ID'];
        }
        if (is_array($value) && isset($value['id'])) {
            return (int) $value['id'];
        }

        return 0;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function attachmentIdToImageArray(int $attachmentId): ?array
    {
        if ($attachmentId <= 0) {
            return null;
        }

        $src = wp_get_attachment_image_src($attachmentId, 'large');
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
