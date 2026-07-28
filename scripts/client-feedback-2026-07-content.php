<?php

/**
 * Client feedback (2026-07): scoped content import + Accessorize offer removal.
 *
 * Imports from www.culversquare.co.uk into culvers_event / culvers_news with the
 * same flexible stacks as DirectoryLiveImport. Does not touch other offers.
 *
 *   cd app/public && ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/client-feedback-2026-07-content.php dry-run
 *
 *   ssh 20i-culvers 'cd public_html && php83 /usr/local/bin/wp eval-file \
 *     wp-content/themes/culvers/scripts/client-feedback-2026-07-content.php'
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Directory\DirectoryLiveImport;

if (! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI: wp eval-file …/client-feedback-2026-07-content.php\n");
    exit(1);
}

if (! function_exists('get_field') || ! function_exists('update_field')) {
    WP_CLI::error('ACF is required.');
}

$cliArgs = $args ?? [];
$dryRun = in_array('dry-run', $cliArgs, true) || in_array('--dry-run', $cliArgs, true);

$userId = (int) apply_filters('culvers_client_feedback_2026_07_content_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

/** @var list<array{slug: string, post_type: string}> */
$storyTargets = [
    ['slug' => 'summertime-screenings', 'post_type' => 'culvers_event'],
    ['slug' => 'free-book-swap-colchester', 'post_type' => 'culvers_news'],
    ['slug' => 'buzzing-into-summer', 'post_type' => 'culvers_event'],
];

WP_CLI::log($dryRun ? '[dry-run] Importing selected live stories…' : 'Importing selected live stories…');
$importer = new DirectoryLiveImport();
$result = $importer->importStoryTargets($storyTargets, $dryRun);
WP_CLI::log(sprintf('Stories: ok=%d failed=%d', $result['ok'], $result['failed']));

WP_CLI::log($dryRun ? '[dry-run] Removing Accessorize offer…' : 'Removing Accessorize offer…');
$offerSlugs = ['accessorize-offers', 'accessorize', 'accessorise-offers', 'accessorise'];
$removed = 0;
foreach ($offerSlugs as $slug) {
    $posts = get_posts([
        'post_type' => 'culvers_offer',
        'name' => $slug,
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);
    foreach ($posts as $postId) {
        $postId = (int) $postId;
        $title = get_the_title($postId);
        if ($dryRun) {
            WP_CLI::log("[dry-run] would trash offer #{$postId} ({$title} / {$slug})");
            ++$removed;

            continue;
        }

        $trashed = wp_trash_post($postId);
        if ($trashed instanceof WP_Post || $trashed !== false) {
            WP_CLI::log("✓ trashed offer #{$postId} ({$title} / {$slug})");
            ++$removed;
        } else {
            WP_CLI::warning("Failed to trash offer #{$postId} ({$slug})");
        }
    }
}

// Also match by title if slug differs.
$titleQuery = new WP_Query([
    'post_type' => 'culvers_offer',
    'post_status' => 'any',
    'posts_per_page' => -1,
    's' => 'Accessorize',
    'fields' => 'ids',
]);
foreach ($titleQuery->posts as $postId) {
    $postId = (int) $postId;
    $title = (string) get_the_title($postId);
    if (! str_contains(mb_strtolower($title), 'accessorize') && ! str_contains(mb_strtolower($title), 'accessorise')) {
        continue;
    }
    if (get_post_status($postId) === 'trash') {
        continue;
    }
    if ($dryRun) {
        WP_CLI::log("[dry-run] would trash offer #{$postId} by title ({$title})");
        ++$removed;

        continue;
    }
    if (wp_trash_post($postId)) {
        WP_CLI::log("✓ trashed offer #{$postId} by title ({$title})");
        ++$removed;
    }
}
wp_reset_postdata();

if ($result['failed'] > 0) {
    WP_CLI::warning('Some story imports failed — check logs above.');
}

WP_CLI::success(sprintf(
    'Content batch complete%s — stories ok=%d, Accessorize removals=%d',
    $dryRun ? ' (dry-run)' : '',
    $result['ok'],
    $removed
));
