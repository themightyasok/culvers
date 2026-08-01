<?php

/**
 * Staging ops for Stu feedback batch (2026-07):
 *  - Fixed Latest Offers mega preview + remove top-level Latest Offers
 *  - Homepage video block → Image mode (use existing poster as still)
 *  - Repair shop logo attachment metadata when 0×0
 *  - Create David + Marie as administrators
 *
 *   wp eval-file wp-content/themes/culvers/scripts/stu-feedback-2026-07-staging.php
 *
 * @phpstan-ignore-file
 */

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI (wp eval-file).\n");
    exit(1);
}

if (! function_exists('get_field') || ! function_exists('update_field')) {
    WP_CLI::error('ACF required.');
}

// ── 1. Nav: remove top-level Latest Offers; set fixed preview under What's on ─
$locs = get_nav_menu_locations();
$menuId = (int) ($locs['primary_navigation'] ?? 0);
if ($menuId <= 0) {
    WP_CLI::warning('No primary_navigation menu — skipping nav updates.');
} else {
    $items = wp_get_nav_menu_items($menuId) ?: [];
    $whatsOnId = 0;
    $offersUnderWhatsOn = 0;
    $topLevelOffers = [];

    foreach ($items as $item) {
        $title = (string) $item->title;
        $parent = (int) $item->menu_item_parent;
        if (stripos($title, "what's on") !== false && $parent === 0) {
            $whatsOnId = (int) $item->ID;
        }
        if (stripos($title, 'latest offers') !== false) {
            if ($parent === 0) {
                $topLevelOffers[] = (int) $item->ID;
            } else {
                $offersUnderWhatsOn = (int) $item->ID;
            }
        }
    }

    foreach ($topLevelOffers as $itemId) {
        if ($offersUnderWhatsOn > 0) {
            wp_delete_post($itemId, true);
            WP_CLI::log("Removed top-level Latest Offers menu item #{$itemId}");
        }
    }

    if ($offersUnderWhatsOn <= 0 && $whatsOnId > 0) {
        $offersUnderWhatsOn = (int) wp_update_nav_menu_item($menuId, 0, [
            'menu-item-title' => 'Latest Offers',
            'menu-item-url' => home_url('/latest-offers/'),
            'menu-item-status' => 'publish',
            'menu-item-parent-id' => $whatsOnId,
            'menu-item-type' => 'custom',
        ]);
        WP_CLI::log("Created Latest Offers under What's on #{$offersUnderWhatsOn}");
    }

    if ($offersUnderWhatsOn > 0) {
        $candidates = [
            'Culver_Square_Whats-on_2',
            'Culver_Square_Whats-on',
            'culver-square-mega-a1b7ce3dbcfd0aab',
        ];
        $previewId = 0;
        foreach ($candidates as $needle) {
            $q = new WP_Query([
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'post_mime_type' => 'image',
                'posts_per_page' => 1,
                'fields' => 'ids',
                's' => $needle,
                'no_found_rows' => true,
            ]);
            if ($q->posts !== []) {
                $previewId = (int) $q->posts[0];
                break;
            }
        }
        if ($previewId <= 0) {
            global $wpdb;
            $previewId = (int) $wpdb->get_var(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_type='attachment' AND guid LIKE '%Whats-on%'
                 ORDER BY ID DESC LIMIT 1"
            );
        }
        if ($previewId > 0) {
            update_post_meta($offersUnderWhatsOn, '_culvers_mega_preview_attachment_id', $previewId);
            delete_post_meta($offersUnderWhatsOn, '_culvers_mega_preview_url');
            WP_CLI::log("Set Latest Offers #{$offersUnderWhatsOn} mega preview → attachment #{$previewId}");
        } else {
            WP_CLI::warning('No suitable Offers mega preview attachment found.');
        }
    }
}

