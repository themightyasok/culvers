<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Resolves portrait card photos for events, offers, and news.
 *
 * Used by {@see \App\Helpers\ThreeCardBlock} and
 * {@see Cards\DirectoryCardSpecFactory} so three-card blocks and archive
 * grids share the same fallback chain:
 *
 *   1. `{post_type}_card_image` listing field (editor override)
 *   2. WordPress featured image
 *   3. First `image_hero` row on the single's flexible `components` stack
 */
final class DirectoryCardImage
{
    /** @var array<string, string> */
    private const CARD_IMAGE_FIELD_BY_POST_TYPE = [
        'culvers_event' => 'event_card_image',
        'culvers_offer' => 'offer_card_image',
        'culvers_news' => 'news_card_image',
    ];

    public static function fieldNameForPostType(string $postType): ?string
    {
        return self::CARD_IMAGE_FIELD_BY_POST_TYPE[$postType] ?? null;
    }

    public static function supportsPostType(string $postType): bool
    {
        return isset(self::CARD_IMAGE_FIELD_BY_POST_TYPE[$postType]);
    }

    /**
     * @return array{url: string, attachment_id: int}
     */
    public static function resolve(int $postId): array
    {
        if ($postId <= 0) {
            return self::empty();
        }

        $postType = get_post_type($postId);
        if (! is_string($postType) || ! self::supportsPostType($postType)) {
            return self::empty();
        }

        $fieldName = self::fieldNameForPostType($postType);
        if ($fieldName !== null && function_exists('get_field')) {
            $fromCardField = self::attachmentFromAcfValue(get_field($fieldName, $postId));
            if ($fromCardField['attachment_id'] > 0) {
                return self::withLargeUrl($fromCardField);
            }
        }

        $featuredId = (int) get_post_thumbnail_id($postId);
        if ($featuredId > 0) {
            return self::withLargeUrl(['url' => '', 'attachment_id' => $featuredId]);
        }

        $heroId = self::heroAttachmentIdFromComponents($postId);
        if ($heroId > 0) {
            return self::withLargeUrl(['url' => '', 'attachment_id' => $heroId]);
        }

        return self::empty();
    }

    /**
     * @return array{url: string, attachment_id: int}
     */
    private static function empty(): array
    {
        return ['url' => '', 'attachment_id' => 0];
    }

    /**
     * @param  array{url: string, attachment_id: int}  $resolved
     * @return array{url: string, attachment_id: int}
     */
    private static function withLargeUrl(array $resolved): array
    {
        if ($resolved['url'] !== '') {
            return $resolved;
        }

        $attachmentId = $resolved['attachment_id'];
        if ($attachmentId <= 0) {
            return self::empty();
        }

        $url = wp_get_attachment_image_url($attachmentId, 'large');

        return [
            'url' => is_string($url) && $url !== '' ? $url : '',
            'attachment_id' => $attachmentId,
        ];
    }

    private static function heroAttachmentIdFromComponents(int $postId): int
    {
        if (! function_exists('get_field')) {
            return 0;
        }

        $components = get_field('components', $postId);
        if (! is_array($components)) {
            return 0;
        }

        foreach ($components as $row) {
            if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'image_hero') {
                continue;
            }

            $fromHero = self::attachmentFromAcfValue($row['hero_image'] ?? null);
            if ($fromHero['attachment_id'] > 0) {
                return $fromHero['attachment_id'];
            }
        }

        return 0;
    }

    /**
     * @return array{url: string, attachment_id: int}
     */
    private static function attachmentFromAcfValue(mixed $value): array
    {
        if (is_numeric($value) && (int) $value > 0) {
            return ['url' => '', 'attachment_id' => (int) $value];
        }

        if (! is_array($value)) {
            return self::empty();
        }

        $id = (int) ($value['ID'] ?? $value['id'] ?? 0);
        if ($id > 0) {
            return ['url' => '', 'attachment_id' => $id];
        }

        $maybeUrl = $value['url'] ?? null;
        if (is_string($maybeUrl) && trim($maybeUrl) !== '') {
            $fromUrl = attachment_url_to_postid(trim($maybeUrl));

            return [
                'url' => trim($maybeUrl),
                'attachment_id' => $fromUrl > 0 ? $fromUrl : 0,
            ];
        }

        return self::empty();
    }
}
