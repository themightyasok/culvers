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
 * Resolves the footer newsletter background attachment ID using:
 *
 *   1. Per-post override (`footer_newsletter_image` on the singular).
 *   2. Directory default for the CPT (same ACF options screen as the archive hero).
 *   3. Site-wide Customizer image ({@see FooterCustomizer::MOD_NEWSLETTER_IMAGE_ID}).
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

    public static function attachmentIdForCurrentView(): int
    {
        $fallback = (int) get_theme_mod(FooterCustomizer::MOD_NEWSLETTER_IMAGE_ID, 0);

        if (is_singular()) {
            $postId = (int) get_queried_object_id();
            if ($postId <= 0) {
                $postId = (int) get_the_ID();
            }
            if ($postId > 0) {
                $override = self::acfImageFieldToId(self::getSingularField($postId));
                if ($override > 0) {
                    return $override;
                }
            }

            $postType = $postId > 0 ? get_post_type($postId) : get_post_type();
            if ($postType !== false && $postType !== '') {
                $archivePick = self::attachmentIdForArchivePrefix(self::prefixForPostType($postType));
                if ($archivePick > 0) {
                    return $archivePick;
                }
            }

            return $fallback;
        }

        if (is_post_type_archive()) {
            $pt = self::postTypeForPostTypeArchive();
            $archivePick = self::attachmentIdForArchivePrefix(self::prefixForPostType($pt));
            if ($archivePick > 0) {
                return $archivePick;
            }
        }

        return $fallback;
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

    private static function getSingularField(int $postId): mixed
    {
        if (! function_exists('get_field')) {
            return null;
        }

        return get_field(self::SINGULAR_FIELD, $postId);
    }

    public static function prefixForPostType(string $postType): ?string
    {
        return self::POST_TYPE_TO_ARCHIVE_PREFIX[$postType] ?? null;
    }

    private static function attachmentIdForArchivePrefix(?string $prefix): int
    {
        if ($prefix === null || $prefix === '' || ! function_exists('get_field')) {
            return 0;
        }

        $value = get_field($prefix . '_footer_newsletter_image', 'option');

        return self::acfImageFieldToId($value);
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
}
