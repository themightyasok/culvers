<?php

/**
 * One-shot migration: remove legacy manual three_card_block rows from saved flexible content.
 *
 * Usage (from app/public):
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/migrations/2026-05-remove-manual-three-card-block.php
 */

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI (wp eval-file) so WordPress is bootstrapped under CLI.\n");
    exit(1);
}

if (! function_exists('get_field') || ! function_exists('update_field')) {
    WP_CLI::error('ACF is required.');
}

/**
 * @return non-empty-string
 */
function culvers_tcb_infer_cpt(array $row, string $parentPostType): string
{
    $heading = mb_strtolower(trim((string) ($row['cards_heading'] ?? '')));

    if ($parentPostType === 'culvers_offer') {
        return 'culvers_offer';
    }
    if ($parentPostType === 'culvers_event') {
        return 'culvers_event';
    }
    if ($parentPostType === 'culvers_news') {
        return 'culvers_news';
    }
    if ($parentPostType === 'culvers_shop') {
        return 'culvers_shop';
    }
    if ($parentPostType === 'culvers_eat_drink') {
        return 'culvers_eat_drink';
    }
    if ($parentPostType === 'culvers_career') {
        return 'culvers_career';
    }

    if (str_contains($heading, 'offer')) {
        return 'culvers_offer';
    }
    if (str_contains($heading, 'event')) {
        return 'culvers_event';
    }
    if (str_contains($heading, 'news')) {
        return 'culvers_news';
    }
    if (str_contains($heading, 'shop') || str_contains($heading, 'family')) {
        return 'culvers_shop';
    }
    if (str_contains($heading, 'eat') || str_contains($heading, 'drink')) {
        return 'culvers_eat_drink';
    }

    foreach (is_array($row['cards_items'] ?? null) ? $row['cards_items'] : [] as $card) {
        if (! is_array($card)) {
            continue;
        }
        $url = mb_strtolower(trim((string) ($card['card_url'] ?? '')));
        if (str_contains($url, '/latest-offers')) {
            return 'culvers_offer';
        }
        if (str_contains($url, '/latest-events')) {
            return 'culvers_event';
        }
        if (str_contains($url, '/latest-news')) {
            return 'culvers_news';
        }
        if (str_contains($url, '/shops')) {
            return 'culvers_shop';
        }
    }

    if (str_contains($heading, 'looking for today')) {
        return 'culvers_news';
    }

    return 'culvers_offer';
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @return list<array<string, mixed>>
 */
function culvers_tcb_migrate_rows(array $rows, string $parentPostType): array
{
    foreach ($rows as $i => $row) {
        if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'three_card_block') {
            continue;
        }
        if (($row['cards_source'] ?? '') !== 'manual') {
            unset($rows[$i]['cards_items']);
            continue;
        }

        $cpt = culvers_tcb_infer_cpt($row, $parentPostType);
        $archive = get_post_type_archive_link($cpt);

        $rows[$i]['cards_source'] = 'cpt';
        $rows[$i]['cards_cpt_post_type'] = [$cpt];
        $rows[$i]['cards_cpt_count'] = (int) ($row['cards_cpt_count'] ?? 3);
        if ($rows[$i]['cards_cpt_count'] < 1) {
            $rows[$i]['cards_cpt_count'] = 3;
        }

        if (trim((string) ($row['cards_view_all_url'] ?? '')) === '' && is_string($archive) && $archive !== '') {
            $rows[$i]['cards_view_all_url'] = $archive;
        }

        if (str_contains(mb_strtolower(trim((string) ($row['cards_heading'] ?? ''))), 'looking for today')) {
            $rows[$i]['cards_cpt_post_type'] = ['culvers_news', 'culvers_event', 'culvers_offer'];
            $rows[$i]['cards_view_all_url'] = '';
        }

        unset($rows[$i]['cards_items']);
    }

    return $rows;
}

$types = [
    'page',
    'culvers_offer',
    'culvers_event',
    'culvers_news',
    'culvers_shop',
    'culvers_eat_drink',
    'culvers_career',
];

$updatedPosts = 0;
$migratedBlocks = 0;

foreach ($types as $postType) {
    $posts = get_posts([
        'post_type' => $postType,
        'post_status' => ['publish', 'draft', 'private', 'future'],
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);

    foreach ($posts as $postId) {
        $postId = (int) $postId;
        $rows = get_field('components', $postId);
        if (! is_array($rows) || $rows === []) {
            continue;
        }

        $before = wp_json_encode($rows);
        $rows = culvers_tcb_migrate_rows($rows, $postType);
        $after = wp_json_encode($rows);

        if ($before === $after) {
            continue;
        }

        $migratedBlocks += substr_count((string) $before, '"cards_source":"manual"');
        update_field('components', $rows, $postId);
        $updatedPosts++;
    }
}

WP_CLI::success(sprintf(
    'Migrated manual three_card_block rows on %d posts (%d legacy manual blocks touched).',
    $updatedPosts,
    $migratedBlocks
));
