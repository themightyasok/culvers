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
     * Heading tag from saved meta when present (legacy rows), otherwise a theme default.
     *
     * @param array<string, mixed> $component
     */
    public static function headingTagFromComponent(array $component, string $legacyKey, int $default = 2): string
    {
        return self::headingTag($component[$legacyKey] ?? null, $default);
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
     * heading tag is identical across the CMS. Pair every visible heading text
     * field with one of these so editors always know the resulting HTML tag.
     *
     * @param ?string $instructions Optional override for the helper text.
     * @param bool $allowH1 Whether H1 is selectable (only true for components
     *                      that may host the page H1, e.g. content-section on
     *                      a long-form policy page; the hero takes the H1 on
     *                      every other page).
     * @param int $default Default ordinal (2–6, or 1 when allowH1).
     * @param string|null $width Optional ACF wrapper width (e.g. `'30'`, `'50'`).
     *                           Pair with the matching heading text field's width
     *                           so both sit on one row (typical 70 / 30 pair).
     *
     * @return array<string, mixed>
     */
    public static function headingLevelField(
        ?string $instructions = null,
        bool $allowH1 = false,
        int $default = 2,
        ?string $width = null,
    ): array {
        $instructions ??= __(
            'Use one H1 per page (typically the hero). Other sections should stay H2–H6 for a logical outline.',
            'culvers'
        );

        $options = [
            'label' => __('Heading level', 'culvers'),
            'instructions' => $instructions,
            'choices' => self::headingLevelChoices($allowH1),
            'default_value' => $default,
            'allow_null' => 0,
            'return_format' => 'value',
        ];

        if ($width !== null && $width !== '') {
            $options['wrapper'] = ['width' => $width];
        }

        return [
            'type' => 'select',
            'options' => $options,
        ];
    }

    /**
     * ACF range slider for a black media overlay (0–100%).
     *
     * @return array<string, mixed>
     */
    public static function overlayOpacityRangeField(
        string $label,
        ?string $instructions = null,
        int $default = 25,
        ?string $width = null,
    ): array {
        $instructions ??= __(
            'Solid black overlay on the image or video (0% = none). Helps light text stay readable.',
            'culvers'
        );

        $options = [
            'label' => $label,
            'instructions' => $instructions,
            'default_value' => $default,
            'min' => 0,
            'max' => 100,
            'step' => 1,
            'append' => '%',
        ];

        if ($width !== null && $width !== '') {
            $options['wrapper'] = ['width' => $width];
        }

        return [
            'type' => 'range',
            'options' => $options,
        ];
    }

    /**
     * Clamp a saved overlay opacity to 0–100 (percent).
     */
    public static function overlayOpacityPercent(mixed $raw, int $default = 25): int
    {
        if (! is_numeric($raw)) {
            return $default;
        }

        return max(0, min(100, (int) $raw));
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
     * Canonical class spine for a section-level heading (default H2).
     *
     * The Culver Square design system treats every section title as **64px
     * Canela at desktop, 48px at mobile** — Figma "Desktop/Titles/H2 Title"
     * (Canela 58 / lh 1.15 / tracking 0). The site H1 (page hero, used once)
     * is the only heading allowed to go larger.
     *
     * Notes:
     *   • `text-5xl` and `text-6xl` ship paired line-heights (1.1 and 1.15) in
     *     {@see resources/styles/theme.tokens.css} that already match Figma.
     *     Do **not** add `leading-tight` / `leading-none` / `leading-[1.1]`
     *     on top — those override the calibrated token line-height.
     *   • Figma sets tracking to 0 for headings; do not add `tracking-tight`.
     *   • Pass a tone class as the first argument (defaults to `text-deep-moss`).
     *   • Pass an `extra` string for component-specific layout utilities
     *     (margins, alignment, BEM root). Keep typography utilities OUT of
     *     `extra` — they belong here so the whole site stays in lockstep.
     */
    public static function sectionHeadingClasses(
        string $toneClass = 'text-deep-moss',
        string $extra = ''
    ): string {
        $base = 'font-heading text-5xl md:text-6xl ' . $toneClass;

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * Section H2 in intro stacks (heading → sub → body → CTA).
     *
     * Pairs with `.section-intro-stack__heading` / `.section-intro-stack__content` in app.css.
     * {@see sectionHeadingClasses()} keeps token line-heights for standalone titles.
     */
    public static function sectionIntroHeadingClasses(
        string $toneClass = 'text-faded-olive',
        string $extra = ''
    ): string {
        $base = 'section-intro-stack__heading font-heading text-5xl md:text-6xl ' . $toneClass;

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * Large body copy under section headings — Figma Halyard Book 20 / lh 1.3 (`text-xl`).
     * Use for section intros, opening-hours copy, split-highlight panels, info-block body, etc.
     */
    public static function sectionBodyClasses(
        string $toneClass = 'text-deep-moss',
        string $extra = ''
    ): string {
        $base = 'font-sans text-xl font-light leading-[1.3] ' . $toneClass;

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * Body copy slot inside {@see sectionIntroContentStackClasses()} — resets first `<p>` margins.
     */
    public static function sectionIntroBodyClasses(
        string $toneClass = 'text-deep-moss',
        string $extra = ''
    ): string {
        return self::sectionBodyClasses($toneClass, '[&_p:first-child]:mt-0 ' . $extra);
    }

    /**
     * Canonical intro-stack spacing — `.section-intro-stack__*` CSS + stock Tailwind margins.
     *
     * Do not use flex `gap-*` between heading and body — use {@see sectionIntroContentStackClasses()}.
     */

    /** Inner wrapper for subheading + body + optional CTA beneath a section heading. */
    public static function sectionIntroContentStackClasses(string $extra = ''): string
    {
        $base = 'section-intro-stack__content flex w-full flex-col items-center';

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /** @deprecated Flex gap on intro stacks — use {@see sectionIntroContentStackClasses()} instead. */
    public static function sectionIntroStackGapClasses(string $extra = ''): string
    {
        return trim($extra);
    }

    /** Margin from section heading to body when not using {@see sectionIntroContentStackClasses()}. */
    public static function sectionHeadingToBodyGapClasses(string $extra = ''): string
    {
        $base = 'mt-4';

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /** Margin from subheading / label line to body copy beneath. */
    public static function sectionSubheadingToBodyGapClasses(string $extra = ''): string
    {
        $base = 'mt-4';

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /** Margin from body copy to CTA pill beneath. */
    public static function sectionBodyToCtaGapClasses(string $extra = ''): string
    {
        $base = 'mt-11';

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * Margin below body / sub copy when there is no CTA — same 44px rhythm as
     * {@see sectionBodyToCtaGapClasses()} so card grids are not tight against prose.
     */
    public static function sectionBodyToFollowContentGapClasses(string $extra = ''): string
    {
        $base = 'mb-10 md:mb-12';

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * Margin below a section heading before non-prose content (hours list, card grid, scroller strip).
     */
    public static function sectionHeadingToFollowContentGapClasses(string $extra = ''): string
    {
        $base = 'mb-10 md:mb-12';

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * Tighter heading → carousel on shop singles (Figma `51:8984` / `51:8337`).
     *
     * @deprecated Prefer {@see sectionHeadingSpacingClasses()} with preset `carousel`.
     */
    public static function sectionHeadingToCarouselGapClasses(string $extra = ''): string
    {
        return self::sectionHeadingSpacingClasses('carousel', $extra);
    }

    /**
     * @return array<string, string>
     */
    public static function sectionHeadingSpacingChoices(): array
    {
        return [
            'standard' => __('Standard — list or prose below', 'culvers'),
            'carousel' => __('Carousel — horizontal strip below', 'culvers'),
            'compact' => __('Compact — multi-column panel below', 'culvers'),
        ];
    }

    /**
     * Editor select for space between a section H2 and the content beneath it.
     *
     * @return array{type: string, options: array<string, mixed>}
     */
    public static function sectionHeadingSpacingField(
        string $default = 'standard',
        ?string $instructions = null
    ): array {
        return [
            'type' => 'select',
            'options' => [
                'label' => __('Heading spacing', 'culvers'),
                'instructions' => $instructions ?? __(
                    'Vertical space between the section heading and the content below.',
                    'culvers'
                ),
                'choices' => self::sectionHeadingSpacingChoices(),
                'default_value' => $default,
                'allow_null' => 0,
                'return_format' => 'value',
                'wrapper' => ['width' => '50'],
            ],
        ];
    }

    public static function sectionHeadingSpacingClasses(string $preset, string $extra = ''): string
    {
        $base = match ($preset) {
            'carousel' => 'mb-14 md:mb-5',
            'compact' => 'mb-8 md:mb-5',
            default => 'mb-10 md:mb-12',
        };

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function resolveSectionHeadingSpacing(
        array $component,
        string $fieldKey,
        string $default = 'standard'
    ): string {
        $raw = is_string($component[$fieldKey] ?? null) ? trim($component[$fieldKey]) : '';

        if (in_array($raw, ['standard', 'carousel', 'compact'], true)) {
            return $raw;
        }

        return $default;
    }

    /**
     * Margin below intro copy / heading before a filter or tab pill row.
     */
    public static function sectionIntroToControlsGapClasses(string $extra = ''): string
    {
        $base = 'mb-6 md:mb-8';

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * Margin below a filter / tab pill row before a card grid or panel stack.
     * Apply on the control row — never `mt-*` on the grid beneath.
     */
    public static function sectionControlsToFollowContentGapClasses(string $extra = ''): string
    {
        $base = 'mb-10 md:mb-14';

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * Image-hero H1 — mobile `51:9234`: Canela 46 / lh 1 (wraps cleanly); desktop
     * `51:9364`: Canela 96 / lh 0.7 display lockup. Homepage slider uses its own
     * ramp in hero-slider.blade.php — not this helper.
     */
    public static function imageHeroTitleClasses(
        string $toneClass = 'text-glowleaf',
        string $extra = ''
    ): string {
        $base = 'font-heading text-[46px] leading-none md:text-9xl md:leading-[0.7] ' . $toneClass;

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * Image-hero subtitle — Figma `51:9236` / `51:9495`: Commuter SemiBold 16 / lh 24 /
     * ~1 px tracking, uppercase on the hero band (all breakpoints in the mobile frames).
     */
    public static function imageHeroSubtitleClasses(string $extra = ''): string
    {
        $base = 'font-label text-base font-semibold uppercase leading-6 tracking-[0.0625em] text-white';

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * Hero-slider slide kicker — Figma `51:8206`: Commuter 16 / 24 / ~1 px tracking on mobile;
     * desktop keeps the wider `text-xl` + `tracking-[0.2em]` ramp from the homepage hero.
     */
    public static function heroKickerClasses(string $extra = ''): string
    {
        $base = 'font-label text-base font-semibold uppercase leading-6 tracking-[0.0625em] text-white md:text-xl md:tracking-[0.2em]';

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * Sidebar / panel subheads that are Halyard on mobile but Canela from `sm` up.
     *
     * Figma `51:9546` / `51:8898`: Halyard Medium 20 / lh 24 (sentence case). Tablet+
     * contact + shop panels revert to Canela 32 (`text-3xl`).
     */
    public static function mobilePanelSubheadClasses(
        string $toneClass = 'text-faded-olive',
        string $extra = ''
    ): string {
        $base = 'font-sans text-xl font-medium leading-6 sm:font-heading sm:text-3xl sm:font-normal sm:leading-[1.1] ' . $toneClass;

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * Centre-map category labels on mobile — Figma `51:8961`–`8976`: Halyard Book 20 / lh 24.
     * Desktop accordion list keeps Commuter uppercase (`lg:` utilities on the element).
     */
    public static function centreMapCategoryMobileClasses(string $extra = ''): string
    {
        $base = 'max-lg:font-sans max-lg:text-xl max-lg:font-light max-lg:normal-case max-lg:leading-6 max-lg:tracking-normal';

        return trim($extra !== '' ? $base . ' ' . $extra : $base);
    }

    /**
     * Standard structural classes shared by every component `<section>`.
     *
     * Returns the resolved `_grid_classes` (column span + responsive gutters,
     * plus the inter-section `mt-*` rhythm utility prepended by
     * {@see resources/views/partials/flexible-components.blade.php}), with
     * inset horizontal gutters optionally stripped for components that render
     * their own inner shell.
     *
     * Outer vertical padding (`pt-*` / `pb-*` / `py-*` on the section root) is
     * **never** emitted — inter-section spacing is the parent grid `gap-y-[76px]`
     * ({@see \App\Helpers\Grid::getMainGridContainerClasses()}), with optional
     * negative `mt-*` from {@see \App\Helpers\Rhythm} for flush/breathed rows.
     * Painted bands apply *internal* padding inside their own shells only.
     *
     * @param array<string, mixed> $component
     */
    public static function rootClasses(array $component, bool $stripGutters = true): string
    {
        $grid = isset($component['_grid_classes']) && is_string($component['_grid_classes'])
            ? $component['_grid_classes']
            : '';

        if ($stripGutters) {
            $grid = Grid::stripHorizontalInsetPadding($grid);
        }

        return trim($grid);
    }

    /**
     * Drop-in ACF field map for md+ default imagery + optional mobile crop (`_*_image_mobile`).
     *
     * @param non-empty-string $prefix Subject prefix (e.g. `promo` → `promo_image`, `promo_image_mobile`).
     * @param array{desktop?: string, mobile?: string} $labels
     *
     * @return array<string, array<string, mixed>>
     */
    public static function responsiveImagePair(string $prefix, array $labels = []): array
    {
        $dLabel = $labels['desktop'] ?? __('Image (tablet / desktop)', 'culvers');
        $mLabel = $labels['mobile'] ?? __('Image (mobile override)', 'culvers');

        return [
            "{$prefix}_image" => [
                'type' => 'image',
                'options' => [
                    'label' => $dLabel,
                    'instructions' => __('Default from the md breakpoint upward (tablet + desktop).', 'culvers'),
                    'return_format' => 'array',
                    'preview_size' => 'large',
                    'library' => 'all',
                ],
            ],
            "{$prefix}_image_mobile" => [
                'type' => 'image',
                'options' => [
                    'label' => $mLabel,
                    'instructions' => __('Optional; shown only below md when set.', 'culvers'),
                    'return_format' => 'array',
                    'preview_size' => 'medium',
                    'library' => 'all',
                    'wrapper' => ['width' => '50'],
                ],
            ],
        ];
    }

    /**
     * @deprecated Use {@see self::responsiveImagePair()} — tablet-specific imagery was removed from the authoring model.
     *
     * @param array{desktop?: string, mobile?: string} $labels
     *
     * @return array<string, array<string, mixed>>
     */
    public static function responsiveImageTriplet(string $prefix, array $labels = []): array
    {
        $pairLabels = [];
        if (array_key_exists('desktop', $labels)) {
            $pairLabels['desktop'] = $labels['desktop'];
        }
        if (array_key_exists('mobile', $labels)) {
            $pairLabels['mobile'] = $labels['mobile'];
        }

        return self::responsiveImagePair($prefix, $pairLabels);
    }

    /**
     * In-tab sub-section divider (a `message` field with a stable wrapper class
     * so {@see resources/styles/acf-flexible-admin.css} can style it as a pill
     * heading). Use inside a component's own `main` / `typography` / `mobile`
     * field map — the registry already emits a divider before each chrome block.
     *
     * The label is upper-cased here so the divider always reads as a section
     * break, even if admin CSS for `text-transform` is overridden elsewhere.
     *
     * @return array<string, mixed>
     */
    public static function sectionDivider(string $label): array
    {
        return [
            'type' => 'message',
            'options' => [
                'message' => '<span class="culvers-acf-section-head__label">'
                    . esc_html(mb_strtoupper($label))
                    . '</span>',
                'esc_html' => 0,
                'wrapper' => ['class' => 'culvers-acf-section-head'],
            ],
        ];
    }
}
