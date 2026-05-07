<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Tailwind padding helpers — sub-element spacing only.
 *
 * The legacy section-level padding helpers (`getClasses`, `getTopPaddingClass`,
 * `getBottomPaddingClass`, `getBottomPaddingClassesOnly`) were removed when the
 * per-component "Top padding" / "Bottom padding" ACF controls were dropped: the
 * inter-section vertical rhythm between flexible components now comes from a
 * single `gap-y-32` on the parent grid container (see
 * {@see Grid::getMainGridContainerClasses()}), so no component is allowed to
 * contribute to outer spacing. Components that need internal padding around
 * their own painted background apply it directly in their Blade template.
 *
 * What remains here are the **header / subheader / body** padding helpers used
 * by `horizontal_scroller.php` to let editors fine-tune the spacing between
 * text elements *inside* the scroller (heading ↔ subheading ↔ body, item
 * kicker ↔ heading ↔ body). That is genuinely intra-component typography
 * spacing — a different concern from the dead section-level controls — and
 * stays editor-tunable.
 */
final class Padding
{
    /**
     * ACF choices for header/subheader padding (above and below).
     * Values: none, small, medium, large, large-xl
     *
     * @return array<string, string>
     */
    public static function getHeaderSubheaderPaddingChoices(): array
    {
        return [
            'none' => 'None',
            'small' => 'Small',
            'medium' => 'Medium',
            'large' => 'Large',
            'large-xl' => 'Large XL',
        ];
    }

    /**
     * Get Tailwind padding classes for header, subheader, or body text elements.
     *
     * @param string $paddingTop    none|small|medium|large|large-xl
     * @param string $paddingBottom none|small|medium|large|large-xl
     */
    public static function getHeaderSubheaderPaddingClasses(string $paddingTop, string $paddingBottom): string
    {
        $topMap = [
            'none' => 'pt-0',
            'small' => 'pt-2',
            'medium' => 'pt-4',
            'large' => 'pt-6',
            'large-xl' => 'pt-8',
        ];
        $bottomMap = [
            'none' => 'pb-0',
            'small' => 'pb-2',
            'medium' => 'pb-4',
            'large' => 'pb-6',
            'large-xl' => 'pb-8',
        ];

        $top = $topMap[$paddingTop] ?? 'pt-0';
        $bottom = $bottomMap[$paddingBottom] ?? 'pb-0';

        return trim("{$top} {$bottom}");
    }
}
