<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Type-safe coercion of `mixed` values to scalar primitives.
 *
 * WordPress, ACF, and Customizer surfaces all hand back `mixed` (option store,
 * meta, JSON, post data). PHP allows `(string) $mixed` at runtime but PHPStan
 * (rightly) flags it because mixed could be an array or non-stringable object.
 *
 * Use these helpers at the WP boundary so the rest of the codebase can rely on
 * narrow types.
 */
final class Cast
{
    /**
     * Coerce a mixed value to a string.
     *
     * Strings pass through; scalars cast; arrays/objects/nulls collapse to `''`.
     */
    public static function toString(mixed $value, string $default = ''): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return $default;
    }

    /**
     * Coerce a mixed value to an int. Non-numeric input collapses to `$default`.
     */
    public static function toInt(mixed $value, int $default = 0): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return $default;
    }

    /**
     * Coerce a mixed value to a float. Non-numeric input collapses to `$default`.
     */
    public static function toFloat(mixed $value, float $default = 0.0): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return $default;
    }

    /**
     * Coerce a mixed value to an array. Non-array input collapses to `[]`.
     *
     * @return array<array-key, mixed>
     */
    public static function toArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
