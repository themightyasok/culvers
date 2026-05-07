<?php

/**
 * Wire the assigned WordPress nav menus (primary mega + three footer locations)
 * to the live page set.
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *       wp-content/themes/culvers/scripts/nav-sync-pages.php
 *
 * Steps run in order:
 *   1. Create stub pages for legal / accessibility surfaces that don't exist yet
 *      (`/accessible-guide/`, `/modern-slavery-statement/`) so the matching
 *      menu items can resolve to a real URL.
 *   2. Run {@see App\Nav\PrimaryNavLinkSync} — rewrites every `href="#"` item
 *      in the assigned `primary_navigation` menu to its canonical archive /
 *      page / deep-link URL (companion to {@see App\Nav\ShopDirectoryNavSync}).
 *   3. Run {@see App\Nav\FooterNavLinkSync} — patches `footer_column_one` +
 *      `footer_brand_subnav` placeholders, and rebuilds `footer_column_two`
 *      ("Useful Links") to the canonical Figma seed shape.
 *
 * Re-running is idempotent — version options short-circuit work that is
 * already up to date and hand-edited URLs (anything other than `#`) are
 * left alone.
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Nav\FooterNavLinkSync;
use App\Nav\PrimaryNavLinkSync;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

$userId = (int) apply_filters('culvers_nav_sync_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

/* -----------------------------------------------------------------
 * Stub pages for legal / accessibility surfaces
 * --------------------------------------------------------------- */

/**
 * @return array{id: int, created: bool}
 */
$ensurePage = static function (string $slug, string $title, string $body): array {
    $existing = get_page_by_path($slug, OBJECT, 'page');
    if ($existing instanceof \WP_Post) {
        return ['id' => (int) $existing->ID, 'created' => false];
    }

    $insert = wp_insert_post(
        [
            'post_title' => $title,
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => $body,
        ],
        true
    );
    if (is_wp_error($insert)) {
        \WP_CLI::warning(sprintf('Failed to create stub page "%s": %s', $slug, $insert->get_error_message()));

        return ['id' => 0, 'created' => false];
    }

    return ['id' => (int) $insert, 'created' => true];
};

$stubPages = [
    [
        'slug' => 'accessible-guide',
        'title' => __('Accessible Guide', 'culvers'),
        'body' => '<p>'
            . esc_html__(
                'Shopping and days out in town shouldn’t be tricky. We are working on a full accessible '
                . 'guide for Culver Square — accessible parking, step-free routes, Changing Places '
                . 'facilities, sensory-friendly times and more. Please contact our guest services team '
                . 'in the meantime via the Contact Us page.',
                'culvers'
            )
            . '</p>',
    ],
    [
        'slug' => 'modern-slavery-statement',
        'title' => __('Modern Slavery Statement', 'culvers'),
        'body' => '<p>'
            . esc_html__(
                'Culver Square is committed to operating in an ethical and responsible way. Our full '
                . 'modern slavery and human trafficking statement is being prepared and will be '
                . 'published here in line with section 54 of the Modern Slavery Act 2015.',
                'culvers'
            )
            . '</p>',
    ],
];

foreach ($stubPages as $stub) {
    $result = $ensurePage($stub['slug'], $stub['title'], $stub['body']);
    \WP_CLI::log(sprintf(
        'Stub page %-32s ID %-4d %s',
        $stub['slug'],
        $result['id'],
        $result['created'] ? '(created)' : '(already existed)'
    ));
}

/* -----------------------------------------------------------------
 * Primary nav sync
 * --------------------------------------------------------------- */

if (! PrimaryNavLinkSync::syncAssignedPrimaryMenu()) {
    \WP_CLI::warning('Primary navigation menu has no assigned location — skipping mega-menu URL rewrite.');
} else {
    update_option(PrimaryNavLinkSync::OPTION_VER, PrimaryNavLinkSync::CURRENT_VER, true);
    \WP_CLI::log('Primary mega menu URLs rewritten.');
}

/* -----------------------------------------------------------------
 * Footer nav sync
 * --------------------------------------------------------------- */

FooterNavLinkSync::syncAllLocations();
update_option(FooterNavLinkSync::OPTION_VER, FooterNavLinkSync::CURRENT_VER, true);
\WP_CLI::log('Footer columns + legal row URLs rewritten.');

/* -----------------------------------------------------------------
 * Header utility links (read from theme mods, not menu items)
 * --------------------------------------------------------------- */

// `sections/header.blade.php` reads these two theme mods to render the small
// "Centre Map" and "Getting Here" pills next to the search button. They
// default to `#` until an editor sets them in Customize → Theme Options, so
// preset them now to the Plan My Visit page where both are documented.
$planMyVisitUrl = function_exists('home_url') ? home_url('/plan-my-visit/') : '/plan-my-visit/';
foreach (['culvers_centre_map_url', 'culvers_getting_here_url'] as $modKey) {
    $current = (string) get_theme_mod($modKey, '#');
    if ($current === '' || $current === '#') {
        set_theme_mod($modKey, $planMyVisitUrl);
        \WP_CLI::log(sprintf('Theme mod %s set to %s', $modKey, $planMyVisitUrl));
    }
}

/* -----------------------------------------------------------------
 * Cache flush — menu HTML is cached aggressively.
 * --------------------------------------------------------------- */

wp_cache_flush();

\WP_CLI::success('Nav sync complete.');
