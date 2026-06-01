<?php

/**
 * Remap stale `_components` field-key references after the per–post-type flexible
 * field groups were introduced (`field_page_components_page_components`, etc.).
 *
 * Run via the Local environment wrapper:
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/migrations/2026-05-31-flexible-field-group-refs.php
 *
 * Idempotent: only updates rows still pointing at the legacy unified key.
 */

use App\Config\ComponentPostTypes;

if (! defined('WPINC')) {
    fwrite(STDERR, "Run via WP-CLI (wp eval-file) so WordPress is bootstrapped under CLI.\n");
    exit(1);
}

$legacy = ComponentPostTypes::LEGACY_FLEXIBLE_FIELD_KEY;
$postTypes = [];

foreach (ComponentPostTypes::fieldGroupDefinitions() as $definition) {
    foreach ($definition['post_types'] as $postType) {
        $postTypes[$postType] = 'field_page_components_' . $definition['group_key'] . '_components';
    }
}

$updated = 0;
$skipped = 0;

foreach (array_keys($postTypes) as $postType) {
    $posts = get_posts([
        'post_type' => $postType,
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);

    foreach ($posts as $postId) {
        $postId = (int) $postId;
        $ref = get_post_meta($postId, '_components', true);

        if (! is_string($ref) || $ref === '') {
            ++$skipped;
            continue;
        }

        if ($ref !== $legacy) {
            ++$skipped;
            continue;
        }

        $target = $postTypes[$postType] ?? null;
        if ($target === null) {
            ++$skipped;
            continue;
        }

        update_post_meta($postId, '_components', $target);
        ++$updated;
        WP_CLI::log(sprintf('post %d (%s): %s → %s', $postId, $postType, $legacy, $target));
    }
}

WP_CLI::success(sprintf('Updated %d posts; skipped %d.', $updated, $skipped));
