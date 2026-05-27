<?php

/**
 * Theme bootstrap: hooks for assets, supports, menus, ACF, Customizer.
 *
 * PHP layout under `app/`:
 *   Assets\*                       Front-end / editor enqueue
 *   Nav\*, Customizer\*, Fields    Feature modules
 *   Support\*                     Small shared helpers (e.g. Vite dev probe)
 *   Helpers\, Services\, Components Template / flexible-content plumbing
 */

declare(strict_types=1);

namespace App;

use App\Admin\AdminMenu;
use App\Admin\HideClassicEditor;
use App\Assets\AcfFlexibleAdmin;
use App\Assets\FrontendAssets;
use App\Assets\NavMegaPreviewAdmin;
use App\Support\SiteBranding;

FrontendAssets::register();
SiteBranding::register();
AcfFlexibleAdmin::register();
NavMegaPreviewAdmin::register();
AdminMenu::register();
HideClassicEditor::register();

add_action('init', static function (): void {
    Directory\DirectoryPostTypes::register();
}, 5);

add_action('init', static function (): void {
    Directory\ShopTaxonomySeeder::maybeSeed();
    Directory\EatDrinkTaxonomySeeder::maybeSeed();
    Directory\EventTaxonomySeeder::maybeSeed();
    Directory\CareerTaxonomySeeder::maybeSeed();
}, 15);

add_action('init', static function (): void {
    Nav\NavMenuItemMeta::register();
}, 20);

add_action('init', static function (): void {
    Nav\CulverSquareFigmaPrimaryMenu::maybeInstall();
}, 120);

add_action('init', static function (): void {
    Nav\CulverSquareFigmaPrimaryMenu::maybeHydratePreviewAttachments();
}, 125);

add_action('init', static function (): void {
    Nav\ShopDirectoryNavSync::maybeSync();
}, 126);

add_action('init', static function (): void {
    Nav\CulverSquareFigmaFooterMenus::maybeInstall();
}, 122);

// Wire the rest of the mega-menu branches (Eat & Drink, Plan my visit, what's on,
// Guest Services) and the three footer locations to live URLs once their target
// pages exist. Both syncs are idempotent and version-gated.
add_action('init', static function (): void {
    Nav\PrimaryNavLinkSync::maybeSync();
}, 127);

add_action('init', static function (): void {
    Nav\FooterNavLinkSync::maybeSync();
}, 128);

add_action('init', static function (): void {
    Legal\PolicyPageInstaller::maybeSeed();
}, 40);

add_action('customize_register', static function (\WP_Customize_Manager $wp_customize): void {
    Customizer\SiteShortcutsCustomizer::register($wp_customize);
    Customizer\FooterCustomizer::register($wp_customize);
    Customizer\GoogleMapsCustomizer::register($wp_customize);
});

add_action('rest_api_init', static function (): void {
    Travel\TravelCalculatorEndpoint::register();
    Contact\ContactFormEndpoint::register();
    Search\SearchEndpoint::register();
});

Search\MainSearch::register();

add_filter('culvers_default_full_width_components', static function (array $layouts): array {
    $layouts[] = 'hero_slider';
    $layouts[] = 'image_hero';

    return $layouts;
});

add_filter('culvers_full_width_components', static function (array $layouts): array {
    $layouts[] = 'image_hero';

    return $layouts;
});

// Authoritative chrome defaults: see App\Helpers\ComponentLayoutChrome.
add_filter('culvers_component_layout_chrome', static function (array $chrome, string $layout, array $component): array {
    if ($layout === 'opening_hours' && \App\Helpers\OpeningHoursContext::isRetailer($component)) {
        return array_merge($chrome, [
            'component_width' => 'full',
        ]);
    }

    return $chrome;
}, 10, 3);

add_action('after_setup_theme', static function (): void {
    remove_theme_support('block-templates');
    remove_theme_support('core-block-patterns');

    register_nav_menus([
        'primary_navigation' => __('Primary navigation', 'culvers'),
        'footer_column_one' => __('Footer column 3 — What’s Here', 'culvers'),
        'footer_column_two' => __('Footer column 4 — Useful Links', 'culvers'),
        'footer_brand_subnav' => __('Footer — legal links (under wordmark)', 'culvers'),
    ]);

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'height' => 80,
        'width' => 400,
        'flex-height' => true,
        'flex-width' => true,
    ]);
    add_theme_support('responsive-embeds');
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    add_theme_support('editor-styles');
    $td = get_template_directory();
    if (file_exists($td . '/dist/css/app.css')) {
        add_editor_style('dist/css/app.css');
    } elseif (file_exists($td . '/css/app.css')) {
        add_editor_style('css/app.css');
    } elseif (file_exists($td . '/app.css')) {
        add_editor_style('app.css');
    }
}, 20);

add_filter(
    'nav_menu_link_attributes',
    static function (array $atts, \WP_Post $unused_item, mixed $args, int $unused_depth): array {
        unset($unused_item, $unused_depth);
        $loc = '';
        if (is_object($args) && isset($args->theme_location) && is_string($args->theme_location)) {
            $loc = $args->theme_location;
        }
        if ($loc === '' || ! str_starts_with($loc, 'footer')) {
            return $atts;
        }

        $extra = match ($loc) {
            'footer_brand_subnav' => 'footer-nav__link--legal',
            'footer_column_one', 'footer_column_two' => 'footer-nav__link-col',
            default => 'footer-nav__link',
        };

        $existingClass = isset($atts['class']) && is_string($atts['class']) ? $atts['class'] : '';
        $atts['class'] = $existingClass !== '' ? trim($existingClass . ' ' . $extra) : $extra;

        return $atts;
    },
    10,
    4
);

add_action('acf/init', static function (): void {
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    try {
        new Fields();
    } catch (\Throwable $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[culvers][acf] ' . $e->getMessage());
        }
    }
}, 20);

add_filter('acf/settings/save_json', static fn (): string => get_stylesheet_directory() . '/acf-json');

add_filter(
    'acf/settings/load_json',
    static function (array $paths): array {
        $paths[] = get_stylesheet_directory() . '/acf-json';

        return $paths;
    }
);

add_filter('intermediate_image_sizes_advanced', static function (array $sizes): array {
    unset($sizes['medium'], $sizes['medium_large'], $sizes['large']);

    return $sizes;
});

add_action('init', static function (): void {
    remove_image_size('1536x1536');
    remove_image_size('2048x2048');
}, 99);
