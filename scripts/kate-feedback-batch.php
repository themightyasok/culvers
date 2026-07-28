<?php

/**
 * Kate feedback batch (2026-06): CMS/data updates for staging or local.
 *
 *   cd app/public && ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/kate-feedback-batch.php dry-run
 *
 *   ssh 20i-culvers 'cd public_html && php83 /usr/local/bin/wp eval-file \
 *     wp-content/themes/culvers/scripts/kate-feedback-batch.php'
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Customizer\FooterCustomizer;
use App\Directory\ShopLiveSync;

if (! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI: wp eval-file wp-content/themes/culvers/scripts/kate-feedback-batch.php\n");
    exit(1);
}

if (! function_exists('get_field') || ! function_exists('update_field')) {
    WP_CLI::error('ACF is required.');
}

$cliArgs = $args ?? [];
$dryRun = in_array('dry-run', $cliArgs, true) || in_array('--dry-run', $cliArgs, true);

$userId = (int) apply_filters('culvers_kate_feedback_batch_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

const FB_URL = 'https://www.facebook.com/Culversquarecolchester';
const FOOTER_ADDRESS = "Culver Square Shopping Centre\n7A Culver Square\nColchester, Essex\nCO1 1WF";

/**
 * @param callable():void $write
 */
function culvers_kate_run(bool $dryRun, string $label, callable $write): void
{
    if ($dryRun) {
        WP_CLI::log("[dry-run] {$label}");
        return;
    }

    $write();
    WP_CLI::log("✓ {$label}");
}

// ── 1. Homepage: disable Shop / Eat & Drink buttons on three_card_block ──────

$homeId = (int) get_option('page_on_front');
if ($homeId <= 0) {
    WP_CLI::warning('No front page set — skipping homepage button toggle.');
} else {
    $components = get_field('components', $homeId);
    if (! is_array($components)) {
        WP_CLI::warning("Home #{$homeId} has no components field.");
    } else {
        $changed = false;
        foreach ($components as $i => $row) {
            if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'three_card_block') {
                continue;
            }
            if (empty($row['cards_show_directory_buttons'])) {
                continue;
            }
            $components[$i]['cards_show_directory_buttons'] = 0;
            $changed = true;
            WP_CLI::log(sprintf(
                'Home three_card_block row %d: cards_show_directory_buttons off',
                $i
            ));
        }

        culvers_kate_run($dryRun, "Home #{$homeId} directory buttons off", static function () use ($homeId, $components, $changed): void {
            if ($changed) {
                update_field('components', $components, $homeId);
            }
        });
    }
}

// ── 2. Nav: Latest Offers under What's On only (remove top-level duplicate) ─

$locations = get_nav_menu_locations();
$menuId = isset($locations['primary_navigation']) ? (int) $locations['primary_navigation'] : 0;

if ($menuId <= 0) {
    WP_CLI::warning('No primary_navigation menu — skipping nav update.');
} else {
    $items = wp_get_nav_menu_items($menuId);
    $whatsOnId = 0;
    $hasChildOffers = false;
    /** @var list<int> $topLevelOffersIds */
    $topLevelOffersIds = [];

    if (is_array($items)) {
        foreach ($items as $item) {
            if (! $item instanceof WP_Post) {
                continue;
            }
            $titleLower = strtolower(trim((string) $item->post_title));
            $isOffers = str_contains($titleLower, 'latest offers');
            if ($titleLower === "what's on" && (int) $item->menu_item_parent === 0) {
                $whatsOnId = (int) $item->ID;
            }
            if ($isOffers && (int) $item->menu_item_parent === 0) {
                $topLevelOffersIds[] = (int) $item->ID;
            }
            if ($isOffers && (int) $item->menu_item_parent === $whatsOnId && $whatsOnId > 0) {
                $hasChildOffers = true;
            }
        }
    }

    foreach ($topLevelOffersIds as $itemId) {
        culvers_kate_run($dryRun, "Remove top-level Latest Offers menu item #{$itemId}", static function () use ($itemId): void {
            wp_delete_post($itemId, true);
        });
    }

    if ($whatsOnId <= 0) {
        WP_CLI::warning('Could not find What\'s On parent in primary menu.');
    } elseif ($hasChildOffers) {
        WP_CLI::log('Latest Offers already under What\'s On.');
    } else {
        $offersUrl = home_url('/latest-offers/');
        culvers_kate_run($dryRun, 'Add Latest Offers under What\'s On', static function () use ($menuId, $whatsOnId, $offersUrl): void {
            wp_update_nav_menu_item($menuId, 0, [
                'menu-item-title' => __('Latest Offers', 'culvers'),
                'menu-item-url' => $offersUrl,
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
                'menu-item-parent-id' => $whatsOnId,
            ]);
        });
    }
}

