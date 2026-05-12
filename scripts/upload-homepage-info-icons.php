<?php
/**
 * Upload the 8 hand-drawn icons from Figma into the WP media library and
 * wire them onto the homepage info_block (Pages → Home → row 4 → cells 0-7).
 *
 * Sources are vector SVGs exported from the "Culver Square Website Design —
 * Developer Release" file (file key `KoBl6rTY98YnvusBgKLx4A`), specifically
 * the 4×2 stat-tile grid above the brand strip. Each tile in Figma has its
 * own icon (fork/knife, garment, footprints, bunting, train, lolly, dog,
 * coffee cup); IDs are commented below.
 *
 * Each SVG ships from Figma with three export bugs:
 *   1. `preserveAspectRatio="none"` — disables aspect-ratio preservation
 *   2. `width="100%" height="100%"` — no intrinsic dimensions
 *   3. WP attachment meta defaults to 0x0 for SVGs
 *
 * This script patches (1) and (2) inline before upload, then writes the
 * viewBox-derived pixel dimensions into `_wp_attachment_metadata` so that
 * `App\Helpers\Image::render()` can emit real `width`/`height` HTML
 * attributes. This is the same fix `fix-figma-svg-aspect.php` +
 * `fix-svg-attachment-meta.php` apply to existing assets, just done up
 * front so re-running them is a no-op for these new files.
 *
 * Idempotent: detects existing attachments by file slug and reuses their
 * IDs rather than uploading duplicates. Safe to re-run.
 *
 * Run with:
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/upload-homepage-info-icons.php
 */

if (! defined('ABSPATH')) {
    return;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/* WP locks SVG uploads behind upload_mimes by default; whitelist for this
 * script only. (The site's runtime SVG policy is handled elsewhere — this
 * mirrors what ShopDirectoryPopulate does for its CDN-sourced assets.) */
add_filter('upload_mimes', static function (array $mimes): array {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
});
add_filter('wp_check_filetype_and_ext', static function (array $data, string $file, string $filename): array {
    if (! empty($data['ext']) && ! empty($data['type'])) {
        return $data;
    }
    if (str_ends_with(strtolower($filename), '.svg')) {
        $data['ext'] = 'svg';
        $data['type'] = 'image/svg+xml';
    }
    return $data;
}, 99, 3);

$home_id = 54;
$row_index = 4; // components row 4 → info_block
/* tmp/ sits at the Local site root, one level above the Local "app" folder
 * that ABSPATH (.../app/public) lives inside. */
$source_dir = dirname(ABSPATH, 2) . '/tmp/homepage-icons';

if (! is_dir($source_dir)) {
    echo "Source directory not found: {$source_dir}\n";
    return;
}

/*
 * Ordered slot → file mapping. Slots match the existing info_items rows
 * on the home page (see `HomepageFlexibleSeedData::infoBlockItems()`):
 *   0 = 5 places (to Eat & Drink)
 *   1 = Fab Fashion
 *   2 = 11 Min Walk
 *   3 = Fun For All
 *   4 = 36 mins
 *   5 = Sweet Tooth?
 *   6 = Pet Friendly
 *   7 = But First, Coffee
 */
$icons = [
    0 => ['file' => 'icon-1-eat-drink.svg', 'title' => 'Eat & Drink icon — Culver Square homepage', 'alt' => 'Fork and knife line illustration'],
    1 => ['file' => 'icon-2-fashion.svg', 'title' => 'Fashion icon — Culver Square homepage', 'alt' => 'Garment line illustration'],
    2 => ['file' => 'icon-3-walk.svg', 'title' => 'Walk distance icon — Culver Square homepage', 'alt' => 'Footprints line illustration'],
    3 => ['file' => 'icon-4-events.svg', 'title' => 'Family events icon — Culver Square homepage', 'alt' => 'Bunting line illustration'],
    4 => ['file' => 'icon-5-train.svg', 'title' => 'Train journey icon — Culver Square homepage', 'alt' => 'Train line illustration'],
    5 => ['file' => 'icon-6-sweet.svg', 'title' => 'Sweet treats icon — Culver Square homepage', 'alt' => 'Lolly line illustration'],
    6 => ['file' => 'icon-7-pet.svg', 'title' => 'Pet friendly icon — Culver Square homepage', 'alt' => 'Dog line illustration'],
    7 => ['file' => 'icon-8-coffee.svg', 'title' => 'Coffee icon — Culver Square homepage', 'alt' => 'Coffee cup line illustration'],
];

/**
 * Patch a Figma-exported SVG string so it has intrinsic dimensions and a
 * sane preserveAspectRatio. Returns [patched_svg, width, height].
 *
 * @param string $svg
 * @return array{0: string, 1: int, 2: int}|null
 */
$patch_svg = function (string $svg): ?array {
    if (! preg_match('/<svg\b[^>]*>/i', $svg, $match, PREG_OFFSET_CAPTURE)) {
        return null;
    }
    $tag = $match[0][0];
    $offset = $match[0][1];

    if (! preg_match('/\bviewBox\s*=\s*"([\d.\-eE\s]+)"/i', $tag, $vb)) {
        return null;
    }
    $parts = preg_split('/\s+/', trim($vb[1]));
    if (! is_array($parts) || count($parts) !== 4) {
        return null;
    }
    $vw = (float) $parts[2];
    $vh = (float) $parts[3];
    if ($vw <= 0 || $vh <= 0) {
        return null;
    }
    $intW = (int) round($vw);
    $intH = (int) round($vh);

    $patched = $tag;
    $patched = preg_replace('/\bwidth\s*=\s*"100%"/i', 'width="' . $intW . '"', $patched, 1);
    $patched = preg_replace('/\bheight\s*=\s*"100%"/i', 'height="' . $intH . '"', $patched, 1);
    $patched = preg_replace('/\s+preserveAspectRatio\s*=\s*"none"/i', '', $patched, 1);

    if ($patched !== $tag) {
        $svg = substr_replace($svg, $patched, $offset, strlen($tag));
    }

    return [$svg, $intW, $intH];
};

/**
 * Find an existing SVG attachment by its desired post_name slug, so re-runs
 * don't create wp-content/uploads/.../icon-1-eat-drink-2.svg, -3.svg, etc.
 */
$find_existing = function (string $slug): int {
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'post_mime_type' => 'image/svg+xml',
        'name' => $slug,
        'numberposts' => 1,
        'fields' => 'ids',
    ]);
    return $existing[0] ?? 0;
};

