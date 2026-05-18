<?php

/**
 * One-shot: align Senior Supervisor flexible rows with Figma 51:6450 seed
 * (drops duplicate sidebar logo; hero already carries Subway lockup).
 *
 * Usage (from app/public):
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/fix-senior-supervisor-career-layout.php
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit(1);
}

$post = get_page_by_path('senior-supervisor', OBJECT, 'culvers_career');
if (! $post instanceof WP_Post) {
    WP_CLI::error('culvers_career senior-supervisor post not found.');

    return;
}

$rows = get_field('components', $post->ID);
if (! is_array($rows)) {
    WP_CLI::error('No flexible components on senior-supervisor.');

    return;
}

$changed = false;
foreach ($rows as $index => $row) {
    if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'career_detail') {
        continue;
    }

    if (! empty($row['career_sidebar_brand_logo'])) {
        $rows[$index]['career_sidebar_brand_logo'] = null;
        $changed = true;
    }
}

if (! $changed) {
    WP_CLI::success('career_detail row already has no sidebar logo.');

    return;
}

if (update_field('components', $rows, $post->ID) === false) {
    WP_CLI::error('update_field(components) failed.');

    return;
}

WP_CLI::success('Removed career_sidebar_brand_logo from senior-supervisor career_detail row.');
