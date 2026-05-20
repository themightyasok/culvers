<?php

/**
 * Sync homepage horizontal_scroller row (brand strip) without replacing the full homepage stack.
 *
 *     ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *       wp-content/themes/culvers/scripts/homepage-brand-scroller-sync.php
 */

use App\Helpers\HomepageFlexibleAcfAttach;
use App\Helpers\HomepageFlexibleSeedData;

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

$seedRow = null;
foreach (HomepageFlexibleSeedData::fullStack() as $row) {
    if (($row['acf_fc_layout'] ?? '') === 'horizontal_scroller') {
        $seedRow = $row;
        break;
    }
}

if ($seedRow === null) {
    \WP_CLI::error('Seed horizontal_scroller row missing.');
}

$attached = HomepageFlexibleAcfAttach::attachFlexibleRows([$seedRow]);
$seedAttached = $attached[0] ?? null;
if (! is_array($seedAttached)) {
    \WP_CLI::error('Failed to attach seed row.');
}

$updated = false;
foreach ($rows as $index => $row) {
    if (($row['acf_fc_layout'] ?? '') !== 'horizontal_scroller') {
        continue;
    }
    $rows[$index] = $seedAttached;
    $updated = true;
    break;
}

if (! $updated) {
    $rows[] = $seedAttached;
}

update_field('components', $rows, $front_id);
\WP_CLI::success(sprintf('Homepage brand scroller synced on page %d.', $front_id));
