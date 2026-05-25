<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Runtime sanitizer for Eat & Drink single flexible layouts (drops legacy tail rows).
 */
final class EatDrinkSingleFlexiblePopulate
{
    /** @var list<string> */
    private const LEGACY_TAIL_LAYOUTS = ['section_header', 'three_card_block'];

    public static function registerAcfLoadSanitizer(): void
    {
        add_filter('acf/load_value/name=components', [self::class, 'filterLoadComponents'], 25, 3);
    }

    /**
     * @param  mixed  $value
     * @param  string|int|false  $postId
     * @param  array<string, mixed>  $field
     * @return mixed
     */
    public static function filterLoadComponents($value, $postId, array $field)
    {
        unset($field);

        if (! is_numeric($postId)) {
            return $value;
        }

        $pid = (int) $postId;
        if (get_post_type($pid) !== 'culvers_eat_drink' || ! is_array($value)) {
            return $value;
        }

        return self::normalizeComponentsForDisplay($value);
    }

    /**
     * @param  list<array<string, mixed>>  $components
     * @return list<array<string, mixed>>
     */
    public static function normalizeComponentsForDisplay(array $components): array
    {
        $archive = function_exists('get_post_type_archive_link')
            ? (string) get_post_type_archive_link('culvers_eat_drink')
            : '/eat-drink/';

        $normalized = [];
        $hasRelated = false;

        foreach ($components as $row) {
            $layout = (string) ($row['acf_fc_layout'] ?? '');
            if (in_array($layout, self::LEGACY_TAIL_LAYOUTS, true)) {
                continue;
            }

            if ($layout === 'shop_related_eat_drink') {
                if ($hasRelated) {
                    continue;
                }
                $hasRelated = true;
                $row['eat_drink_related_heading'] = __('More flavours to discover', 'culvers');
                $row['eat_drink_related_view_all_url'] = $archive;
                $row['eat_drink_related_view_all_label'] = __('View all', 'culvers');
            }

            $normalized[] = $row;
        }

        return $normalized;
    }
}
