<?php

/**
 * Backfill `event_ends_on` from free-text `event_card_date` where parseable, then
 * draft any published events that have already ended.
 *
 * Usage (from app/public):
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/event-ends-on-backfill.php
 *
 * Dry-run by default. Apply with:
 *   CULVERS_EVENT_ENDS_APPLY=1 wp eval-file …
 */

declare(strict_types=1);

use App\Directory\EventExpiry;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run via WP-CLI eval-file.\n");
    exit(1);
}

$apply = getenv('CULVERS_EVENT_ENDS_APPLY') === '1';

/**
 * @return list<string>
 */
function culvers_event_ends_candidate_strings(int $postId): array
{
    $candidates = [];
    $card = trim((string) get_field('event_card_date', $postId));
    if ($card !== '') {
        $candidates[] = $card;
    }

    $components = get_field('components', $postId);
    if (is_array($components)) {
        foreach ($components as $row) {
            if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'event_meta') {
                continue;
            }
            $meta = trim((string) ($row['event_meta_date_value'] ?? ''));
            if ($meta !== '') {
                $candidates[] = $meta;
            }
        }
    }

    return $candidates;
}

/**
 * Best-effort parse of display strings like "Until 15 Aug 2026" or "6 Dec 2025 – 23 Dec 2025".
 */
function culvers_event_ends_parse_display(string $text): ?DateTimeImmutable
{
    $text = trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($text === '') {
        return null;
    }

    $tz = wp_timezone();

    if (preg_match('/until\s+(\d{1,2}\s+[A-Za-z]+\s+\d{4})/i', $text, $m) === 1) {
        $dt = DateTimeImmutable::createFromFormat('!j F Y', $m[1], $tz)
            ?: DateTimeImmutable::createFromFormat('!j M Y', $m[1], $tz);
        if ($dt instanceof DateTimeImmutable) {
            return $dt;
        }
    }

    if (preg_match(
        '/(\d{1,2}\s+[A-Za-z]+\s+\d{4})\s*[–—-]\s*(\d{1,2}\s+[A-Za-z]+\s+\d{4})/u',
        $text,
        $m
    ) === 1) {
        $dt = DateTimeImmutable::createFromFormat('!j F Y', $m[2], $tz)
            ?: DateTimeImmutable::createFromFormat('!j M Y', $m[2], $tz);
        if ($dt instanceof DateTimeImmutable) {
            return $dt;
        }
    }

    if (preg_match('/^(\d{1,2}\s+[A-Za-z]+\s+\d{4})$/', $text, $m) === 1) {
        $dt = DateTimeImmutable::createFromFormat('!j F Y', $m[1], $tz)
            ?: DateTimeImmutable::createFromFormat('!j M Y', $m[1], $tz);
        if ($dt instanceof DateTimeImmutable) {
            return $dt;
        }
    }

    return EventExpiry::parseDate($text);
}

$posts = get_posts([
    'post_type' => 'culvers_event',
    'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
    'numberposts' => -1,
    'orderby' => 'ID',
    'order' => 'ASC',
]);

$filled = 0;
$skipped = 0;
$unparsed = [];

foreach ($posts as $post) {
    $id = (int) $post->ID;
    $existing = EventExpiry::endDateYmd($id);
    if ($existing !== null) {
        $skipped++;
        continue;
    }

    $parsed = null;
    foreach (culvers_event_ends_candidate_strings($id) as $candidate) {
        $parsed = culvers_event_ends_parse_display($candidate);
        if ($parsed instanceof DateTimeImmutable) {
            break;
        }
    }

    if (! $parsed instanceof DateTimeImmutable) {
        $card = trim((string) get_field('event_card_date', $id));
        if ($card !== '') {
            $unparsed[] = sprintf('#%d %s — %s', $id, $post->post_title, $card);
        }
        continue;
    }

    $ymd = $parsed->format('Ymd');
    WP_CLI::log(sprintf(
        '%s #%d %s → event_ends_on=%s (%s)',
        $apply ? 'SET' : 'DRY',
        $id,
        $post->post_title,
        $ymd,
        $parsed->format('j F Y')
    ));

    if ($apply) {
        update_field(EventExpiry::FIELD, $ymd, $id);
        EventExpiry::rescheduleForPost($id);
    }
    $filled++;
}

WP_CLI::log(sprintf(
    'Backfill: %d would-set/set, %d already had end date, %d unparsed with card dates.',
    $filled,
    $skipped,
    count($unparsed)
));

foreach ($unparsed as $line) {
    WP_CLI::log('  unparsed: ' . $line);
}

if ($apply) {
    $unpublished = EventExpiry::unpublishExpired();
    WP_CLI::success(sprintf(
        'Applied. Unpublished %d expired published event(s): %s',
        count($unpublished),
        $unpublished === [] ? 'none' : implode(', ', $unpublished)
    ));
} else {
    WP_CLI::warning('Dry-run only. Re-run with CULVERS_EVENT_ENDS_APPLY=1 to write and unpublish.');
}