// ── 2. Homepage video block → Image mode using existing poster ───────────────
$homeId = (int) get_option('page_on_front');
$components = get_field('components', $homeId);
if (! is_array($components)) {
    WP_CLI::warning("No components on front page #{$homeId}");
} else {
    $updated = false;
    foreach ($components as $i => $row) {
        if (($row['acf_fc_layout'] ?? '') !== 'video_block') {
            continue;
        }
        $poster = $row['video_poster'] ?? null;
        $posterId = 0;
        if (is_array($poster)) {
            $posterId = (int) ($poster['ID'] ?? $poster['id'] ?? 0);
        } elseif (is_numeric($poster)) {
            $posterId = (int) $poster;
        }
        $components[$i]['video_media_type'] = 'image';
        if ($posterId > 0) {
            $components[$i]['video_image'] = $posterId;
        }
        $updated = true;
        WP_CLI::log("Homepage video_block → image mode (still=#{$posterId})");
        break;
    }
    if ($updated) {
        update_field('components', $components, $homeId);
    } else {
        WP_CLI::warning('No video_block on homepage.');
    }
}

// ── 3. Repair 0×0 shop logo metadata ─────────────────────────────────────────
$shops = get_posts([
    'post_type' => ['culvers_shop', 'culvers_eat_drink'],
    'numberposts' => -1,
    'post_status' => 'publish',
]);
$repaired = 0;
foreach ($shops as $shop) {
    foreach (['shop_logo', 'eat_drink_logo'] as $field) {
        $logo = get_field($field, $shop->ID);
        $lid = is_array($logo) ? (int) ($logo['ID'] ?? 0) : (int) $logo;
        if ($lid <= 0) {
            continue;
        }
        $meta = wp_get_attachment_metadata($lid);
        $w = (int) ($meta['width'] ?? 0);
        $h = (int) ($meta['height'] ?? 0);
        if ($w > 0 && $h > 0) {
            continue;
        }
        $path = get_attached_file($lid);
        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            continue;
        }
        $size = @getimagesize($path);
        if (! is_array($size) || (int) ($size[0] ?? 0) <= 0) {
            continue;
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $newMeta = wp_generate_attachment_metadata($lid, $path);
        if (is_array($newMeta) && $newMeta !== []) {
            wp_update_attachment_metadata($lid, $newMeta);
            $repaired++;
            WP_CLI::log("Repaired logo metadata #{$lid} for {$shop->post_title}");
        }
    }
}
WP_CLI::log("Logo metadata repairs: {$repaired}");

// ── 4. Admin users ───────────────────────────────────────────────────────────
$newUsers = [
    [
        'user_login' => 'david.robertson',
        'user_email' => 'david.robertson@culversquare.co.uk',
        'display_name' => 'David Robertson',
    ],
    [
        'user_login' => 'marie.gillard',
        'user_email' => 'marie.gillard@culversquare.co.uk',
        'display_name' => 'Marie Gillard',
    ],
];

foreach ($newUsers as $spec) {
    $existing = get_user_by('email', $spec['user_email']);
    if ($existing instanceof WP_User) {
        $existing->set_role('administrator');
        WP_CLI::log("User exists {$spec['user_email']} (#{$existing->ID}) — role set to administrator");
        continue;
    }
    $login = $spec['user_login'];
    if (username_exists($login)) {
        $login .= '_cs';
    }
    $password = wp_generate_password(24, true, true);
    $userId = wp_insert_user([
        'user_login' => $login,
        'user_email' => $spec['user_email'],
        'user_pass' => $password,
        'display_name' => $spec['display_name'],
        'role' => 'administrator',
    ]);
    if (is_wp_error($userId)) {
        WP_CLI::warning("Failed to create {$spec['user_email']}: " . $userId->get_error_message());
        continue;
    }
    WP_CLI::log("Created administrator #{$userId} {$spec['user_email']} (login={$login})");
    $reset = retrieve_password($login);
    if ($reset === true) {
        WP_CLI::log("Password reset email requested for {$login}");
    } else {
        WP_CLI::warning("Could not send reset for {$login} — temp password: {$password}");
    }
}

WP_CLI::success('Stu feedback staging ops complete.');
