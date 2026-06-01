<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Config\ComponentPostTypes;

/**
 * Load flexible `components` rows for the current post-type field group.
 *
 * Existing content was saved under {@see ComponentPostTypes::LEGACY_FLEXIBLE_FIELD_KEY}
 * before per–post-type groups were introduced; {@see setup.php} remaps stale `_components`
 * references at read time so {@see get_field()} and {@see self::getRows()} stay aligned.
 */
final class FlexibleComponents
{
    /**
     * Remap a stored `_components` field-key reference to the post-type-specific group.
     */
    public static function resolveStoredFieldReference(int $postId, mixed $storedReference): ?string
    {
        $postType = get_post_type($postId);
        if (! is_string($postType) || $postType === '') {
            return is_string($storedReference) && $storedReference !== '' ? $storedReference : null;
        }

        $expected = ComponentPostTypes::flexibleFieldKeyForPostType($postType);
        if ($expected === null) {
            return is_string($storedReference) && $storedReference !== '' ? $storedReference : null;
        }

        if (
            ! is_string($storedReference)
            || $storedReference === ''
            || $storedReference === ComponentPostTypes::LEGACY_FLEXIBLE_FIELD_KEY
        ) {
            return $expected;
        }

        return $storedReference;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function getRows(int $postId): array
    {
        if ($postId <= 0 || ! function_exists('acf_get_field') || ! function_exists('acf_get_value')) {
            return [];
        }

        $fieldKey = self::resolveStoredFieldReference($postId, get_post_meta($postId, '_components', true));
        if ($fieldKey === null) {
            return [];
        }

        $field = acf_get_field($fieldKey);
        if (! is_array($field)) {
            return [];
        }

        $raw = acf_get_value($postId, $field);
        $rows = function_exists('acf_format_value')
            ? acf_format_value($raw, $postId, $field)
            : $raw;

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter(
            $rows,
            static fn ($row): bool => is_array($row) && empty($row['acf_fc_layout_disabled'])
        ));
    }

    public static function hasRows(int $postId): bool
    {
        return self::getRows($postId) !== [];
    }
}
