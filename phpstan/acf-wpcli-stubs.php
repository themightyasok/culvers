<?php

declare(strict_types=1);

/**
 * PHPStan stubs for runtime-only WordPress plugins (ACF, WP-CLI).
 *
 * @see phpstan.neon
 */

namespace {
    /**
     * @param  int|string|bool  $post_id
     */
    function get_field(string $selector, mixed $post_id = false, bool $format_value = true): mixed
    {
    }

    /**
     * @param  int|string|bool  $post_id
     */
    function update_field(string $selector, mixed $value, mixed $post_id = false): bool
    {
        return true;
    }

    /**
     * @param  int|string|bool  $post_id
     */
    function delete_field(string $selector, mixed $post_id = false): bool
    {
        return true;
    }

    class WP_CLI
    {
        public static function log(string $message): void
        {
        }

        public static function warning(string $message): void
        {
        }

        public static function success(string $message): void
        {
        }

        public static function error(string $message): void
        {
        }
    }
}
