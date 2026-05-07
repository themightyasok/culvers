<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Optional defaults merged only for keys missing from ACF (`$component + defaults`).
 */
final class ComponentDefaults
{
    /**
     * @param string $layout acf_fc_layout
     * @return array<string, mixed>
     */
    public static function get(string $layout): array
    {
        /** @var array<string, array<string, mixed>> $defaults */
        $defaults = [
            // Add per-layout stubs as you create components, e.g. 'hero' => ['intro_align' => 'left'],
        ];

        return $defaults[$layout] ?? [];
    }
}
