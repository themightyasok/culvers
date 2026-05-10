<?php

/**
 * Assign distinct mega-menu hover preview images per sibling submenu row (primary_navigation).
 *
 * Run from WordPress root with Local:
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file wp-content/themes/culvers/scripts/mega-menu-distinct-previews.php
 *
 * Leaves already-unique preview URLs untouched; writes attachment IDs from the media library
 * when siblings share the same resolved image URL or a row has no preview.
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Nav\MegaMenuDistinctPreviews;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

$n = MegaMenuDistinctPreviews::assignDistinctAttachmentPreviews('primary_navigation');

if ($n <= 0) {
    \WP_CLI::warning(
        'No submenu rows updated. Check primary_navigation has nested items and the media library has images.'
    );
} else {
    \WP_CLI::success(sprintf('Assigned distinct mega preview attachments on %d submenu row(s).', $n));
}
