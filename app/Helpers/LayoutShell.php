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
     * Primary blocks (contact, centre map, split highlight, etc.): cap at 8xl and match
     * `mega-nav__bar-row` horizontal inset (`px-4 md:px-5 lg:px-6`) — not the scrolled header.
     */
    public const INNER_MAX_GUTTERED = 'mx-auto w-full max-w-8xl px-4 md:px-5 lg:px-6';

    /** Edge-to-edge within the 8xl cap (card grids, info strips). */
    public const INNER_MAX_FLUSH_X = 'mx-auto w-full max-w-8xl px-0';

    /** Narrow readable column (opening hours, store details). */
    public const INNER_READABLE_960 = 'mx-auto max-w-[960px] px-5 sm:px-6 lg:px-8';
}