// ── 3. Facebook URL ─────────────────────────────────────────────────────────

culvers_kate_run($dryRun, 'Facebook theme mod → Culversquarecolchester', static function (): void {
    set_theme_mod(FooterCustomizer::MOD_FACEBOOK_URL, FB_URL);
});

// ── 4. Footer / contact address (contact reads same theme mod) ───────────────

culvers_kate_run($dryRun, 'Footer getting-here address → CO1 1WF', static function (): void {
    set_theme_mod(FooterCustomizer::MOD_GETTING_HERE_ADDRESS, FOOTER_ADDRESS);
});

// ── 5. Commercialisation: remove Cushman & Wakefield agent row ──────────────

$commPage = get_page_by_path('commercialisation-opportunities');
if (! $commPage instanceof WP_Post) {
    WP_CLI::warning('commercialisation-opportunities page not found.');
} else {
    $commId = (int) $commPage->ID;
    $components = get_field('components', $commId);
    if (! is_array($components)) {
        WP_CLI::warning("Commercialisation #{$commId} has no components.");
    } else {
        $commChanged = false;
        foreach ($components as $i => $row) {
            if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'leasing_agent_grid') {
                continue;
            }
            $agents = $row['leasing_agents'] ?? [];
            if (! is_array($agents)) {
                continue;
            }
            $filtered = array_values(array_filter($agents, static function ($agent): bool {
                if (! is_array($agent)) {
                    return true;
                }
                $name = strtolower((string) ($agent['agent_name'] ?? ''));
                return ! str_contains($name, 'cushman');
            }));
            if (count($filtered) !== count($agents)) {
                $components[$i]['leasing_agents'] = $filtered;
                $commChanged = true;
                WP_CLI::log('Removed Cushman agent from commercialisation leasing_agent_grid.');
            }
        }

        culvers_kate_run($dryRun, "Commercialisation #{$commId} Cushman removed", static function () use ($commId, $components, $commChanged): void {
            if ($commChanged) {
                update_field('components', $components, $commId);
            }
        });
    }
}

// ── 6. Hotel Chocolat → Cafés filter ─────────────────────────────────────────

$hotel = get_posts([
    'post_type' => 'culvers_shop',
    'name' => 'hotel-chocolat',
    'post_status' => 'any',
    'numberposts' => 1,
    'fields' => 'ids',
]);

if ($hotel === []) {
    $hotel = get_posts([
        'post_type' => 'culvers_shop',
        'p' => 773,
        'post_status' => 'any',
        'numberposts' => 1,
        'fields' => 'ids',
    ]);
}

$hotelId = (int) ($hotel[0] ?? 0);
if ($hotelId <= 0) {
    WP_CLI::warning('Hotel Chocolat shop not found.');
} else {
    culvers_kate_run($dryRun, "Hotel Chocolat #{$hotelId} eat-drink filter = cafes", static function () use ($hotelId): void {
        update_field('shop_also_eat_drink', 1, $hotelId);
        update_field('shop_eat_drink_filter_types', ['cafes'], $hotelId);
    });
}

// ── 7. Opening hours summary sync (all shops) ───────────────────────────────

$shopIds = get_posts([
    'post_type' => 'culvers_shop',
    'post_status' => 'publish',
    'numberposts' => -1,
    'fields' => 'ids',
    'orderby' => 'ID',
    'order' => 'ASC',
]);

$synced = 0;
foreach ($shopIds as $shopId) {
    $shopId = (int) $shopId;
    $components = get_field('components', $shopId);
    if (! is_array($components)) {
        continue;
    }

    if ($dryRun) {
        foreach ($components as $row) {
            if (is_array($row) && ($row['acf_fc_layout'] ?? '') === 'opening_hours') {
                ++$synced;
                break;
            }
        }
        continue;
    }

    ShopLiveSync::syncHoursSummaryFromComponents($shopId, $components);
    ++$synced;
}

WP_CLI::log($dryRun
    ? "[dry-run] Would sync opening_hours_summary on {$synced} shop(s) with opening_hours row."
    : "Synced opening_hours_summary on {$synced} shop(s).");

if ($dryRun) {
    WP_CLI::success('Dry run complete — no database writes.');
} else {
    WP_CLI::success('Kate feedback batch applied.');
}
