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
     *   STANDARD  100 px                        `gap-y-[100px]` on the grid
     *   HUGGED    ~60 px effective               `-mt-[16px]`
     *   BREATHED  48 px effective                `-mt-[28px]`
     *   FLUSH     0 px effective                 `-mt-[100px]`
 *
 * Implementation: the parent grid owns the default gap. The renderer walks rows
 * and asks {@see self::spaceAboveClass()} for optional negative `mt-*` utilities
 * that reduce the grid gap for the exceptions above. Do not stack outer `py-*`
 * on component roots — that was doubling the visual rhythm.
 *
 * Tailwind:
     *   • `gap-y-[100px]` → 100 px (STANDARD, on the grid)
     *   • `-mt-[100px]` → cancel one gap unit (FLUSH)
 *   • `-mt-[28px]` → 48 px effective (BREATHED)
 *   • `-mt-[16px]` → 60 px effective (HUGGED, reserved)
 */
final class Rhythm
{
    /** Default gap is on the grid (`gap-y-[100px]`); rows do not add top margin. */
    public const SPACE_STANDARD = '';

    /** Reserved: intro band hugs its immediate next sibling (60 px effective). */
    public const SPACE_HUGGED = '-mt-[16px]';

    /** Section-header-with-body → next component (48 px effective). */
    public const SPACE_BREATHED = '-mt-[28px]';

    /** Slim section_header → next component (0 px effective). */
    public const SPACE_FLUSH = '-mt-[100px]';

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

            // Slim label (heading only): cancel grid gap so the H2 sits on its subject.
            // Body band: keep the full grid gap — spacing below copy lives on the header
            // row via {@see Component::sectionBodyToFollowContentGapClasses()}, not negative
            // margin on the component below (BREATHED was collapsing 76px → ~48px).
            return $hasBody ? self::SPACE_STANDARD : self::SPACE_FLUSH;
        }

        return self::SPACE_STANDARD;
    }
}
