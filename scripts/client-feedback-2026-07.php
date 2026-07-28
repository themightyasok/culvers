<?php

/**
 * Client feedback (2026-07): clear store phone on shops without a contact number.
 *
 *   cd app/public && ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/client-feedback-2026-07.php dry-run
 *
 *   ssh 20i-culvers 'cd public_html && php83 /usr/local/bin/wp eval-file \
 *     wp-content/themes/culvers/scripts/client-feedback-2026-07.php'
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

if (! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI: wp eval-file wp-content/themes/culvers/scripts/client-feedback-2026-07.php\n");
    exit(1);
}

if (! function_exists('get_field') || ! function_exists('update_field')) {
    WP_CLI::error('ACF is required.');
}

$cliArgs = $args ?? [];
$dryRun = in_array('dry-run', $cliArgs, true) || in_array('--dry-run', $cliArgs, true);

$userId = (int) apply_filters('culvers_client_feedback_2026_07_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

/** @var list<string> */
const SHOP_SLUGS_WITHOUT_PHONE = [
    'eyfel-cosmetics',
    'snows-collectables',
    'twana-fruit-vegetables',
];

/**
 * @param callable():void $write
 */
function culvers_client_feedback_run(bool $dryRun, string $label, callable $write): void
{
    if ($dryRun) {
        WP_CLI::log("[dry-run] {$label}");

        return;
    }

    $write();
    WP_CLI::log("✓ {$label}");
}

culvers_client_feedback_run($dryRun, 'Clear phone on Eyfel, Snow\'s Collectables, and Twana', static function (): void {
    foreach (SHOP_SLUGS_WITHOUT_PHONE as $slug) {
        $post = get_page_by_path($slug, OBJECT, 'culvers_shop');
        if (! $post instanceof WP_Post) {
            WP_CLI::warning("Shop not found: {$slug}");

            continue;
        }

        $components = get_field('components', $post->ID);
        if (! is_array($components) || $components === []) {
            WP_CLI::warning("{$slug} (#{$post->ID}): no flexible components");

            continue;
        }

        $updated = false;
        foreach ($components as $index => $row) {
            if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'shop_store_details') {
                continue;
            }

            $before = trim((string) ($row['details_contact_phone'] ?? ''));
            if ($before === '') {
                WP_CLI::log("  {$slug}: phone already empty");

                continue;
            }

            $components[$index]['details_contact_phone'] = '';
            $updated = true;
            WP_CLI::log("  {$slug}: cleared phone (was {$before})");
        }

        if (! $updated) {
            continue;
        }

        if (! update_field('components', $components, $post->ID)) {
            WP_CLI::warning("{$slug} (#{$post->ID}): update_field failed");
        }
    }
});

WP_CLI::success($dryRun ? 'Dry run complete.' : 'Client feedback batch complete.');
