<?php

declare(strict_types=1);

namespace App\Directory;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Single source of truth for the centre's own opening hours.
 *
 * The {@see opening_hours} flexible layout still stores per-venue hours on each
 * shop / eat & drink single (context "retailer"). For the centre-wide figure —
 * shown on the homepage, Plan My Visit and Guest Services (context "centre") —
 * the day rows are managed once here, on an ACF options page, so editing the
 * centre's hours (e.g. bank-holiday changes) updates every centre page at once
 * instead of being re-typed on each page.
 *
 * @see resources/views/components/opening-hours.blade.php (consumes {@see rows()})
 */
final class CentreOpeningHours
{
    /** ACF options page slug (also the field-group location target). */
    public const OPTION_SLUG = 'culvers-centre-hours';

    /** Repeater field name on the options page. */
    private const ROWS_FIELD = 'centre_hours_rows';

    private static bool $registered = false;

    /**
     * Weekday choices for the highlight select — mirrors the component's
     * {@see app/Components/opening_hours.php} `weekday_highlight` field.
     *
     * @return array<string, string>
     */
    public static function weekdayChoices(): array
    {
        return [
            'none' => __('None', 'culvers'),
            'sun' => __('Sunday', 'culvers'),
            'mon' => __('Monday', 'culvers'),
            'tue' => __('Tuesday', 'culvers'),
            'wed' => __('Wednesday', 'culvers'),
            'thu' => __('Thursday', 'culvers'),
            'fri' => __('Friday', 'culvers'),
            'sat' => __('Saturday', 'culvers'),
        ];
    }

    /**
     * Resolve the canonical centre hours rows.
     *
     * Returns the rows managed on the options page. When that store is empty
     * (e.g. before the first save) it falls back to the rows saved on the
     * current page so the block never renders blank during migration.
     *
     * @param array<int, array<string, mixed>> $fallback Per-page `hours_rows`.
     * @return array<int, array<string, mixed>>
     */
    public static function rows(array $fallback = []): array
    {
        if (! function_exists('get_field')) {
            return $fallback;
        }

        $rows = get_field(self::ROWS_FIELD, 'option');

        if (! is_array($rows) || $rows === []) {
            return $fallback;
        }

        return $rows;
    }

    /** Admin URL of the centre-hours options page (for editor guidance links). */
    public static function adminUrl(): string
    {
        return admin_url('admin.php?page=' . self::OPTION_SLUG);
    }

    /**
     * Register the options page and its field group once.
     *
     * Hooked from {@see \App\Fields::registerComponentFields()}.
     */
    public static function register(): void
    {
        if (self::$registered || ! function_exists('acf_add_local_field_group')) {
            return;
        }

        self::$registered = true;

        if (function_exists('acf_add_options_page')) {
            acf_add_options_page([
                'page_title' => __('Centre Opening Hours', 'culvers'),
                'menu_title' => __('Centre Hours', 'culvers'),
                'menu_slug' => self::OPTION_SLUG,
                'capability' => 'edit_theme_options',
                'redirect' => false,
                'position' => 59,
                'icon_url' => 'dashicons-clock',
                'description' => __('Centre-wide opening hours shown on the homepage, Plan My Visit and Guest Services.', 'culvers'),
            ]);
        }

        $group = new FieldsBuilder('culvers_centre_opening_hours', [
            'title' => __('Centre opening hours', 'culvers'),
        ]);

        $group->addMessage(
            'centre_hours_intro',
            __(
                'These hours are the centre\'s own opening times. They appear on every page whose '
                . '“Opening hours” block is set to <strong>Centre — site-wide hours</strong> '
                . '(homepage, Plan My Visit, Guest Services). Edit them once here and all of those '
                . 'pages update together. Individual shops and eat &amp; drink venues keep their own '
                . 'hours on their own pages.',
                'culvers'
            ),
            ['new_lines' => 'wpautop']
        );

        $repeater = $group->addRepeater(self::ROWS_FIELD, [
            'label' => __('Hours', 'culvers'),
            'instructions' => __(
                'One row per day. Set “Match weekday for highlight” so the correct row is highlighted '
                . 'on that day (site timezone). Use “None” for special rows that should never highlight.',
                'culvers'
            ),
            'min' => 0,
            'max' => 14,
            'layout' => 'table',
            'button_label' => __('Add row', 'culvers'),
        ]);

        $repeater->addText('day_label', [
            'label' => __('Day / title', 'culvers'),
            'instructions' => __('Displayed label (e.g. Monday, Easter Sunday).', 'culvers'),
            'required' => 1,
            'wrapper' => ['width' => '34'],
        ]);

        $repeater->addText('time_range', [
            'label' => __('Hours', 'culvers'),
            'instructions' => __('e.g. 9am – 5:30pm or Closed', 'culvers'),
            'required' => 1,
            'wrapper' => ['width' => '33'],
        ]);

        $repeater->addSelect('weekday_highlight', [
            'label' => __('Match weekday for highlight', 'culvers'),
            'instructions' => __('Uses the site timezone. Choose “None” for special rows.', 'culvers'),
            'choices' => self::weekdayChoices(),
            'default_value' => 'none',
            'allow_null' => 0,
            'return_format' => 'value',
            'wrapper' => ['width' => '33'],
        ]);

        $repeater->endRepeater();

        $group->setLocation('options_page', '==', self::OPTION_SLUG);

        acf_add_local_field_group($group->build());
    }
}
