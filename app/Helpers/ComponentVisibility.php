<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Maps registry visibility toggles to Tailwind utility bundles shipped in app.css
 * (explicit @apply so dynamic class strings are not purged).
 *
 * Two bands only: below `md` vs `md` and up (tablet + desktop share the same band).
 */
final class ComponentVisibility
{
    /**
     * @param array<string, mixed> $component One flexible row after sanitiser merge
     */
    public static function gridUtilityClasses(array $component): string
    {
        $hidePhone = self::hidePhone($component);
        $hideMdUp = ! empty($component['visibility_hide_desktop']);

        return match (($hidePhone ? '1' : '0') . ($hideMdUp ? '1' : '0')) {
            '00' => '',
            '10' => 'culvers-vis--hide-phone',
            '01' => 'culvers-vis--hide-md-up',
            '11' => 'culvers-vis--hide-all',
        };
    }

    /**
     * @param array<string, mixed> $component
     */
    private static function hidePhone(array $component): bool
    {
        if (! empty($component['visibility_hide_phone'])) {
            return true;
        }

        return ($component['visibility_mobile'] ?? 'visible') === 'hidden';
    }
}
