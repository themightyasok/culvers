<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * `<img>` rendering helper for ACF image fields.
 *
 * Centralises width/height/loading/decoding/fetchpriority defaults so every
 * component renders images through one consistent code path. Outputs HTML
 * already escaped at the boundary (do not double-escape).
 */
final class Image
{
    /**
     * RAW attribute values (escape at the boundary, see {@see self::render()}).
     *
     * Recognised `$args` keys (all optional):
     *   - `class`: string CSS classes for the `<img>`.
     *   - `lazy`: bool. Default `true`. Convenience for `loading=lazy decoding=async`.
     *   - `loading`: 'eager'|'lazy'|null. Overrides `lazy`.
     *   - `decoding`: 'async'|'sync'|'auto'|null. Overrides the `lazy` default.
     *   - `fetchpriority`: 'high'|'low'|'auto'|null.
     *   - `alt`: string|null. Overrides the ACF alt (use `''` for decorative).
     *   - `role`: string|null. e.g. `'presentation'` for decorative images.
     *   - `width` / `height`: int|null. Overrides the ACF width/height (e.g. fixed grid card).
     *   - `data`: array<string, string|int|bool> rendered as `data-*` attributes.
     *
     * @param array<string, mixed>|null $image
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public static function getAttributes(?array $image, array $args = []): array
    {
        if ($image === null) {
            return [];
        }

        $sanitized = Sanitizer::image($image);
        if ($sanitized === null) {
            return [];
        }

        $attributes = [
            'src' => Cast::toString($sanitized['url'] ?? ''),
            'alt' => array_key_exists('alt', $args)
                ? Cast::toString($args['alt'])
                : Cast::toString($sanitized['alt'] ?? ''),
        ];

        $class = Cast::toString($args['class'] ?? '');
        if ($class !== '') {
            $attributes['class'] = $class;
        }

        if (isset($args['role']) && is_string($args['role']) && $args['role'] !== '') {
            $attributes['role'] = $args['role'];
        }

        // Skip width/height when zero or missing — typical for SVGs (WordPress
        // can't extract intrinsic dimensions from the viewBox so reports 0×0).
        // Emitting `width="0" height="0"` would collapse the rendered img.
        $width = $args['width'] ?? $sanitized['width'] ?? null;
        $widthInt = $width !== null && $width !== '' ? absint(Cast::toString($width)) : 0;
        if ($widthInt > 0) {
            $attributes['width'] = $widthInt;
        }

        $height = $args['height'] ?? $sanitized['height'] ?? null;
        $heightInt = $height !== null && $height !== '' ? absint(Cast::toString($height)) : 0;
        if ($heightInt > 0) {
            $attributes['height'] = $heightInt;
        }

        $lazy = (bool) ($args['lazy'] ?? true);
        $loading = $args['loading'] ?? ($lazy ? 'lazy' : null);
        if (in_array($loading, ['eager', 'lazy'], true)) {
            $attributes['loading'] = $loading;
        }

        $decoding = $args['decoding'] ?? ($lazy ? 'async' : null);
        if (in_array($decoding, ['async', 'sync', 'auto'], true)) {
            $attributes['decoding'] = $decoding;
        }

        if (isset($args['fetchpriority']) && in_array($args['fetchpriority'], ['high', 'low', 'auto'], true)) {
            $attributes['fetchpriority'] = $args['fetchpriority'];
        }

        if (isset($args['data']) && is_array($args['data'])) {
            foreach ($args['data'] as $key => $value) {
                if (! is_string($key) || $key === '') {
                    continue;
                }

                $attributes['data-' . $key] = is_bool($value) ? ($value ? '1' : '0') : Cast::toString($value);
            }
        }

        return $attributes;
    }

    /**
     * @param array<string, mixed>|null $image
     * @param array<string, mixed> $args
     */
    public static function render(?array $image, array $args = []): string
    {
        $attributes = self::getAttributes($image, $args);

        if ($attributes === []) {
            return '';
        }

        $html = '<img';
        foreach ($attributes as $key => $value) {
            // `src` is escaped as a URL, everything else as a generic attribute. Avoids the
            // historical double-escape (esc_url + esc_attr) that mangled `&` and quoted alt text.
            $escaped = $key === 'src'
                ? esc_url(Cast::toString($value))
                : esc_attr(Cast::toString($value));
            $html .= ' ' . $key . '="' . $escaped . '"';
        }
        $html .= '>';

        return $html;
    }
}
