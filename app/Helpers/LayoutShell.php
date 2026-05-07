<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Repeated inner widths / gutters — mirror header & footer (`resources/views/sections/header.blade.php`, `footer.blade.php`).
 *
 * Use these class strings in Blade so flexible components stay visually aligned without duplicating literals.
 *
 * `max-w-8xl` is a numerical pattern extension on Tailwind's container scale
 * (xs..7xl → 8xl), defined as `--container-8xl: 90rem` in `theme.tokens.css`.
 */
final class LayoutShell
{
    /** Site shell — `max-w-8xl` (1440px) + Figma horizontal gutters (`px-4 md:px-12`). */
    public const INNER_MAX_GUTTERED = 'mx-auto w-full max-w-8xl px-4 md:px-12';

    /** Full-width inner shell without horizontal padding (grid sections manage their own inset). */
    public const INNER_MAX_FLUSH_X = 'mx-auto w-full max-w-8xl px-0';

    /** Narrow readable column (opening hours, store details). */
    public const INNER_READABLE_960 = 'mx-auto max-w-[960px] px-5 sm:px-6 lg:px-8';
}
