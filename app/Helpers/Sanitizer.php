<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Sanitisers for component-shaped data (WYSIWYG, text, colours, image arrays, full components).
 */
final class Sanitizer
{
    /**
     * Sanitize WYSIWYG content (allows safe HTML)
     *
     * @param string $content The content to sanitize
     * @return string Sanitized content
     */
    public static function wysiwyg(string $content): string
    {
        $allowed = wp_kses_allowed_html('post');
        $allowed['small'] = array_merge($allowed['small'] ?? [], ['data-font' => true]);
        $allowed['br'] = $allowed['br'] ?? [];
        $allowed['em'] = array_merge($allowed['em'] ?? [], ['data-font' => true]);

        return (string) wp_kses($content, $allowed);
    }

    /**
     * Sanitize text content (strips HTML)
     *
     * @param string $content The content to sanitize
     * @return string Sanitized content
     */
    public static function text(string $content): string
    {
        return sanitize_text_field($content);
    }

    /**
     * Sanitize textarea content (preserves line breaks)
     *
     * @param string $content The content to sanitize
     * @return string Sanitized content
     */
    public static function textarea(string $content): string
    {
        return sanitize_textarea_field($content);
    }

    /**
     * Sanitize URL
     *
     * @param string $url The URL to sanitize
     * @return string Sanitized URL
     */
    public static function url(string $url): string
    {
        return esc_url_raw($url);
    }

    /**
     * Sanitize color value (hex, rgb, rgba)
     *
     * @param string $color The color to sanitize
     * @return string Sanitized color or empty string
     */
    public static function color(string $color): string
    {
        // Remove whitespace
        $color = trim($color);

        // Validate hex color
        if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return $color;
        }

        // Validate rgb/rgba - more strict validation
        $rgbaPattern = '/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*([\d\.]+))?\s*\)$/';
        if (preg_match($rgbaPattern, $color, $matches)) {
            // Validate RGB values are 0-255
            $r = (int)$matches[1];
            $g = (int)$matches[2];
            $b = (int)$matches[3];
            if ($r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255) {
                // Validate alpha if present (0-1)
                if (isset($matches[4])) {
                    $alpha = (float)$matches[4];
                    if ($alpha >= 0 && $alpha <= 1) {
                        return $color;
                    }
                } else {
                    return $color;
                }
            }
        }

        // Validate named colors (basic set)
        $namedColors = ['transparent', 'inherit', 'initial', 'unset'];
        if (in_array(strtolower($color), $namedColors, true)) {
            return $color;
        }

        return '';
    }

    /**
     * Sanitize image array from ACF.
     *
     * @param array<string, mixed>|null $image Image array from ACF
     * @return array<string, mixed>|null Sanitized image array or null
     */
    public static function image(?array $image): ?array
    {
        if ($image === null) {
            return null;
        }

        $sanitized = [];

        if (isset($image['url'])) {
            $sanitized['url'] = self::url($image['url']);
        }

        if (isset($image['alt'])) {
            $sanitized['alt'] = self::text($image['alt']);
        }

        if (isset($image['title'])) {
            $sanitized['title'] = self::text($image['title']);
        }

        if (isset($image['width'])) {
            $sanitized['width'] = absint($image['width']);
        }

        if (isset($image['height'])) {
            $sanitized['height'] = absint($image['height']);
        }

        if (isset($image['mime_type'])) {
            $sanitized['mime_type'] = self::text($image['mime_type']);
        }

        return ! empty($sanitized) ? $sanitized : null;
    }

    /**
     * Sanitize component data array.
     *
     * @param array<string, mixed> $component Component data array
     * @return array<string, mixed> Sanitized component data
     */
    public static function component(array $component): array
    {
        $sanitized = [];

        foreach ($component as $key => $value) {
            $key = (string) $key;
            // Skip internal keys
            if (str_starts_with($key, '_')) {
                $sanitized[$key] = $value;
                continue;
            }

            // ACF select with return_format "array": [ 'value' => 'text-xl', 'label' => '…' ] — templates expect the value string.
            if (
                is_array($value)
                && array_key_exists('value', $value)
                && array_key_exists('label', $value)
                && !isset($value['url'])
                && !isset($value['ID'])
                && !isset($value['target'])
                && !isset($value['red'])
            ) {
                $inner = $value['value'];
                if ($inner === null || $inner === '') {
                    $value = '';
                } elseif (is_string($inner) || is_int($inner) || is_float($inner)) {
                    $value = (string) $inner;
                }
            }

            if (is_string($value)) {
                // Tailwind tokens from ACF: do not run sanitize_text_field (can alter valid classes).
                if (str_ends_with($key, '_size') || str_ends_with($key, '_weight')) {
                    $sanitized[$key] = trim($value);
                    continue;
                }
                // ACF range / spacing fields: keep numeric strings intact (do not strip).
                if (str_ends_with($key, '_spacing') && is_numeric(trim($value))) {
                    $sanitized[$key] = (int) round((float) trim($value));
                    continue;
                }
                // Determine sanitization based on key
                if (in_array($key, ['url', 'link', 'href'], true)) {
                    $sanitized[$key] = self::url($value);
                } elseif (
                    $key === 'background_color' ||
                    $key === 'background_overlay' ||
                    $key === 'background_gradient_color_from' ||
                    $key === 'background_gradient_color_to'
                ) {
                    $sanitized[$key] = self::color($value);
                } elseif ($key === 'body_text_tone') {
                    $sanitized[$key] = TailwindColors::sanitizeBodyTextTone($value);
                } elseif (
                    in_array($key, [
                        // Component subject-prefixed wysiwyg/rich-text keys (one rule per layout).
                        'content_body',
                        'info_body',
                        'hours_body',
                        'cards_body',
                        'intro_body',
                        'split_body',
                        'scroller_header_text', 'scroller_subheading_text', 'scroller_body_text',
                        'details_address',
                        // Repeater sub-fields & misc rich-text holders kept across components.
                        'hero_subtitle_line',
                        'item_body', 'card_body',
                        'item_answer', 'tab_body',
                    ], true)
                ) {
                    $sanitized[$key] = self::wysiwyg($value);
                } else {
                    $sanitized[$key] = self::text($value);
                }
            } elseif (is_array($value)) {
                // Handle nested arrays (images, etc.)
                if (isset($value['url'])) {
                    $sanitized[$key] = self::image($value);
                } elseif (
                    ($key === 'background_color' || $key === 'background_overlay' ||
                     $key === 'background_gradient_color_from' || $key === 'background_gradient_color_to') &&
                    isset($value['red'], $value['green'], $value['blue'])
                ) {
                    // ACF color picker with transparency returns RGBA array
                    $a = isset($value['alpha']) ? (float) $value['alpha'] : 1;
                    $colorStr = sprintf('rgba(%d,%d,%d,%s)', (int) $value['red'], (int) $value['green'], (int) $value['blue'], $a);
                    $sanitized[$key] = self::color($colorStr);
                } else {
                    $sanitized[$key] = self::component($value);
                }
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
