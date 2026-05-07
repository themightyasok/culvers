<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Shared building blocks reused by every flexible-content component.
 *
 * Every Blade in `resources/views/components/*.blade.php` opens with the same
 * preamble (sanitised `$c`, padding + grid classes, body tone, heading tag).
 * This helper centralises those decisions so the components stay structurally
 * identical and we only have one place to evolve when conventions change.
 */
final class Component
{
    /**
     * Clamp an editor-supplied heading level to a valid HTML tag string.
     *
     * Accepts strings, ints, or anything castable. Falls back to `h{$default}`
     * when the value is empty or out of range.
     */
    public static function headingTag(mixed $level, int $default = 2): string
    {
        $candidate = is_numeric($level) ? (int) $level : $default;
        if ($candidate < 1 || $candidate > 6) {
            $candidate = $default;
        }

        return 'h' . $candidate;
    }

    /**
     * Canonical ACF select choices for a heading-level dropdown.
     *
     * H1 is omitted by default — only one H1 should exist per page (the hero).
     * Pass `allowH1: true` for components that may legitimately host the page H1
     * (e.g. content-section on a long-form policy page).
     *
     * PHP coerces numeric-string array keys (`'2'`) to integers — that is fine
     * because ACF stringifies the resolved value before storing it, but the
     * return type below reflects the actual runtime shape.
     *
     * @return array<int, string>
     */
    public static function headingLevelChoices(bool $allowH1 = false): array
    {
        $choices = [
            2 => __('H2 — section title (default)', 'culvers'),
            3 => __('H3', 'culvers'),
            4 => __('H4', 'culvers'),
            5 => __('H5', 'culvers'),
            6 => __('H6', 'culvers'),
        ];

        if ($allowH1) {
            return [1 => __('H1 — main page title (use once)', 'culvers')] + $choices;
        }

        return $choices;
    }

    /**
     * Drop-in ACF field config for a heading-level select.
     *
     * Used by every component PHP file so the editor surface for picking a
     * heading tag is identical across the CMS.
     *
     * @return array<string, mixed>
     */
    public static function headingLevelField(?string $instructions = null, bool $allowH1 = false, int $default = 2): array
    {
        $instructions ??= __(
            'Use one H1 per page (typically the hero). Other sections should stay H2–H6 for a logical outline.',
            'culvers'
        );

        return [
            'type' => 'select',
            'options' => [
                'label' => __('Heading level', 'culvers'),
                'instructions' => $instructions,
                'choices' => self::headingLevelChoices($allowH1),
                'default_value' => $default,
                'allow_null' => 0,
                'return_format' => 'value',
            ],
        ];
    }

    /**
     * Resolve the body-text tone for a component.
     *
     * `$variant === 'light-band'` defends light backgrounds (intro, store details,
     * related shops) from editor-selected light text by coercing white/zinc/brand
     * to {@see TailwindColors::DEFAULT_LIGHT_BAND_BODY_TEXT_TONE}. Otherwise the
     * value is sanitised through the standard tone allowlist.
     *
     * @param array<string, mixed> $component
     */
    public static function bodyTextTone(array $component, ?string $variant = null): string
    {
        $value = isset($component['body_text_tone']) && is_string($component['body_text_tone'])
            ? $component['body_text_tone']
            : null;

        if ($variant === 'light-band') {
            return TailwindColors::bodyToneForWhiteBackground($value);
        }

        return TailwindColors::sanitizeBodyTextTone($value);
    }

    /**
     * Standard structural classes shared by every component `<section>`.
     *
     * Combines `_grid_classes` (after stripping inset gutters when the component
     * renders its own shell) with the resolved padding utilities. Returned as
     * a single trimmed string ready to drop into `class="{{ esc_attr(...) }}"`.
     *
     * Pass `includePadding: false` for full-bleed components (hero shells, image
     * heroes) that must paint edge-to-edge with no `pt-*` / `pb-*` band — the
     * default `pt-16 pb-16` would otherwise put a moss strip above/below the art.
     *
     * @param array<string, mixed> $component
     */
    public static function rootClasses(array $component, bool $stripGutters = true, bool $includePadding = true): string
    {
        $grid = isset($component['_grid_classes']) && is_string($component['_grid_classes'])
            ? $component['_grid_classes']
            : '';

        if ($stripGutters) {
            $grid = Grid::stripHorizontalInsetPadding($grid);
        }

        if (! $includePadding) {
            return trim($grid);
        }

        $padding = Padding::getClasses($component);

        return trim($grid . ' ' . $padding);
    }
}
