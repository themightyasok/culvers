<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * WYSIWYG / plain text output with optional CMS highlight tags.
 *
 * Supports editor-authored {@code <highlight>...</highlight>} (semantic alias for brand accent).
 */
final class TextFormatter
{
    public static function replaceHighlightTags(string $text): string
    {
        $with_open = (string) preg_replace('/<\s*highlight\s*>/i', '<span class="text-brand-500">', $text);

        return (string) preg_replace('/<\s*\/\s*highlight\s*>/i', '</span>', $with_open);
    }

    /** @deprecated Use {@see replaceHighlightTags}; kept for older content using {@code pink}. */
    public static function replacePinkTags(string $text): string
    {
        $with_open = (string) preg_replace('/<\s*pink\s*>/i', '<span class="text-brand-500">', $text);

        return (string) preg_replace('/<\s*\/\s*pink\s*>/i', '</span>', $with_open);
    }

    public static function inline(string $text): string
    {
        $text = self::ensureLineBreaks($text);
        $with_tags = self::replacePinkTags(self::replaceHighlightTags($text));
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
        $with_tags = self::replacePinkTags(self::replaceHighlightTags($text));
        if (
            $applyWpautop
            && ! preg_match('/<\s*(?:p|div|ul|ol|h[1-6]|blockquote|table|figure|section|article)\b/i', $with_tags)
        ) {
            $with_tags = wpautop($with_tags, true);
        }
        $allowed = wp_kses_allowed_html('post');
        $span = isset($allowed['span']) && is_array($allowed['span']) ? $allowed['span'] : [];
        $span['class'] = true;
        $allowed['span'] = $span;
        $allowed['small'] = $allowed['small'] ?? [];
        $allowed['br'] = $allowed['br'] ?? [];
        $allowed['em'] = $allowed['em'] ?? [];

        return (string) wp_kses($with_tags, $allowed);
    }

    public static function plain(string $text, bool $withLineBreaks = false): string
    {
        $parts = preg_split('/(<\s*\/?\s*(?:pink|highlight)\s*>)/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (! is_array($parts)) {
            $parts = [$text];
        }

        $output = '';
        $open_spans = 0;

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if ((bool) preg_match('/^<\s*(?:pink|highlight)\s*>$/i', $part)) {
                $output .= '<span class="text-brand-500">';
                $open_spans++;

                continue;
            }

            if ((bool) preg_match('/^<\s*\/\s*(?:pink|highlight)\s*>$/i', $part)) {
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

        $candidate = self::replacePinkTags(self::replaceHighlightTags($candidate));
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
}
