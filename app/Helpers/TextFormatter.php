<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * WYSIWYG / plain text output with optional CMS accent tags.
 *
 * - {@code <highlight>…</highlight>} — Glowleaf colour, keep surrounding font
 * - {@code <extended>…</extended>} — Glowleaf + Canela (display face)
 * - {@code <validity>…</validity>} — Glowleaf Action Label (Commuters Sans, uppercase)
 * - {@code <pink>…</pink>} — legacy alias of {@code <highlight>}
 */
final class TextFormatter
{
    /** Glowleaf colour only (inherits body font). */
    public const HIGHLIGHT_CLASS = 'culvers-highlight';

    /** Glowleaf + Canela. */
    public const EXTENDED_CLASS = 'culvers-highlight culvers-highlight--extended';

    /** Glowleaf Action Label (offer dates, meta lines). */
    public const VALIDITY_CLASS = 'culvers-validity';

    /** Short ACF field instruction for editors. */
    public static function highlightFieldInstructions(): string
    {
        return __(
            'Glowleaf colour: <highlight>…</highlight>. '
            . 'Glowleaf + Canela: <extended>…</extended> '
            . '(e.g. <extended>Now open</extended>). '
            . 'Offer dates (small caps label): <validity>Offer valid between: …</validity> — '
            . 'do not wrap dates in <extended>.',
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

    public static function replaceValidityTags(string $text): string
    {
        $open = '<span class="' . self::VALIDITY_CLASS . '">';
        $with_open = (string) preg_replace('/<\s*validity\s*>/i', $open, $text);

        return (string) preg_replace('/<\s*\/\s*validity\s*>/i', '</span>', $with_open);
    }

    /** @deprecated Use {@see replaceHighlightTags}; kept for older content using {@code pink}. */
    public static function replacePinkTags(string $text): string
    {
        $open = '<span class="' . self::HIGHLIGHT_CLASS . '">';
        $with_open = (string) preg_replace('/<\s*pink\s*>/i', $open, $text);

        return (string) preg_replace('/<\s*\/\s*pink\s*>/i', '</span>', $with_open);
    }

    /** Expand all accent tags (validity, extended, highlight, pink). */
    public static function replaceAccentTags(string $text): string
    {
        // Validity first so a mistaken <extended><validity>… still lands on the label face.
        return self::replacePinkTags(
            self::replaceHighlightTags(
                self::replaceExtendedTags(
                    self::replaceValidityTags($text)
                )
            )
        );
    }

    public static function inline(string $text): string
    {
        $text = self::normalizeInput($text);
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
        $text = self::normalizeInput($text);
        $text = self::ensureLineBreaks($text);
        $with_tags = self::replaceAccentTags($text);
        if (
            $applyWpautop
            && ! preg_match('/<\s*(?:p|div|ul|ol|h[1-6]|blockquote|table|figure|section|article)\b/i', $with_tags)
        ) {
            $with_tags = wpautop($with_tags, true);
        }

        $html = (string) wp_kses($with_tags, self::richAllowedTags());

        return self::promoteOfferValidityParagraphs($html);
    }

    public static function plain(string $text, bool $withLineBreaks = false): string
    {
        $text = self::normalizeInput($text);
        $parts = preg_split(
            '/(<\s*\/?\s*(?:pink|highlight|extended|validity)\s*>)/i',
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

            if ((bool) preg_match('/^<\s*validity\s*>$/i', $part)) {
                $output .= '<span class="' . self::VALIDITY_CLASS . '">';
                $open_spans++;

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

            if ((bool) preg_match('/^<\s*\/\s*(?:pink|highlight|extended|validity)\s*>$/i', $part)) {
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

        $candidate = self::normalizeInput($candidate);
        $candidate = self::replaceAccentTags($candidate);
        $candidate = str_replace(["\xc2\xa0", '&nbsp;', '&#160;'], ' ', $candidate);
        $candidate = (string) preg_replace('/<br\s*\/?>/i', '', $candidate);
        $candidate = wp_strip_all_tags($candidate, false);
        $candidate = (string) preg_replace('/[\s\p{Cf}]+/u', '', $candidate);

        return $candidate !== '';
    }

    /**
     * TinyMCE / paste often entity-encodes tags (&lt;p&gt;…) while leaving custom
     * accent tags intact — that renders as literal HTML on the front end.
     */
    private static function normalizeInput(string $text): string
    {
        if ($text === '' || ! str_contains($text, '&')) {
            return $text;
        }

        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Style “Offer valid between…” lines as Action Label / Glowleaf even when
     * editors paste plain paragraphs (or legacy Tailwind utility classes).
     */
    private static function promoteOfferValidityParagraphs(string $html): string
    {
        if ($html === '' || ! preg_match('/offer\s+valid\s+between/i', $html)) {
            return $html;
        }

        // Wrong accent on a validity phrase → label face.
        $html = (string) preg_replace(
            '/<span class="culvers-highlight(?:\s+culvers-highlight--extended)?">\s*(Offer\s+valid\s+between[\s\S]*?)<\/span>/i',
            '<span class="' . self::VALIDITY_CLASS . '">$1</span>',
            $html
        );

        return (string) preg_replace_callback(
            '/<p\b([^>]*)>([\s\S]*?)<\/p>/i',
            static function (array $match): string {
                $attrs = $match[1];
                $inner = $match[2];
                $plain = trim(wp_strip_all_tags($inner));
                if ($plain === '' || ! preg_match('/^offer\s+valid\s+between\b/i', $plain)) {
                    return $match[0];
                }

                if (str_contains($attrs, self::VALIDITY_CLASS) || str_contains($inner, self::VALIDITY_CLASS)) {
                    // Normalise legacy utility-class paragraphs to the theme class.
                    return '<p class="' . self::VALIDITY_CLASS . '">' . $inner . '</p>';
                }

                return '<p class="' . self::VALIDITY_CLASS . '">' . $inner . '</p>';
            },
            $html
        );
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
            'p' => [
                'class' => true,
            ],
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
        $p = isset($allowed['p']) && is_array($allowed['p']) ? $allowed['p'] : [];
        $p['class'] = true;
        $allowed['p'] = $p;
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
