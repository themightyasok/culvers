<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Repeated inner widths / gutters — aligned to the **initial** site chrome (footer band +
 * mega-nav bar row insets), using **static Tailwind only**.
 *
 * Main content deliberately does **not** follow `site-header` Alpine scroll state
 * (`max-w-8xl` vs `max-w-none`, padding swaps, etc.) — widths stay fixed per load.
 *
 * `max-w-8xl` is defined as `--container-8xl: 90rem` in `theme.tokens.css`.
 */
final class LayoutShell
{
    /**
     * Canonical horizontal inset — matches `mega-nav__bar-row` and Plan / content sections.
     * Use on any full-bleed band: background edge-to-edge, content inset with this.
     */
    public const GUTTER_X = 'px-4 md:px-5 lg:px-6';

    /**
     * Primary blocks (contact, centre map, split highlight, etc.): cap at 8xl + {@see self::GUTTER_X}.
     */
    public const INNER_MAX_GUTTERED = 'mx-auto w-full max-w-8xl ' . self::GUTTER_X;

    /** Edge-to-edge within the 8xl cap (rare — prefer {@see self::INNER_MAX_GUTTERED}). */
    public const INNER_MAX_FLUSH_X = 'mx-auto w-full max-w-8xl px-0';

    /** Narrow readable column (opening hours, store details). */
    public const INNER_READABLE_960 = 'mx-auto max-w-[960px] ' . self::GUTTER_X;
}
