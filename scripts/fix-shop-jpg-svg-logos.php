<?php
/**
 * Repair shop logos that were imported from Figma with a `.jpg` extension
 * even though the file content is actually SVG.
 *
 * Background: when the shop directory was first populated from the Figma
 * developer-release file, the import path used `media_handle_sideload`
 * with a `.jpg` extension picked from the Figma CDN URL — but the bytes
 * served by the Figma MCP asset endpoint are SVG markup, not JPEG.
 * Browsers refuse to render SVG content served via a `.jpg` URL (the
 * Content-Type vs file extension mismatch trips an `<img>` decode
 * failure), which is why nine shop tiles on `/shops/` show only the
 * hover photo and no logo:
 *
 *   Accessorize London, ALL 4 U CARE, Flying Tiger Copenhagen, HMV,
 *   Pandora, Schuh, Smiggle, Søstrene Grene, TK Maxx.
 *
 * The fix: for every `culvers_shop` whose `shop_logo` (or featured
 * image) points at an attachment whose file is SVG-on-disk-but-jpg-by-
 * extension, copy the bytes into a sibling `.svg` file, patch the
 * Figma export bugs (preserveAspectRatio="none" / width="100%" /
 * height="100%") so the SVG has intrinsic dimensions, create a fresh
 * image/svg+xml attachment, and repoint the ACF + thumbnail meta.
 * The original broken `.jpg` attachment is trashed if nothing else
 * references it; otherwise left in place.
 *
 * Idempotent: skips any shop whose current `shop_logo` already resolves
 * to an `image/svg+xml` attachment (or any non-SVG raster — those
 * stayed `.png` and render fine).
 *
 * Run with:
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/fix-shop-jpg-svg-logos.php
 *
 * Or on 20i:
 *
 *   ssh 20i-culvers "cd public_html && php83 /usr/local/bin/wp \
 *     eval-file wp-content/themes/culvers/themes/culvers/scripts/fix-shop-jpg-svg-logos.php"
 */

