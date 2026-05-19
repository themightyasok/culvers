<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Builds ordered sidebar filter options for directory archives (Figma order).
 */
final class DirectoryFilterOptions
{
    /**
     * @param list<array{slug: string, name: string}> $figmaOrder
     * @param list<\WP_Term> $terms
     * @return list<array{slug: string, name: string}>
     */
    public static function fromFigmaOrder(array $figmaOrder, array $terms): array
    {
        $bySlug = [];
        foreach ($terms as $term) {
            $bySlug[(string) $term->slug] = $term;
        }

        $options = [];
        foreach ($figmaOrder as $row) {
            $slug = $row['slug'];
            if (! isset($bySlug[$slug])) {
                continue;
            }
            $term = $bySlug[$slug];
            $options[] = [
                'slug' => $slug,
                'name' => (string) $term->name,
            ];
            unset($bySlug[$slug]);
        }

        foreach ($bySlug as $term) {
            $options[] = [
                'slug' => (string) $term->slug,
                'name' => (string) $term->name,
            ];
        }

        return $options;
    }
}
