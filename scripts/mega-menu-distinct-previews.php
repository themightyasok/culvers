<?php

/**
 * Mega menu previews — **generic media-library path**
 *
 * When sibling submenu rows share the same resolved preview URL (or lack meta),
 * writes distinct `_culvers_mega_preview_attachment_id` values using images from
 * the library ({@see MegaMenuDistinctPreviews}). For Figma-bootstrap URL sync,
 * use `mega-menu-sync-previews.php` instead (see `docs/README.md`).
 *
 * Run from WordPress root with Local:
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file wp-content/themes/culvers/scripts/mega-menu-distinct-previews.php
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
