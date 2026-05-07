<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Inter-section vertical rhythm for the flexible-components grid.
 *
 * Measured against the Figma file (`Culver Square Website Design — Developer
 * Release`, file key `KoBl6rTY98YnvusBgKLx4A`) on the homepage and
 * Plan-My-Visit page, the design uses a tight standard gap with two
 * exceptions reserved for specific authored cases:
 *
 *   STANDARD  96 px        Site-wide default between flexible components (`mt-24`).
 *   HUGGED    ~60 px       Reserved for future "intro band hugs its content"
 *                            cases. Not used by default — apply per-case.
 *   FLUSH     0 px         Cluster joins where one component flows visually
 *                            into the next (e.g. Plan-My-Visit Travel
 *                            Calculator → Centre Map, Newsletter → Footer).
 *                            Not used by default — apply per-case.
 *
 * Implementation: the renderer
 * ({@see resources/views/partials/flexible-components.blade.php}) walks the
 * components in order and asks {@see self::spaceAboveClass()} for the
 * `mt-*` utility to put on the current row. The first row gets no top
 * margin (the page hero handles its own offset under the fixed header).
 *
 * There is no editor surface for this — it is an architectural decision
 * baked in code so consistency does not depend on per-page authoring.
 * To change the default gap, edit {@see self::SPACE_STANDARD}; to mark
 * a layout key as always-hugged or always-flush, add a branch inside
 * {@see self::spaceAboveClass()}.
 *
 * Tailwind utilities ship from the default v4 spacing scale (1 unit = 4 px):
 *   • `mt-24` → 6rem → 96 px (STANDARD)
 *   • `mt-15` → 3.75rem → 60 px (HUGGED, reserved)
 *   • `mt-0`  → 0 → 0 px (FLUSH, reserved)
 */
final class Rhythm
{
    /** Standard inter-section gap between flexible components (96 px). */
    public const SPACE_STANDARD = 'mt-24';

    /** Reserved: intro band hugs its immediate next sibling. */
    public const SPACE_HUGGED = 'mt-15';

    /** Reserved: cluster join — the next component butts directly against this one. */
    public const SPACE_FLUSH = 'mt-0';

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
     */
    public static function spaceAboveClass(?string $previousLayout, array $previousComponent = []): string
    {
        if ($previousLayout === null) {
            return '';
        }

        return self::SPACE_STANDARD;
    }
}
