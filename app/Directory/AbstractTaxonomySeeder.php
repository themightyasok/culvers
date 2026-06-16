<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Idempotent "seed once" wrapper for the directory taxonomy seeders.
 *
 * Every directory CPT seeds default terms on first activation so the CMS
 * isn't empty on `init`. This base class collapses the option-key gate
 * (`get_option` → seed → `update_option`) shared by every concrete seeder;
 * subclasses just declare their option key and how to perform the seed
 * (see {@see AbstractFlatListTaxonomySeeder} for flat-name variants).
 */
abstract class AbstractTaxonomySeeder
{
    public static function maybeSeed(): void
    {
        if ((bool) get_option(static::optionKey(), false)) {
            return;
        }

        static::performSeed();

        update_option(static::optionKey(), '1', true);
    }

    abstract protected static function optionKey(): string;

    abstract protected static function performSeed(): void;
}
