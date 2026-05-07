<?php

declare(strict_types=1);

namespace App\Customizer;

/**
 * Static sanitisers reused by Customizer settings.
 *
 * WordPress passes Customizer values as `mixed` (post data, JSON, defaults).
 * These helpers narrow that mixed value down to a known scalar, then defer to
 * the matching `sanitize_*` core function. Used directly as `sanitize_callback`
 * via {@see self::text(...)} / {@see self::textarea(...)} / {@see self::url(...)}.
 */
final class Sanitize
{
    /**
     * Coerce a Customizer value to a string before any further processing.
     */
    public static function toString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    /** Single-line text sanitiser callback. */
    public static function text(mixed $value): string
    {
        return sanitize_text_field(self::toString($value));
    }

    /** Multi-line textarea sanitiser callback. */
    public static function textarea(mixed $value): string
    {
        return sanitize_textarea_field(self::toString($value));
    }

    /** URL sanitiser callback. Empty input passes through as `''`. */
    public static function url(mixed $value): string
    {
        $string = self::toString($value);

        return $string === '' ? '' : esc_url_raw($string);
    }
}
