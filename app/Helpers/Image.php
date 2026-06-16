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
     * Normalise an ACF image field value (attachment ID, array, or empty) for {@see render()}.
     *
     * The returned array preserves the source attachment `ID` when it can be
     * resolved, so callers that need the ID (e.g. footer newsletter background)
     * can read it without rolling their own parser. {@see idFromAcf()} /
     * {@see urlFromAcf()} are thin convenience wrappers for those cases.
     *
     * @return array<string, mixed>|null
     */
    public static function fromAcf(mixed $value): ?array
    {
        if (is_array($value)) {
            $url = isset($value['url']) ? trim((string) $value['url']) : '';
            if ($url === '') {
                $id = (int) ($value['ID'] ?? $value['id'] ?? 0);
                if ($id > 0) {
                    return self::fromAcf($id);
                }

                return null;
            }

            $sanitized = Sanitizer::image($value);
            if ($sanitized === null) {
                return null;
            }

            // Sanitizer::image() drops the `ID` key by design; re-attach it so
            // callers like FooterNewsletterImage can resolve attachment IDs from
            // either a raw int field or a hydrated ACF image array.
            $rawId = (int) ($value['ID'] ?? $value['id'] ?? 0);
            if ($rawId > 0) {
                $sanitized['ID'] = $rawId;
            }

            return $sanitized;
        }

        if (is_numeric($value) && (int) $value > 0) {
            $attachmentId = (int) $value;
            $src = wp_get_attachment_image_src($attachmentId, 'full');
            if (! is_array($src) || $src[0] === '') {
                return null;
            }

            $alt = get_post_meta($attachmentId, '_wp_attachment_image_alt', true);

            $sanitized = Sanitizer::image([
                'url' => $src[0],
                'width' => $src[1],
                'height' => $src[2],
                'alt' => is_string($alt) ? $alt : '',
            ]);
            if ($sanitized === null) {
                return null;
            }

            $sanitized['ID'] = $attachmentId;

            return $sanitized;
        }

        return null;
    }

    /**
     * Resolve the attachment ID from an ACF image field value. Returns 0 if the
     * value cannot be resolved to a real attachment.
     */
    public static function idFromAcf(mixed $value): int
    {
        $image = self::fromAcf($value);
        if ($image === null) {
            return 0;
        }

        return (int) ($image['ID'] ?? 0);
    }

    /**
     * Resolve the public URL for an ACF image field value. Returns an empty
     * string if the value cannot be resolved.
     */
    public static function urlFromAcf(mixed $value): string
    {
        $image = self::fromAcf($value);
        if ($image === null) {
            return '';
        }

        return (string) ($image['url'] ?? '');
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

    /**
     * Desktop + optional mobile cover images (below md shows mobile when set).
     *
     * @param array<string, mixed>|null $desktop
     * @param array<string, mixed>|null $mobile
     * @param array<string, mixed> $args Passed to {@see render()} for both images.
     */
    public static function renderResponsiveCover(?array $desktop, ?array $mobile, array $args = []): string
    {
        $deskUrl = isset($desktop['url']) ? trim((string) $desktop['url']) : '';
        $mobUrl = isset($mobile['url']) ? trim((string) $mobile['url']) : '';

        if ($deskUrl === '' && $mobUrl === '') {
            return '';
        }

        $baseClass = trim((string) ($args['class'] ?? 'absolute inset-0 size-full object-cover'));
        $html = '';

        if ($mobUrl !== '') {
            $mobArgs = $args;
            $mobArgs['class'] = trim($baseClass . ' md:hidden');
            $html .= self::render($mobile, $mobArgs);
        }

        $deskArgs = $args;
        $deskArgs['class'] = $mobUrl !== ''
            ? trim($baseClass . ' max-md:hidden')
            : $baseClass;
        if ($deskUrl !== '') {
            $html .= self::render($desktop, $deskArgs);
        } elseif ($mobUrl !== '') {
            $deskArgs['class'] = $baseClass;
            $html .= self::render($mobile, $deskArgs);
        }

        return $html;
    }
}
