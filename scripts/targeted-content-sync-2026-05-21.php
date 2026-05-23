<?php

/**
 * Targeted flexible-content sync — touches ONLY the posts/fields listed below.
 *
 * Do NOT use pages-and-singles-populate.php for this work; that rewrites every
 * seed-defined page and five directory singles.
 *
 * Default (no extra args): culvers_event easter-egg-hunt — full `components` stack
 * from CptSinglesFlexibleSeedData::easterEggHunt() (drops event_meta, Figma 51:6386).
 *
 * Optional flag: guest-services — patches ONLY the Lost Property text-image-slider
 * repeater row (body + CTA). Images and other accordion rows are left unchanged.
 *
 * Usage (from app/public):
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/targeted-content-sync-2026-05-21.php
 *
 * Dry run:
 *   ... targeted-content-sync-2026-05-21.php dry-run
 *
 * Both targets:
 *   ... targeted-content-sync-2026-05-21.php easter-egg-hunt guest-services
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Helpers\CptSinglesFlexibleSeedData;
use App\Helpers\HomepageFlexibleAcfAttach;
use App\Helpers\PagesFlexibleSeedData;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

if (! function_exists('update_field') || ! function_exists('get_field')) {
    WP_CLI::error('ACF is required (get_field / update_field missing).');
}

$cliArgs = $args ?? [];
$dryRun = in_array('dry-run', $cliArgs, true)
    || in_array('--dry-run', $cliArgs, true);

$runEaster = true;
$runGuestServices = false;

foreach ($cliArgs as $arg) {
    if (! is_string($arg) || $arg === '' || $arg === 'dry-run' || $arg === '--dry-run') {
        continue;
    }
    if ($arg === 'easter-egg-hunt') {
        $runEaster = true;
        continue;
    }
    if ($arg === 'guest-services') {
        $runGuestServices = true;
        continue;
    }
    WP_CLI::error(sprintf('Unknown target "%s". Use: easter-egg-hunt, guest-services, dry-run.', $arg));
}

// Explicit-only mode: if caller names targets, do not run unnamed defaults.
$namedTargets = array_values(array_filter(
    $cliArgs,
    static fn ($a) => is_string($a) && in_array($a, ['easter-egg-hunt', 'guest-services'], true)
));
if ($namedTargets !== []) {
    $runEaster = in_array('easter-egg-hunt', $namedTargets, true);
    $runGuestServices = in_array('guest-services', $namedTargets, true);
}

/**
 * @return array{item_body: string, item_cta_label: string, item_cta_url: string}|null
 */
function culvers_lost_property_tis_patch_from_seed(): ?array
{
    foreach (PagesFlexibleSeedData::guestServicesPage() as $row) {
        if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'text_image_slider') {
            continue;
        }
        $items = $row['tis_items'] ?? null;
        if (! is_array($items)) {
            return null;
        }
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['item_label'] ?? ''));
            if (strcasecmp($label, 'Lost Property') !== 0) {
                continue;
            }

            return [
                'item_body' => (string) ($item['item_body'] ?? ''),
                'item_cta_label' => (string) ($item['item_cta_label'] ?? ''),
                'item_cta_url' => (string) ($item['item_cta_url'] ?? ''),
            ];
        }
    }

    return null;
}

if ($runEaster) {
    $post = get_page_by_path('easter-egg-hunt', OBJECT, 'culvers_event');
    if (! $post instanceof WP_Post) {
        WP_CLI::error('culvers_event easter-egg-hunt not found.');
    }

    $before = get_field('components', $post->ID);
    $beforeLayouts = is_array($before)
        ? array_values(array_filter(array_map(
            static fn ($r) => is_array($r) ? (string) ($r['acf_fc_layout'] ?? '') : '',
            $before
        )))
        : [];

    $rows = HomepageFlexibleAcfAttach::attachFlexibleRows(CptSinglesFlexibleSeedData::easterEggHunt());
    $afterLayouts = array_map(static fn ($r) => (string) ($r['acf_fc_layout'] ?? ''), $rows);

    if ($dryRun) {
        WP_CLI::log(sprintf(
            '[dry-run] easter-egg-hunt #%d: layouts %s → %s',
            $post->ID,
            implode(', ', $beforeLayouts) ?: '(empty)',
            implode(', ', $afterLayouts)
        ));
    } else {
        $result = update_field('components', $rows, $post->ID);
        if ($result === false) {
            WP_CLI::warning(sprintf('update_field returned false for easter-egg-hunt #%d — verify in admin.', $post->ID));
        }
        WP_CLI::success(sprintf(
            'easter-egg-hunt #%d: %d layouts (%s).',
            $post->ID,
            count($rows),
            implode(', ', $afterLayouts)
        ));
    }
}

if ($runGuestServices) {
    $page = get_page_by_path('guest-services', OBJECT, 'page');
    if (! $page instanceof WP_Post) {
        WP_CLI::error('page guest-services not found.');
    }

    $patch = culvers_lost_property_tis_patch_from_seed();
    if ($patch === null || $patch['item_cta_label'] === '') {
        WP_CLI::error('Seed Lost Property row (body + CTA) not found.');
    }

    $rows = get_field('components', $page->ID);
    if (! is_array($rows) || $rows === []) {
        WP_CLI::error('No flexible components on guest-services.');
    }

    $mutated = false;
    $patchedIndex = null;
    foreach ($rows as $layoutIndex => $row) {
        if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'text_image_slider') {
            continue;
        }
        $items = $row['tis_items'] ?? null;
        if (! is_array($items)) {
            continue;
        }
        foreach ($items as $itemIndex => $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['item_label'] ?? ''));
            if (strcasecmp($label, 'Lost Property') !== 0) {
                continue;
            }
            $rows[$layoutIndex]['tis_items'][$itemIndex]['item_body'] = $patch['item_body'];
            $rows[$layoutIndex]['tis_items'][$itemIndex]['item_cta_label'] = $patch['item_cta_label'];
            $rows[$layoutIndex]['tis_items'][$itemIndex]['item_cta_url'] = $patch['item_cta_url'];
            $mutated = true;
            $patchedIndex = $itemIndex;
            break 2;
        }
    }

    if (! $mutated) {
        WP_CLI::error('guest-services: no Lost Property row in text_image_slider.');
    }

    if ($dryRun) {
        WP_CLI::log(sprintf(
            '[dry-run] guest-services #%d: would patch Lost Property row #%d (body + CTA only).',
            $page->ID,
            $patchedIndex
        ));
    } else {
        $attached = HomepageFlexibleAcfAttach::attachFlexibleRows($rows);
        $result = update_field('components', $attached, $page->ID);
        if ($result === false) {
            WP_CLI::warning('update_field returned false for guest-services — verify in admin.');
        }
        WP_CLI::success(sprintf(
            'guest-services #%d: Lost Property row patched (body + CTA; images unchanged).',
            $page->ID
        ));
    }
}

if ($dryRun && ! $runEaster && ! $runGuestServices) {
    WP_CLI::error('No targets selected.');
}

if ($dryRun) {
    WP_CLI::success('Dry run complete — no database writes.');
}
