<?php

/**
 * Wire homepage three-card block mobile video fields to existing media-library
 * landscape crops — no uploads, no desktop video changes, no other rows touched.
 *
 * Maps manual card slots 0–2 (Shop / Eat & Drink / Plan My Visit) to:
 *   culvers-homepage-8c754f9bbacbe216-1.mp4
 *   culvers-homepage-8c754f9bbacbe216-2.mp4
 *   culvers-homepage-8c754f9bbacbe216-3.mp4
 *
 * Idempotent: skips cards already pointing at the correct mobile attachment.
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/homepage-three-card-mobile-videos-sync.php dry-run
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/homepage-three-card-mobile-videos-sync.php
 */

if (! defined('ABSPATH')) {
    return;
}

$cliArgs = $GLOBALS['argv'] ?? [];
$dryRun = in_array('dry-run', $cliArgs, true) || in_array('--dry-run', $cliArgs, true);

/** @var list<string> */
const MOBILE_BASENAMES_BY_SLOT = [
    'culvers-homepage-8c754f9bbacbe216-1.mp4',
    'culvers-homepage-8c754f9bbacbe216-2.mp4',
    'culvers-homepage-8c754f9bbacbe216-3.mp4',
];

/**
 * @return int|null Attachment ID for an existing uploads file (never uploads).
 */
function culvers_find_video_attachment_by_basename(string $basename): ?int
{
    global $wpdb;

    $basename = ltrim(trim($basename), '/');
    if ($basename === '') {
        return null;
    }

    $like = '%' . $wpdb->esc_like($basename);
    $id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta}
         WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s
         ORDER BY post_id DESC LIMIT 1",
        $like
    ));

    if (! is_numeric($id) || (int) $id <= 0) {
        return null;
    }

    $mime = get_post_mime_type((int) $id);

    return is_string($mime) && str_starts_with($mime, 'video/') ? (int) $id : null;
}

/**
 * @param mixed $field
 */
function culvers_attachment_id_from_acf_file(mixed $field): int
{
    if (is_numeric($field) && (int) $field > 0) {
        return (int) $field;
    }

    if (is_array($field) && ! empty($field['ID'])) {
        return (int) $field['ID'];
    }

    return 0;
}

$frontId = (int) get_option('page_on_front');
if ($frontId <= 0) {
    $frontId = 54;
}

$rows = get_field('components', $frontId);
if (! is_array($rows)) {
    echo "No components on homepage (ID {$frontId}).\n";

    return;
}

$patched = false;
$changes = 0;

foreach ($rows as $rowIndex => $row) {
    if (! is_array($row)) {
        continue;
    }

    if (($row['acf_fc_layout'] ?? '') !== 'three_card_block') {
        continue;
    }

    if (($row['cards_source'] ?? 'manual') !== 'manual') {
        continue;
    }

    $cards = is_array($row['cards_items'] ?? null) ? $row['cards_items'] : [];
    if ($cards === []) {
        continue;
    }

    $rowChanged = false;

    foreach ($cards as $slot => $card) {
        if (! is_array($card)) {
            continue;
        }

        if ((string) ($card['card_media_type'] ?? '') !== 'video') {
            continue;
        }

        $basename = MOBILE_BASENAMES_BY_SLOT[(int) $slot] ?? null;
        if ($basename === null) {
            continue;
        }

        $mobileId = culvers_find_video_attachment_by_basename($basename);
        if ($mobileId === null) {
            echo "Missing media library file: {$basename} (slot {$slot})\n";
            continue;
        }

        $currentMobileId = culvers_attachment_id_from_acf_file($card['card_video_mobile'] ?? null);
        $desktopId = culvers_attachment_id_from_acf_file($card['card_video'] ?? null);
        $title = trim((string) ($card['card_title'] ?? ''));

        if ($currentMobileId === $mobileId) {
            echo "OK slot {$slot} ({$title}): already mobile attachment {$mobileId}\n";
            continue;
        }

        $desktopName = $desktopId > 0 ? basename((string) get_attached_file($desktopId)) : 'none';
        $mobileName = basename((string) get_attached_file($mobileId));

        echo ($dryRun ? '[dry-run] ' : '')
            . "slot {$slot} ({$title}): card_video_mobile {$currentMobileId} → {$mobileId}"
            . " ({$mobileName}); desktop stays {$desktopId} ({$desktopName})\n";

        if (! $dryRun) {
            $cards[$slot]['card_video_mobile'] = $mobileId;
        }

        $rowChanged = true;
        ++$changes;
    }

    if ($rowChanged) {
        if (! $dryRun) {
            $rows[$rowIndex]['cards_items'] = $cards;
        }
        $patched = true;
    }
}

if (! $patched) {
    echo $changes === 0
        ? "Nothing to do — mobile video fields already correct.\n"
        : "Dry-run only — {$changes} slot(s) would update.\n";

    return;
}

if ($dryRun) {
    echo "Dry-run complete — {$changes} slot(s) would update on homepage {$frontId}.\n";

    return;
}

update_field('components', $rows, $frontId);
echo "Updated {$changes} mobile video field(s) on homepage {$frontId}.\n";
