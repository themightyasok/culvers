<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Inter-section vertical rhythm for the flexible-components grid.
 *
 * Measured against the Figma file (`Culver Square Website Design — Developer
 * Release`, file key `KoBl6rTY98YnvusBgKLx4A`) on the homepage and
 * Plan-My-Visit page, the design uses a single standard gap (~91–96 px)
 * between flexible components, with a few exceptions:
 *
 *   STANDARD  96 px        `gap-y-24` on the flexible-components grid
 *                            ({@see Grid::getMainGridContainerClasses()}). Same at all breakpoints.
 *   HUGGED    ~60 px       Reserved — `-mt-9`.
 *   BREATHED  48 px        After `section_header` with body — `-mt-12`.
 *   FLUSH     0 px         After slim `section_header` — `-mt-24`.
 *
 * Implementation: the parent grid owns the default gap. The renderer walks rows
 * and asks {@see self::spaceAboveClass()} for optional negative `mt-*` utilities
 * that reduce the grid gap for the exceptions above. Do not stack outer `py-*`
 * on component roots — that was doubling the visual rhythm.
 *
 * Tailwind (1 unit = 4 px):
 *   • `gap-y-24` → 96 px (STANDARD, on the grid)
 *   • `-mt-24` → cancel one gap unit (FLUSH)
 *   • `-mt-12` → 48 px effective (BREATHED)
 *   • `-mt-9` → 60 px effective (HUGGED, reserved)
 */
final class Rhythm
{
    /** Default gap is on the grid (`gap-y-24`); rows do not add top margin. */
    public const SPACE_STANDARD = '';

    /** Reserved: intro band hugs its immediate next sibling (60 px effective). */
    public const SPACE_HUGGED = '-mt-9';

    /** Section-header-with-body → next component (48 px effective). */
    public const SPACE_BREATHED = '-mt-12';

    /** Slim section_header → next component (0 px effective). */
    public const SPACE_FLUSH = '-mt-24';

    /**
     * Top-margin utility to apply to the *current* row, given what came
     * before it. `null` → first row (no margin).
     *
     * The previous component's full data array is accepted so future rules
     * can decide based on instance content (e.g. heading present, image
     * filled, cluster flag). Today every non-first row is Standard.
     *
     * @param array<string, mixed> $previousComponent Sanitised row data of
     *   the previous component (may be empty when first).
     * @param ?string $currentLayout Layout key of the row receiving the margin.
     */
    public static function spaceAboveClass(
        ?string $previousLayout,
        array $previousComponent = [],
        ?string $currentLayout = null,
    ): string {
        if ($previousLayout === null) {
            return '';
        }

        /*
         * Full-bleed image hero → shop intro band (Figma 51:6154 / 51:6679): cancel
         * the default grid gap; the intro block's own pt-[100px] (lg) / pt-[90px]
         * (mobile, 51:8886) supplies the measured space below the hero keyline.
         */
        if ($previousLayout === 'image_hero' && $currentLayout === 'shop_intro_block') {
            return self::SPACE_FLUSH;
        }

        /*
         * A `section_header` is an intro band whose role is to label what
         * immediately follows. There are two shapes:
         *
         *  • Slim label: eyebrow + heading only (no body) — the heading should
         *    sit directly atop its subject component (Figma cluster pages:
         *    Leasing, Plan-My-Visit). FLUSH.
         *  • Content band: eyebrow + heading + body paragraph — the body needs
         *    breathing room or the paragraph crashes into the next component.
         *    BREATHED (48 px).
         *
         * Sheet feedback (May): Guest Services "About Colchester" section_header
         * has a body paragraph and was flushing into the History 2-col below —
         * the body line read as if it were part of the History block. Branching
         * on body presence fixes both the slim label pattern and the heavier
         * intro pattern without forcing editors to pick a spacing knob.
         */
        if ($previousLayout === 'section_header') {
            $hasBody = trim(strip_tags((string) ($previousComponent['header_body'] ?? ''))) !== '';
            return $hasBody ? self::SPACE_BREATHED : self::SPACE_FLUSH;
        }

        return self::SPACE_STANDARD;
    }
}
