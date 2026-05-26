<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * When false (default), brand logos on moss tiles and image heroes are forced
 * to white via CSS brightness/invert. Some marks ship with their own background
 * (e.g. black lockup + white type) and must render as uploaded.
 */
final class LogoPreserveColors
{
    /** @return array<string, mixed> ACF true/false field definition for AcfBuilder */
    public static function acfFieldDefinition(): array
    {
        return [
            'label' => __('Use logo as uploaded', 'culvers'),
            'instructions' => __(
                'Turn on when the artwork already has its own colours or background ' .
                '(e.g. a black badge with white lettering). The default treatment ' .
                'forces logos to white for dark moss tiles and hero photos.',
                'culvers'
            ),
            'ui' => 1,
            'ui_on_text' => __('Yes', 'culvers'),
            'ui_off_text' => __('White filter', 'culvers'),
            'default_value' => 0,
        ];
    }

    public static function shouldPreserveForPost(int $postId, string $postType): bool
    {
        if (! function_exists('get_field')) {
            return false;
        }

        $fieldName = match ($postType) {
            'culvers_shop' => 'shop_logo_preserve_colors',
            'culvers_eat_drink' => 'eat_drink_logo_preserve_colors',
            'culvers_career' => 'career_employer_logo_preserve_colors',
            default => null,
        };

        if ($fieldName === null) {
            return false;
        }

        return (bool) get_field($fieldName, $postId);
    }
}
