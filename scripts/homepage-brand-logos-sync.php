<?php

/**
 * Sideload optically normalized homepage brand SVGs and point the homepage
 * horizontal_scroller row at them.
 *
 * Run after `python3 scripts/normalize-brand-logos.py`.
 *
 *     ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *       wp-content/themes/culvers/scripts/homepage-brand-logos-sync.php
 */

if (! defined('WP_CLI') || ! WP_CLI || ! function_exists('update_field')) {
    exit(1);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

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

$themeDir = get_template_directory();
$logoDir = $themeDir . '/resources/images/homepage-brands';

$logoMap = [
    'Schuh' => 'schuh.svg',
    'Accessorize London' => 'accessorize-london.svg',
    'H&M' => 'hm.svg',
    'Pandora' => 'pandora.svg',
    'TK Maxx' => 'tk-maxx.svg',
];

/**
 * @return array<string, mixed>|null
 */
function culvers_sideload_brand_logo(string $absolutePath, string $alt): ?array
{
    if (! is_readable($absolutePath)) {
        \WP_CLI::warning('Missing logo file: ' . $absolutePath);

        return null;
    }

    $baseName = 'homepage-brand-' . sanitize_file_name(pathinfo($absolutePath, PATHINFO_FILENAME)) . '.svg';
    $tmp = wp_tempnam($baseName);
    if (! $tmp || ! @copy($absolutePath, $tmp)) {
        \WP_CLI::warning('Temp copy failed for ' . $absolutePath);

        return null;
    }

    $attachId = media_handle_sideload([
        'name' => $baseName,
        'tmp_name' => $tmp,
    ], 0, $alt);

    @unlink($tmp);

    if (is_wp_error($attachId)) {
        \WP_CLI::warning('Sideload failed for ' . $baseName . ': ' . $attachId->get_error_message());

        return null;
    }

    $src = wp_get_attachment_image_src((int) $attachId, 'full');
    if (! is_array($src) || empty($src[0])) {
        return null;
    }

    return [
        'id' => (int) $attachId,
        'url' => $src[0],
        'width' => isset($src[1]) ? (int) $src[1] : null,
        'height' => isset($src[2]) ? (int) $src[2] : null,
        'alt' => $alt,
    ];
}

$frontId = (int) get_option('page_on_front');
if ($frontId <= 0) {
    \WP_CLI::error('No front page set.');
}

$rows = get_field('components', $frontId);
if (! is_array($rows)) {
    $rows = [];
}

$patched = false;
foreach ($rows as $index => $row) {
    if (($row['acf_fc_layout'] ?? '') !== 'horizontal_scroller') {
        continue;
    }

    $row['scroller_preset'] = 'homepage_brands';
    $items = is_array($row['scroller_items'] ?? null) ? $row['scroller_items'] : [];
    $nextItems = [];

    foreach ($items as $item) {
        if (! is_array($item)) {
            continue;
        }

        $alt = trim((string) ($item['item_image_alt'] ?? ''));
        if ($alt === '' && is_array($item['item_image'] ?? null)) {
            $alt = trim((string) ($item['item_image']['alt'] ?? ''));
        }

        $file = $logoMap[$alt] ?? null;
        if ($file === null) {
            $nextItems[] = $item;
            continue;
        }

        $absolute = $logoDir . '/' . $file;
        $image = culvers_sideload_brand_logo($absolute, $alt);
        if ($image === null) {
            $nextItems[] = $item;
            continue;
        }

        $item['item_type'] = 'image';
        $item['item_size'] = 'medium';
        $item['item_vertical_offset'] = 'center';
        $item['item_aspect_ratio'] = 'landscape';
        $item['item_image'] = (int) $image['id'];
        $item['item_image_alt'] = $alt;
        $nextItems[] = $item;

        \WP_CLI::log(sprintf('Updated %s → attachment %d (%s)', $alt, $image['id'], basename($image['url'])));
    }

    $row['scroller_items'] = $nextItems;
    $rows[$index] = $row;
    $patched = true;
    break;
}

if (! $patched) {
    \WP_CLI::error('No horizontal_scroller row on the homepage.');
}

update_field('components', $rows, $frontId);
\WP_CLI::success('Homepage brand logos synced from resources/images/homepage-brands/.');
