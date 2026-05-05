<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Repeated inner widths / gutters — mirror header & footer (`resources/views/sections/header.blade.php`, `footer.blade.php`).
 *
 * Use these class strings in Blade so flexible components stay visually aligned without duplicating literals.
 */
final class LayoutShell
{
    /** `max-w-[1440px]` + horizontal gutters (`px-4 md:px-[46px]`). */
    public const INNER_MAX_GUTTERED = 'mx-auto w-full max-w-[1440px] px-4 md:px-[46px]';

    /** Full-width inner shell without horizontal padding (grid sections manage their own inset). */
    public const INNER_MAX_FLUSH_X = 'mx-auto w-full max-w-[1440px] px-0';

    /** Narrow readable column (opening hours, store details). */
    public const INNER_READABLE_960 = 'mx-auto max-w-[960px] px-5 sm:px-6 lg:px-8';
}
