<?php

/**
 * One-shot: write the canonical Figma primary menu INTO WordPress so Appearance → Menus
 * matches what the header renders. After this runs, the theme reads the menu as-is (order,
 * nesting, URLs) — drag items in WP and the front end follows.
 *
 * Fixes flat menus where Shop categories (Fashion, Jewellery, …) were top-level instead of
 * under Shop, wrong order, or stray Careers in the primary bar.
 *
 * Safe to re-run: deletes every item in the assigned primary_navigation menu, then
 * recreates the canonical Figma tree + canonical URLs + mega preview meta.
 *
 * Local:
 *   cd app/public && ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/rebuild-primary-nav-menu.php
 *
 * Dry run (counts only, no writes):
 *   ... rebuild-primary-nav-menu.php dry-run
 *
 * 20i staging / live (after theme file is deployed):
 *   ssh 20i-culvers 'cd public_html && php83 /usr/local/bin/wp eval-file \
 *     wp-content/themes/culvers/scripts/rebuild-primary-nav-menu.php'
 *
 * Figma primary bar order: Shop → Eat & Drink → Plan my visit → what's on → Guest Services.
 * Careers is footer-only (not a top-level pill). Centre Map / Getting Here are hardcoded
 * header utilities; Plan my visit submenu still lists them for mega-panel parity.
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Nav\CulverSquareFigmaPrimaryMenu;
use App\Nav\PrimaryNav;

if (! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI: wp eval-file wp-content/themes/culvers/scripts/rebuild-primary-nav-menu.php\n");
    exit(1);
}

$cliArgs = $args ?? [];
$dryRun = in_array('dry-run', $cliArgs, true) || in_array('--dry-run', $cliArgs, true);

$userId = (int) apply_filters('culvers_rebuild_primary_nav_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

$result = CulverSquareFigmaPrimaryMenu::rebuildAssignedPrimaryMenu($dryRun);

if ($result['menu_id'] <= 0) {
    WP_CLI::error('No primary navigation menu could be resolved or created.');
}

$menu = wp_get_nav_menu_object($result['menu_id']);
$menuName = $menu instanceof WP_Term ? $menu->name : (string) $result['menu_id'];

if ($dryRun) {
    WP_CLI::log(sprintf(
        '[dry-run] Would rebuild menu "%s" (ID %d): delete %d item(s), create %d top-level + %d children.',
        $menuName,
        $result['menu_id'],
        $result['deleted'],
        $result['top_level'],
        $result['children']
    ));
    WP_CLI::success('Dry run complete — no database writes.');
    exit(0);
}

WP_CLI::log(sprintf(
    'Rebuilt menu "%s" (ID %d): removed %d item(s), wrote %d top-level + %d children.',
    $menuName,
    $result['menu_id'],
    $result['deleted'],
    $result['top_level'],
    $result['children']
));

WP_CLI::log('Front-end tree (after PrimaryNav normalisation):');
foreach (PrimaryNav::tree('primary_navigation') as $branch) {
    WP_CLI::log(sprintf('  %s → %s', $branch['title'], $branch['url']));
    foreach ($branch['children'] as $child) {
        WP_CLI::log(sprintf('    - %s → %s', $child['title'], $child['url']));
    }
}

WP_CLI::success(
    'Primary menu saved in WordPress. The header now renders this menu as-is — edit order and nesting under Appearance → Menus.'
);
