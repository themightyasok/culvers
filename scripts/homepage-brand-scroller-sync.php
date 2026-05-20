<?php

/**
 * Sync homepage horizontal_scroller row (brand strip) without replacing the full homepage stack.
 * Patches copy/CTA fields in place and dedupes Accessorize + London logo tiles.
 *
 *     ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *       wp-content/themes/culvers/scripts/homepage-brand-scroller-sync.php
 */

if (! defined('WP_CLI') || ! WP_CLI || ! function_exists('update_field')) {
    exit(1);
}

$front_id = (int) get_option('page_on_front');
if ($front_id <= 0) {
    \WP_CLI::error('No front page set.');
}

$rows = get_field('components', $front_id);
if (! is_array($rows)) {
    $rows = [];
}

$patched = false;
foreach ($rows as $index => $row) {
    if (($row['acf_fc_layout'] ?? '') !== 'horizontal_scroller') {
        continue;
    }

    $row['scroller_preset'] = 'homepage_brands';
    $row['scroller_body_text'] = sprintf(
        '<p>%s</p><p>%s</p>',
        esc_html__('From iconic high-street labels to local independents,', 'culvers'),
        esc_html__('Culver Square brings the best of Colchester together under one roof.', 'culvers')
    );
    $row['scroller_button_text'] = __('View all', 'culvers');
    $row['scroller_button_show_arrow'] = 0;
    $row['scroller_button_link'] = [
        'url' => home_url('/shops/'),
        'title' => '',
        'target' => '',
    ];

    $items = is_array($row['scroller_items'] ?? null) ? $row['scroller_items'] : [];
    $deduped = [];
    $hasAccessorizeLondon = false;

    foreach ($items as $item) {
        if (! is_array($item)) {
            continue;
        }
        $alt = strtolower(trim((string) ($item['item_image_alt'] ?? '')));
        if (
            ! $hasAccessorizeLondon
            && (
                str_contains($alt, 'accessorize')
                || str_contains($alt, 'accessorise')
                || $alt === 'london'
            )
        ) {
            $item['item_image_alt'] = __('Accessorize London', 'culvers');
            $hasAccessorizeLondon = true;
            $deduped[] = $item;
            continue;
        }
        if ($hasAccessorizeLondon && ($alt === 'london' || str_contains($alt, 'accessorize'))) {
            continue;
        }
        $deduped[] = $item;
    }

    $row['scroller_items'] = $deduped;
    $rows[$index] = $row;
    $patched = true;
    break;
}

if (! $patched) {
    \WP_CLI::error('No horizontal_scroller row on the homepage.');
}

update_field('components', $rows, $front_id);
\WP_CLI::success(sprintf('Homepage brand scroller synced on page %d.', $front_id));