if (! defined('ABSPATH')) {
    return;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/* Local installs lock SVG uploads behind upload_mimes by default. Whitelist
 * the MIME for the duration of this script — mirrors ShopDirectoryPopulate.
 */
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

/**
 * Detect whether a file on disk is SVG markup regardless of extension.
 */
$looks_like_svg = static function (string $path): bool {
    if (! is_readable($path)) {
        return false;
    }
    $head = file_get_contents($path, false, null, 0, 512);
    if (! is_string($head)) {
        return false;
    }
    $head = ltrim($head);
    // Strip a leading XML prolog if present.
    if (str_starts_with($head, '<?xml')) {
        $close = strpos($head, '?>');
        if ($close !== false) {
            $head = ltrim(substr($head, $close + 2));
        }
    }
    return stripos($head, '<svg') === 0 || stripos($head, '<svg ') !== false;
};

/**
 * Patch the SVG tag so it carries intrinsic width/height (from viewBox) and
 * drops Figma's bogus `preserveAspectRatio="none"`. Returns [patched_svg, w, h].
 *
 * @return array{0: string, 1: int, 2: int}|null
 */
$patch_svg_string = static function (string $svg): ?array {
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

$shops = get_posts([
    'post_type' => 'culvers_shop',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$repaired = [];
$skipped = [];
$failed = [];

foreach ($shops as $shop_id) {
    $title = get_the_title($shop_id);
    $logo_id = (int) get_post_meta($shop_id, 'shop_logo', true);
    if ($logo_id <= 0) {
        $skipped[] = [$shop_id, $title, 'no shop_logo meta'];
        continue;
    }

    $logo_path = get_attached_file($logo_id);
    if (! is_string($logo_path) || $logo_path === '') {
        $failed[] = [$shop_id, $title, "attachment #{$logo_id}: no file path"];
        continue;
    }

    $mime = (string) get_post_mime_type($logo_id);
    $extension = strtolower((string) pathinfo($logo_path, PATHINFO_EXTENSION));

    /* Three states to recognise:
     *
     *  1. mime=svg + ext=svg → already correct, skip.
     *  2. mime=svg + ext≠svg (typically `.jpg`) → file bytes are SVG but
     *     Apache serves Content-Type: image/jpeg based on the extension,
     *     which browsers refuse to render. Rename the file (and update
     *     guid / `_wp_attached_file` / attachment metadata) so the URL
     *     ends in `.svg` and the host serves it as image/svg+xml.
     *  3. mime≠svg + bytes-look-like-svg → previous bad import that
     *     never even updated the attachment's mime. Rebuild as a fresh
     *     SVG attachment (covered by the original branch below).
     */
    if ($mime === 'image/svg+xml' && $extension === 'svg') {
        $skipped[] = [$shop_id, $title, "attachment #{$logo_id}: already correct (mime+ext)"];
        continue;
    }

    if ($mime === 'image/svg+xml' && $extension !== 'svg') {
        /* Case 2 — rename file in-place, repoint the attachment. */
        $svg_raw_in = file_get_contents($logo_path);
        if (! is_string($svg_raw_in)) {
            $failed[] = [$shop_id, $title, "attachment #{$logo_id}: read failed"];
            continue;
        }
        $patched_in = $patch_svg_string($svg_raw_in);
        if ($patched_in === null) {
            $failed[] = [$shop_id, $title, "attachment #{$logo_id}: SVG had no viewBox, can't patch"];
            continue;
        }
        [$svg_fixed_in, $intW_in, $intH_in] = $patched_in;

        $base = pathinfo($logo_path, PATHINFO_FILENAME);
        $dir = dirname($logo_path);
        $new_path = $dir . '/' . $base . '.svg';

        /* Avoid clobbering if a previous run already moved the file. */
        if (! file_exists($new_path)) {
            if (file_put_contents($new_path, $svg_fixed_in) === false) {
                $failed[] = [$shop_id, $title, "attachment #{$logo_id}: write to {$new_path} failed"];
                continue;
            }
            @unlink($logo_path);
        }

        $relative = _wp_relative_upload_path($new_path);
        update_post_meta($logo_id, '_wp_attached_file', $relative);

        $uploads = wp_get_upload_dir();
        $new_url = trailingslashit($uploads['baseurl']) . $relative;
        wp_update_post([
            'ID' => $logo_id,
            'guid' => $new_url,
        ]);

        $meta_in = wp_get_attachment_metadata($logo_id);
        if (! is_array($meta_in)) {
            $meta_in = [];
        }
        $meta_in['width'] = $intW_in;
        $meta_in['height'] = $intH_in;
        $meta_in['file'] = $relative;
        wp_update_attachment_metadata($logo_id, $meta_in);

        $repaired[] = [$shop_id, $title, "renamed #{$logo_id} .{$extension} → .svg ({$intW_in}×{$intH_in})"];
        continue;
    }

    if (! $looks_like_svg($logo_path)) {
        $skipped[] = [$shop_id, $title, "attachment #{$logo_id}: bytes are not SVG (real raster, leaving alone)"];
        continue;
    }

    /* The file is SVG content under the wrong extension. Build a sibling
     * .svg path, copy + patch, and create a fresh attachment we can repoint
     * the ACF field at. Using sanitize_title($slug)-logo as the slug keeps
     * filenames human-readable in the media library. */
    $svg_raw = file_get_contents($logo_path);
    if (! is_string($svg_raw)) {
        $failed[] = [$shop_id, $title, "attachment #{$logo_id}: read failed"];
        continue;
    }
    $patched = $patch_svg_string($svg_raw);
    if ($patched === null) {
        $failed[] = [$shop_id, $title, "attachment #{$logo_id}: SVG had no viewBox, can't patch"];
        continue;
    }
    [$svg_fixed, $intW, $intH] = $patched;

    $slug = sanitize_title($title) . '-logo';
    if ($slug === '-logo' || $slug === '') {
        $slug = 'shop-' . $shop_id . '-logo';
    }
    $desired_filename = $slug . '.svg';

    /* Re-use an existing .svg attachment we may have created on a previous run
     * (the script is idempotent by design). */
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'post_mime_type' => 'image/svg+xml',
        'name' => $slug,
        'numberposts' => 1,
        'fields' => 'ids',
    ]);
    $new_id = $existing[0] ?? 0;

    if ($new_id > 0) {
        /* Already exists — just ensure ACF + featured image point at it. */
        $action = 'reuse';
    } else {
        $upload = wp_upload_bits($desired_filename, null, $svg_fixed);
        if (! empty($upload['error'])) {
            $failed[] = [$shop_id, $title, "upload bits failed: {$upload['error']}"];
            continue;
        }
        $new_id = wp_insert_attachment([
            'post_mime_type' => 'image/svg+xml',
            'post_title' => $title . ' — logo',
            'post_content' => '',
            'post_status' => 'inherit',
            'post_name' => $slug,
        ], $upload['file']);
        if (is_wp_error($new_id) || ! $new_id) {
            $failed[] = [$shop_id, $title, 'wp_insert_attachment failed'];
            continue;
        }
        update_post_meta($new_id, '_wp_attachment_image_alt', $title . ' logo');
        $action = 'created';
    }

    /* Set/refresh attachment metadata so `App\Helpers\Image::render()` can
     * emit proper width/height HTML attributes (the same fix
     * `fix-svg-attachment-meta.php` applies generically). */
    $meta = wp_get_attachment_metadata($new_id);
    if (! is_array($meta)) {
        $meta = [];
    }
    $meta['width'] = $intW;
    $meta['height'] = $intH;
    if (! isset($meta['file'])) {
        $meta['file'] = _wp_relative_upload_path((string) get_attached_file($new_id));
    }
    wp_update_attachment_metadata($new_id, $meta);

    /* Repoint ACF + featured image. Note: WP's hover-photo lives on
     * `shop_hover_image` and the featured image (`_thumbnail_id`) is what
     * the card uses for the on-hover layer. Some shops have the logo as
     * BOTH `shop_logo` and `_thumbnail_id` (Ann Summers etc.) — but the
     * majority point `_thumbnail_id` at the hover photo instead, so we
     * only repoint `shop_logo`. Cards read the logo from `shop_logo`. */
    update_post_meta($shop_id, 'shop_logo', $new_id);
    $thumb_id = (int) get_post_thumbnail_id($shop_id);
    if ($thumb_id === $logo_id) {
        /* If the original broken attachment was doubling as the featured
         * image, repoint that too — otherwise leave the existing hover
         * photo featured image alone. */
        set_post_thumbnail($shop_id, $new_id);
    }

    /* Trash the original broken `.jpg` attachment if nothing else
     * references it. We check `_thumbnail_id` and a few ACF keys in use. */
    $still_referenced = false;
    if ((int) get_post_thumbnail_id($shop_id) === $logo_id) {
        $still_referenced = true;
    }
    foreach (['shop_hover_image'] as $other_key) {
        if ((int) get_post_meta($shop_id, $other_key, true) === $logo_id) {
            $still_referenced = true;
            break;
        }
    }
    if (! $still_referenced) {
        wp_delete_attachment($logo_id, true);
    }

    $repaired[] = [$shop_id, $title, "{$action} #{$new_id} (was #{$logo_id})"];
}

echo "Repaired (" . count($repaired) . "):\n";
foreach ($repaired as [$id, $title, $note]) {
    printf("  #%d %-28s %s\n", $id, $title, $note);
}
echo "\nSkipped (" . count($skipped) . "):\n";
foreach ($skipped as [$id, $title, $note]) {
    printf("  #%d %-28s %s\n", $id, $title, $note);
}
if ($failed !== []) {
    echo "\nFAILED (" . count($failed) . "):\n";
    foreach ($failed as [$id, $title, $note]) {
        printf("  #%d %-28s %s\n", $id, $title, $note);
    }
}
