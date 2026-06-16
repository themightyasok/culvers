<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Seeder for taxonomies that just need a flat list of editor-facing term names
 * (no slug pinning, no purge-on-resync). Used by Event categories and Career
 * departments — both are editorial taxonomies the client can rename freely;
 * the seeded names are starting points, not contracts.
 *
 * Subclasses provide the taxonomy slug and the default term names; this class
 * does the existence-check and `wp_insert_term` loop.
 */
abstract class AbstractFlatListTaxonomySeeder extends AbstractTaxonomySeeder
{
    abstract protected static function taxonomy(): string;

    /**
     * @return list<string>
     */
    abstract protected static function termNames(): array;

    protected static function performSeed(): void
    {
        $taxonomy = static::taxonomy();
        foreach (static::termNames() as $name) {
            if ($name === '' || term_exists($name, $taxonomy)) {
                continue;
            }
            wp_insert_term($name, $taxonomy);
        }
    }
}
