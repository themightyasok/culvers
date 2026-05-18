<?php

declare(strict_types=1);

namespace App\Directory;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Shared builder for directory archive hero option fields.
 *
 * Every CPT archive (`/shops/`, `/eat-drink/`, `/whats-on/`, `/careers/`)
 * uses the same {@see resources/views/components/image-hero.blade.php}
 * component — the static "header hero" Figma pattern (51:9360, 1440×646
 * band) with a configurable image, glowleaf title, white spaced subtitle,
 * and overlay strength. Sized intentionally smaller than the homepage
 * `hero_slider` (which fills the viewport) so archive pages read as a
 * banner and the listing below stays above the fold on desktop.
 *
 * @internal Used by {@see ShopArchiveFields}, {@see EatDrinkArchiveFields},
 * {@see EventArchiveFields}, {@see CareerArchiveFields}.
 */
final class ArchiveHeroFields
{
    /** @var array<string, true> */
    private static array $optionsPagesRegistered = [];

    /**
     * Register an options page once per slug + the matching field group.
     *
     * @param array{
     *     option_slug: string,
     *     menu_title: string,
     *     page_title: string,
     *     description: string,
     *     icon: string,
     *     position: int,
     *     group_key: string,
     *     group_title: string,
     *     field_prefix: string,
     *     hero_message_title: string,
     *     hero_message_body: string,
     *     intro_field_label: string,
     *     intro_field_instructions: string,
     *     extra?: callable(FieldsBuilder): void
     * } $config
     */
    public static function register(array $config): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        if (function_exists('acf_add_options_page') && ! isset(self::$optionsPagesRegistered[$config['option_slug']])) {
            acf_add_options_page([
                'page_title' => $config['page_title'],
                'menu_title' => $config['menu_title'],
                'menu_slug' => $config['option_slug'],
                'capability' => 'edit_theme_options',
                'redirect' => false,
                'position' => $config['position'],
                'icon_url' => $config['icon'],
                'description' => $config['description'],
            ]);
            self::$optionsPagesRegistered[$config['option_slug']] = true;
        }

        $group = new FieldsBuilder($config['group_key'], [
            'title' => $config['group_title'],
        ]);

        $group->addMessage(
            $config['hero_message_title'],
            $config['hero_message_body'],
            ['new_lines' => 'wpautop']
        );

        $prefix = $config['field_prefix'];

        $group->addImage($prefix . '_hero_image', [
            'label' => __('Hero image (desktop)', 'culvers'),
            'instructions' => __(
                'Wide lifestyle / storefront shot. Figma band is 1440×646 (≈half-viewport on desktop).',
                'culvers'
            ),
            'return_format' => 'array',
            'preview_size' => 'large',
        ]);

        $group->addImage($prefix . '_hero_image_mobile', [
            'label' => __('Hero image (mobile, optional)', 'culvers'),
            'instructions' => __('Tighter crop for small screens. Falls back to desktop image when empty.', 'culvers'),
            'return_format' => 'array',
            'preview_size' => 'medium',
        ]);

        $group->addText($prefix . '_hero_title_line', [
            'label' => __('Title line', 'culvers'),
            'instructions' => __(
                'Large serif headline (e.g. "Good brews, Great bites."). Renders at 96px on desktop in the brand glowleaf colour.',
                'culvers'
            ),
            'wrapper' => ['width' => '50'],
        ]);

        $group->addSelect($prefix . '_hero_title_tone', [
            'label' => __('Title colour', 'culvers'),
            'instructions' => __(
                'Defaults to glowleaf (Figma standard). Use white only when the photo behind pushes glowleaf out of contrast.',
                'culvers'
            ),
            'choices' => [
                'glowleaf' => __('Glowleaf (default)', 'culvers'),
                'white' => __('White', 'culvers'),
                'lighter-cream' => __('Lighter cream', 'culvers'),
            ],
            'default_value' => 'glowleaf',
            'allow_null' => 0,
            'wrapper' => ['width' => '50'],
        ]);

        $group->addTextarea($prefix . '_hero_subtitle_line', [
            'label' => __('Subtitle line', 'culvers'),
            'instructions' => __(
                'Spaced uppercase sans line under the title (e.g. "From quick bites to long lunches"). Renders at 20px / SemiBold / 4px tracking on desktop, on its own line.',
                'culvers'
            ),
            'rows' => 2,
            'new_lines' => 'br',
        ]);

        $group->addNumber($prefix . '_hero_overlay_opacity', [
            'label' => __('Image overlay darkness', 'culvers'),
            'instructions' => __(
                'Solid black overlay opacity on the hero image (0–85). Figma default is 20 — push higher only when text contrast on a busy photo demands it.',
                'culvers'
            ),
            'default_value' => 35,
            'min' => 0,
            'max' => 85,
            'step' => 1,
            'append' => '%',
        ]);

        $group->addTextarea($prefix . '_intro_copy', [
            'label' => $config['intro_field_label'],
            'instructions' => $config['intro_field_instructions'],
            'rows' => 4,
            'new_lines' => 'wpautop',
        ]);

        $group->addImage($prefix . '_footer_newsletter_image', [
            'label' => __('Footer newsletter — background image', 'culvers'),
            'instructions' => __(
                'Used on this directory’s archive and on singles until a post overrides it '
                . '(sidebar “Footer newsletter” when editing a single). Leave empty to use the site-wide '
                . 'Customizer image under Appearance → Customize → Culver Square footer.',
                'culvers'
            ),
            'return_format' => 'id',
            'preview_size' => 'large',
        ]);

        if (isset($config['extra'])) {
            /* PHPDoc constrains `extra` to `callable(FieldsBuilder): void`; no
               extra `is_callable()` guard required. */
            ($config['extra'])($group);
        }

        $group->setLocation('options_page', '==', $config['option_slug']);

        acf_add_local_field_group($group->build());
    }
}
