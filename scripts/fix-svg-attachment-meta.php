<?php
/**
 * WordPress can't introspect intrinsic dimensions from an SVG's `viewBox`,
 * so every SVG attachment in the media library has `width`/`height` stored
 * as 0 in `_wp_attachment_metadata`. Our `App\Helpers\Image::render()`
 * deliberately skips zero-valued width/height attributes (otherwise the
 * `<img>` would collapse), which means SVG `<img>` elements ship to the
 * browser with no intrinsic ratio.
 *
 * On the broader web this is mostly hidden because the SVG file itself
 * carries `width="N" height="N"` attributes the browser can fall back on,
 * but the Figma exports in this project had `preserveAspectRatio="none"`
 * combined with `width="100%" height="100%"`, which (post-`fix-figma-svg-aspect.php`)
 * we have now corrected. Browsers still need a hand when our `<img>` is
 * absolutely sized via `w-full` inside a flex column with no explicit
 * height — without an HTML width/height attribute the inferred aspect
 * ratio comes from the SVG file, and Chromium sometimes resolves that to
 * `naturalWidth/Height = 0` early in layout, collapsing the box.
 *
 * Patching the WordPress attachment meta gives the helper real numbers to
 * emit as `width`/`height` HTML attributes, which sets the browser's
 * `aspect-ratio: auto W / H` fallback and stops the collapse.
 *
 * Run with:
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/fix-svg-attachment-meta.php
 *
 * Idempotent — only touches attachments whose currently stored width/height
 * are 0 (or whose stored values diverge from the viewBox by > 1px).
 */
$query = new WP_Query([
    'post_type' => 'attachment',
    'post_status' => 'inherit',
    'post_mime_type' => 'image/svg+xml',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$patched = 0;
$skipped = 0;
$broken = 0;

foreach ($query->posts as $attachment_id) {
    $path = get_attached_file($attachment_id);
    if (! is_string($path) || ! is_readable($path)) {
        $broken++;
        continue;
    }
    $content = file_get_contents($path);
    if (! is_string($content)) {
        $broken++;
        continue;
    }
    /* Parse the opening <svg> tag — it carries either explicit width/height
       or a viewBox we can fall back on. */
    if (! preg_match('/<svg\b[^>]*>/i', $content, $match)) {
        $broken++;
        continue;
    }
    $tag = $match[0];
    $w = null;
    $h = null;
    if (preg_match('/\bwidth\s*=\s*"([\d.]+)(?:px)?"/i', $tag, $m) && (float) $m[1] > 0) {
        $w = (float) $m[1];
    }
    if (preg_match('/\bheight\s*=\s*"([\d.]+)(?:px)?"/i', $tag, $m) && (float) $m[1] > 0) {
        $h = (float) $m[1];
    }
    if (($w === null || $h === null) && preg_match('/\bviewBox\s*=\s*"([\d.\-eE\s]+)"/i', $tag, $m)) {
        $parts = preg_split('/\s+/', trim($m[1]));
        if (is_array($parts) && count($parts) === 4) {
            $w = $w ?? (float) $parts[2];
            $h = $h ?? (float) $parts[3];
        }
    }
    if ($w === null || $h === null || $w <= 0 || $h <= 0) {
        $broken++;
        continue;
    }
    $intW = (int) round($w);
    $intH = (int) round($h);

    $meta = wp_get_attachment_metadata($attachment_id);
    if (! is_array($meta)) {
        $meta = [];
    }
    $existingW = (int) ($meta['width'] ?? 0);
    $existingH = (int) ($meta['height'] ?? 0);
    if ($existingW === $intW && $existingH === $intH) {
        $skipped++;
        continue;
    }
    $meta['width'] = $intW;
    $meta['height'] = $intH;
    if (! isset($meta['file'])) {
        $meta['file'] = _wp_relative_upload_path($path);
    }
    wp_update_attachment_metadata($attachment_id, $meta);
    $patched++;
}

echo "Patched: $patched\nSkipped (already correct): $skipped\nBroken: $broken\nTotal SVGs: " . count($query->posts) . "\n";
