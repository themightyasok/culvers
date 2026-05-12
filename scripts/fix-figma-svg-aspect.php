<?php
/**
 * Figma exports the line-art SVGs in this project with
 *
 *   preserveAspectRatio="none" width="100%" height="100%"
 *
 * which is *fine* when the SVG is dropped into another Figma frame, but is
 * actively wrong when the same file is served as an `<img>` element:
 *
 *  - `preserveAspectRatio="none"` disables aspect-ratio preservation
 *  - `width="100%" height="100%"` means the SVG fills its `<img>` container,
 *    not the other way round — there are no intrinsic dimensions for the
 *    browser to pick up, so the image either collapses to 0px tall or
 *    stretches to whatever box the CSS gives it.
 *
 * This script patches every SVG under wp-content/uploads in place to:
 *
 *  1. Replace `width="100%"` / `height="100%"` with concrete pixel values
 *     derived from the SVG's `viewBox`, so the browser has intrinsic dimensions.
 *  2. Remove `preserveAspectRatio="none"` so the browser uses the default
 *     `xMidYMid meet` (preserve aspect ratio, centre, no scaling distortion).
 *
 * Run with:
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/fix-figma-svg-aspect.php
 *
 * Idempotent — re-running does nothing once SVGs already have intrinsic
 * dimensions (the regex won't match `width="100%"` any more).
 */
$uploads_dir = wp_get_upload_dir()['basedir'];
if (! is_string($uploads_dir) || ! is_dir($uploads_dir)) {
    echo "Cannot resolve uploads directory.\n";
    return;
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploads_dir, RecursiveDirectoryIterator::SKIP_DOTS));
$patched = 0;
$skipped = 0;
$broken = 0;

foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    if (! $file->isFile() || strtolower($file->getExtension()) !== 'svg') {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);
    if ($content === false) {
        $broken++;
        continue;
    }

    /* Capture the opening <svg ...> tag once so we only patch that, never
       any nested <svg> primitives further down the file. */
    if (! preg_match('/<svg\b[^>]*>/i', $content, $match, PREG_OFFSET_CAPTURE)) {
        $skipped++;
        continue;
    }
    $tag = $match[0][0];
    $offset = $match[0][1];

    $hasPercentWidth = (bool) preg_match('/\bwidth\s*=\s*"100%"/', $tag);
    $hasPercentHeight = (bool) preg_match('/\bheight\s*=\s*"100%"/', $tag);
    $hasNonePreserve = (bool) preg_match('/\bpreserveAspectRatio\s*=\s*"none"/i', $tag);

    if (! $hasPercentWidth && ! $hasPercentHeight && ! $hasNonePreserve) {
        $skipped++;
        continue;
    }

    if (! preg_match('/\bviewBox\s*=\s*"([\d.\-eE\s]+)"/i', $tag, $vb)) {
        $broken++;
        continue;
    }
    $parts = preg_split('/\s+/', trim($vb[1]));
    if (! is_array($parts) || count($parts) !== 4) {
        $broken++;
        continue;
    }
    $vw = (float) $parts[2];
    $vh = (float) $parts[3];
    if ($vw <= 0 || $vh <= 0) {
        $broken++;
        continue;
    }

    /* Round to nearest int — these SVGs render fine at any size, and integer
       attributes keep the resulting markup tidy. */
    $intW = (int) round($vw);
    $intH = (int) round($vh);

    $patchedTag = $tag;
    /* Replace width="100%" with width="N"; if attr is missing entirely, leave
       it — viewBox alone is enough for intrinsic ratio in most browsers, but
       explicit pixel attrs are belt-and-braces for older WebKit. */
    if ($hasPercentWidth) {
        $patchedTag = preg_replace('/\bwidth\s*=\s*"100%"/', 'width="' . $intW . '"', $patchedTag, 1);
    }
    if ($hasPercentHeight) {
        $patchedTag = preg_replace('/\bheight\s*=\s*"100%"/', 'height="' . $intH . '"', $patchedTag, 1);
    }
    if ($hasNonePreserve) {
        /* Drop the attribute entirely so the browser default of `xMidYMid meet`
           kicks in. Avoid leaving an empty attribute or doubled whitespace. */
        $patchedTag = preg_replace('/\s+preserveAspectRatio\s*=\s*"none"/i', '', $patchedTag, 1);
    }

    if ($patchedTag === $tag) {
        $skipped++;
        continue;
    }

    $newContent = substr_replace($content, $patchedTag, $offset, strlen($tag));
    file_put_contents($path, $newContent);
    $patched++;
}

echo "Patched: $patched\nSkipped: $skipped\nBroken/unparsable: $broken\n";
