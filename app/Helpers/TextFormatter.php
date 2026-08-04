<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * WYSIWYG / plain text output with optional CMS accent tags.
 *
 * - {@code <highlight>…</highlight>} — Glowleaf colour, keep surrounding font
 * - {@code <extended>…</extended>} — Glowleaf + Canela (display face)
 * - {@code <pink>…</pink>} — legacy alias of {@code <highlight>}
 */
final class TextFormatter
{
    /** Glowleaf colour only (inherits body font). */
    public const HIGHLIGHT_CLASS = 'culvers-highlight';

    /** Glowleaf + Canela. */
    public const EXTENDED_CLASS = 'culvers-highlight culvers-highlight--extended';

    /** Short ACF field instruction for editors. */
    public static function highlightFieldInstructions(): string
    {
        return __(
            'Glowleaf colour: <highlight>…</highlight>. '
            . 'Glowleaf + Canela: <extended>…</extended> '
            . '(e.g. <extended>Now open</extended>).',
            'culvers'
        );
    }

    public static function replaceHighlightTags(string $text): string
    {
        $open = '<span class="' . self::HIGHLIGHT_CLASS . '">';
        $with_open = (string) preg_replace('/<\s*highlight\s*>/i', $open, $text);

        return (string) preg_replace('/<\s*\/\s*highlight\s*>/i', '</span>', $with_open);
    }

    public static function replaceExtendedTags(string $text): string
    {
        $open = '<span class="' . self::EXTENDED_CLASS . '">';
        $with_open = (string) preg_replace('/<\s*extended\s*>/i', $open, $text);

        return (string) preg_replace('/<\s*\/\s*extended\s*>/i', '</span>', $with_open);
    }

    /** @deprecated Use {@see replaceHighlightTags}; kept for older content using {@code pink}. */
    public static function replacePinkTags(string $text): string
    {
        $open = '<span class="' . self::HIGHLIGHT_CLASS . '">';
        $with_open = (string) preg_replace('/<\s*pink\s*>/i', $open, $text);

        return (string) preg_replace('/<\s*\/\s*pink\s*>/i', '</span>', $with_open);
    }

    /** Expand all accent tags (extended, highlight, pink). */
    public static function replaceAccentTags(string $text): string
    {
        return self::replacePinkTags(self::replaceHighlightTags(self::replaceExtendedTags($text)));
    }

    public static function inline(string $text): string
    {
        $text = self::ensureLineBreaks($text);
        $with_tags = self::replaceAccentTags($text);
        $result = (string) wp_kses($with_tags, self::inlineAllowedTags());
        $result = (string) preg_replace('/<\/p>\s*<p>/i', '<br>', $result);
        $result = (string) preg_replace('/<\/?p>/i', '', $result);

        return trim($result);
    }

    public static function rich(string $text): string
    {
        return self::richInternal($text, true);
    }

    public static function richWithoutWpautop(string $text): string
    {
        return self::richInternal($text, false);
    }

    private static function richInternal(string $text, bool $applyWpautop): string
    {
        $text = self::ensureLineBreaks($text);
        $with_tags = self::replaceAccentTags($text);
        if (
            $applyWpautop
            && ! preg_match('/<\s*(?:p|div|ul|ol|h[1-6]|blockquote|table|figure|section|article)\b/i', $with_tags)
        ) {
            $with_tags = wpautop($with_tags, true);
        }

        return (string) wp_kses($with_tags, self::richAllowedTags());
    }

    public static function plain(string $text, bool $withLineBreaks = false): string
    {
        $parts = preg_split(
            '/(<\s*\/?\s*(?:pink|highlight|extended)\s*>)/i',
            $text,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        if (! is_array($parts)) {
            $parts = [$text];
        }

        $output = '';
        $open_spans = 0;

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if ((bool) preg_match('/^<\s*extended\s*>$/i', $part)) {
                $output .= '<span class="' . self::EXTENDED_CLASS . '">';
                $open_spans++;

                continue;
            }

            if ((bool) preg_match('/^<\s*(?:pink|highlight)\s*>$/i', $part)) {
                $output .= '<span class="' . self::HIGHLIGHT_CLASS . '">';
                $open_spans++;

                continue;
            }

            if ((bool) preg_match('/^<\s*\/\s*(?:pink|highlight|extended)\s*>$/i', $part)) {
                if ($open_spans > 0) {
                    $output .= '</span>';
                    $open_spans--;
                }

                continue;
            }

            $output .= esc_html($part);
        }

        while ($open_spans > 0) {
            $output .= '</span>';
            $open_spans--;
        }

        if ($withLineBreaks) {
            $output = (string) nl2br($output);
        }

        return $output;
    }

    public static function hasVisibleContent(?string $text): bool
    {
        if (! is_string($text)) {
            return false;
        }

        $candidate = trim($text);
        if ($candidate === '') {
            return false;
        }

        $candidate = self::replaceAccentTags($candidate);
        $candidate = str_replace(["\xc2\xa0", '&nbsp;', '&#160;'], ' ', $candidate);
        $candidate = (string) preg_replace('/<br\s*\/?>/i', '', $candidate);
        $candidate = wp_strip_all_tags($candidate, false);
        $candidate = (string) preg_replace('/[\s\p{Cf}]+/u', '', $candidate);

        return $candidate !== '';
    }

    private static function ensureLineBreaks(string $text): string
    {
        $has_br_or_p = str_contains($text, '<br') || str_contains($text, '<p');
        $has_newline = str_contains($text, "\n") || str_contains($text, "\r");
        if (! $has_br_or_p && $has_newline) {
            return (string) nl2br($text, false);
        }

        return $text;
    }

    /** @return array<string, array<string, bool>> */
    private static function inlineAllowedTags(): array
    {
        return [
            'br' => [],
            'p' => [],
            'strong' => [],
            'em' => ['data-font' => true],
            'small' => ['data-font' => true],
            'span' => [
                'class' => true,
            ],
            'a' => [
                'href' => true,
                'target' => true,
                'rel' => true,
            ],
        ];
    }

    /**
     * Post HTML plus span.class / data-font attrs used by theme rich text.
     *
     * @return array<string, mixed>
     */
    private static function richAllowedTags(): array
    {
        $allowed = wp_kses_allowed_html('post');
        $span = isset($allowed['span']) && is_array($allowed['span']) ? $allowed['span'] : [];
        $span['class'] = true;
        $allowed['span'] = $span;
        $allowed['small'] = array_merge(
            is_array($allowed['small'] ?? null) ? $allowed['small'] : [],
            ['data-font' => true]
        );
        $allowed['br'] = $allowed['br'] ?? [];
        $allowed['em'] = array_merge(
            is_array($allowed['em'] ?? null) ? $allowed['em'] : [],
            ['data-font' => true]
        );

        return $allowed;
    }
}
