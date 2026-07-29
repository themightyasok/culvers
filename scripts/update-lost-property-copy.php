<?php

/**
 * Update Guest Services "Lost Property" accordion body copy.
 *
 *   wp eval-file wp-content/themes/culvers/scripts/update-lost-property-copy.php
 */

$newBody = '<p>If you have lost an item while visiting the shopping centre, please visit the Management Office to enquire about lost property or contact us using the link below.</p>';

$guestId = (int) (get_page_by_path('guest-services')->ID ?? 0);
if ($guestId <= 0) {
    fwrite(STDERR, "Guest services page not found.\n");
    exit(1);
}

$components = get_field('components', $guestId);
if (! is_array($components)) {
    fwrite(STDERR, "No components.\n");
    exit(1);
}

$updated = false;
foreach ($components as $i => $row) {
    if (($row['acf_fc_layout'] ?? '') !== 'text_image_slider') {
        continue;
    }
    foreach ($row['tis_items'] as $j => $item) {
        if (strcasecmp(trim((string) ($item['item_label'] ?? '')), 'Lost Property') !== 0) {
            continue;
        }
        $components[$i]['tis_items'][$j]['item_body'] = $newBody;
        $updated = true;
    }
}

if (! $updated) {
    fwrite(STDERR, "Lost Property row not found.\n");
    exit(1);
}

update_field('components', $components, $guestId);

foreach (get_field('components', $guestId) as $row) {
    if (($row['acf_fc_layout'] ?? '') !== 'text_image_slider') {
        continue;
    }
    foreach ($row['tis_items'] as $item) {
        if (strcasecmp(trim((string) ($item['item_label'] ?? '')), 'Lost Property') !== 0) {
            continue;
        }
        if (class_exists('WP_CLI')) {
            WP_CLI::log(wp_strip_all_tags((string) $item['item_body']));
            WP_CLI::success('Lost Property accordion updated on #' . $guestId);
        } else {
            echo wp_strip_all_tags((string) $item['item_body']) . "\n";
        }
    }
}
