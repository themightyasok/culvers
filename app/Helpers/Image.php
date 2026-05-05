<?php

declare(strict_types=1);

namespace App\Helpers;

class Image
{
    /** @param array<string, mixed> $args */
    public static function getAttributes(?array $image, array $args = []): array
    {
        if (! $image || ! is_array($image)) {
            return [];
        }

        $sanitized = Sanitizer::image($image);
        if (! $sanitized) {
            return [];
        }

        $lazy = $args['lazy'] ?? true;
        $class = $args['class'] ?? '';

        $attributes = [
            'src' => esc_url($sanitized['url']),
            'alt' => esc_attr($sanitized['alt'] ?? ''),
            'class' => esc_attr($class),
        ];

        if (isset($sanitized['width'])) {
            $attributes['width'] = absint($sanitized['width']);
        }

        if (isset($sanitized['height'])) {
            $attributes['height'] = absint($sanitized['height']);
        }

        if ($lazy) {
            $attributes['loading'] = 'lazy';
            $attributes['decoding'] = 'async';
        }

        return $attributes;
    }

    /** @param array<string, mixed> $args */
    public static function render(?array $image, array $args = []): string
    {
        $attributes = self::getAttributes($image, $args);

        if ($attributes === []) {
            return '';
        }

        $html = '<img';
        foreach ($attributes as $key => $value) {
            $html .= ' ' . esc_attr((string) $key) . '="' . esc_attr((string) $value) . '"';
        }
        $html .= '>';

        return $html;
    }
}