$results = [];

foreach ($icons as $slot => $cfg) {
    $path = $source_dir . '/' . $cfg['file'];
    if (! is_readable($path)) {
        echo "MISSING: {$path}\n";
        continue;
    }

    $svg = file_get_contents($path);
    if (! is_string($svg)) {
        echo "READ FAIL: {$path}\n";
        continue;
    }

    $patched = $patch_svg($svg);
    if ($patched === null) {
        echo "PATCH FAIL: {$cfg['file']}\n";
        continue;
    }
    [$svg_fixed, $w, $h] = $patched;

    $slug = sanitize_title('culvers-homepage-' . pathinfo($cfg['file'], PATHINFO_FILENAME));
    $existing_id = $find_existing($slug);

    if ($existing_id > 0) {
        $att_id = $existing_id;
        echo "REUSE  slot {$slot}: {$cfg['file']} → #{$att_id}\n";
    } else {
        $upload = wp_upload_bits($slug . '.svg', null, $svg_fixed);
        if (! empty($upload['error'])) {
            echo "UPLOAD FAIL: {$cfg['file']} — {$upload['error']}\n";
            continue;
        }
        $att_id = wp_insert_attachment([
            'post_mime_type' => 'image/svg+xml',
            'post_title' => $cfg['title'],
            'post_content' => '',
            'post_status' => 'inherit',
            'post_name' => $slug,
        ], $upload['file']);
        if (is_wp_error($att_id) || ! $att_id) {
            echo "ATTACH FAIL: {$cfg['file']}\n";
            continue;
        }
        update_post_meta($att_id, '_wp_attachment_image_alt', $cfg['alt']);
        echo "UPLOAD slot {$slot}: {$cfg['file']} → #{$att_id}\n";
    }

    $meta = wp_get_attachment_metadata($att_id);
    if (! is_array($meta)) {
        $meta = [];
    }
    $meta['width'] = $w;
    $meta['height'] = $h;
    if (! isset($meta['file'])) {
        $meta['file'] = _wp_relative_upload_path(get_attached_file($att_id));
    }
    wp_update_attachment_metadata($att_id, $meta);

    update_post_meta($home_id, "components_{$row_index}_info_items_{$slot}_item_image", $att_id);
    $results[$slot] = $att_id;
}

clean_post_cache($home_id);

echo "\nFinal info_block icon assignment on page #{$home_id} row {$row_index}:\n";
foreach ($results as $slot => $id) {
    echo "  slot {$slot} → attachment #{$id}\n";
}
