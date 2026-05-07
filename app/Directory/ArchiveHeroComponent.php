<?php

declare(strict_types=1);

namespace App\Directory;

use App\Constants\ComponentTypes;

/**
 * Reads the per-archive hero option fields registered by
 * {@see ArchiveHeroFields} and packages them as the `$component` array
 * shape that {@see resources/views/components/image-hero.blade.php}
 * expects. Keeps the four `archive-culvers-*.blade.php` templates short
 * and identical (one factory call instead of duplicating eight lines of
 * `get_field()` + array assembly per archive).
 */
final class ArchiveHeroComponent
{
    /**
     * Build the `image_hero` component payload for the given archive prefix.
     *
     * @return array<string, mixed>
     */
    public static function fromOptions(string $prefix): array
    {
        $image = self::optionField($prefix . '_hero_image');
        $imageMobile = self::optionField($prefix . '_hero_image_mobile');
        $titleRaw = self::optionField($prefix . '_hero_title_line');
        $subtitleRaw = self::optionField($prefix . '_hero_subtitle_line');
        $toneRaw = self::optionField($prefix . '_hero_title_tone');
        $opacityRaw = self::optionField($prefix . '_hero_overlay_opacity');

        return [
            /* No background_image — image-hero renders the photo itself via
               `hero_image` and tucks under the fixed header via the
               `.image-hero--viewport` negative margin in app.css. */
            'background_type' => ComponentTypes::BACKGROUND_NONE,
            'hero_image' => is_array($image) ? $image : [],
            'hero_image_mobile' => is_array($imageMobile) ? $imageMobile : [],
            'hero_title_line' => is_string($titleRaw) ? $titleRaw : '',
            'hero_subtitle_line' => is_string($subtitleRaw) ? $subtitleRaw : '',
            'hero_title_tone' => is_string($toneRaw) && $toneRaw !== '' ? $toneRaw : 'glowleaf',
            'hero_overlay_opacity' => is_numeric($opacityRaw) ? (int) $opacityRaw : 35,
            /* Image is supplied without baked-in text — let `image-hero` render the title + subtitle overlay. */
            'hero_title_in_image' => false,
        ];
    }

    /**
     * @return mixed
     */
    private static function optionField(string $field)
    {
        if (! function_exists('get_field')) {
            return null;
        }

        return get_field($field, 'option');
    }
}
