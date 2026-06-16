<?php

/**
 * Scan published site URLs for broken <img> / srcset / CSS background image URLs.
 *
 *   cd app/public && ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/scan-broken-images.php
 *
 * @phpstan-ignore-file
 */

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI.\n");
    exit(1);
}

$base = rtrim(home_url(), '/');
$paths = ['/'];

$postTypes = [
    'page',
    'culvers_shop',
    'culvers_eat_drink',
    'culvers_career',
    'culvers_offer',
    'culvers_event',
    'culvers_news',
];

foreach ($postTypes as $postType) {
    $posts = get_posts([
        'post_type' => $postType,
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
    ]);

    foreach ($posts as $postId) {
        $link = get_permalink((int) $postId);
        if (! is_string($link) || $link === '') {
            continue;
        }
        $path = wp_parse_url($link, PHP_URL_PATH);
        if (is_string($path) && $path !== '') {
            $paths[] = $path;
        }
    }
}

// CPT archives
$archives = [];
foreach ($postTypes as $postType) {
    if ($postType === 'page') {
        continue;
    }
    $link = get_post_type_archive_link($postType);
    if (is_string($link) && $link !== '') {
        $path = wp_parse_url($link, PHP_URL_PATH);
        if (is_string($path) && $path !== '') {
            $archives[] = $path;
        }
    }
}
$archives[] = '/whats-on/';
foreach ($archives as $archivePath) {
    $paths[] = $archivePath;
}

$paths = array_values(array_unique($paths));
sort($paths);

/**
 * @return list<string>
 */
function culvers_extract_image_urls(string $html, string $pageUrl): array
{
    $urls = [];

    if (preg_match_all('/<img\b[^>]+>/i', $html, $tags)) {
        foreach ($tags[0] as $tag) {
            if (preg_match('/\bsrc=["\']([^"\']+)["\']/i', $tag, $m)) {
                $urls[] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            if (preg_match('/\bsrcset=["\']([^"\']+)["\']/i', $tag, $m)) {
                foreach (preg_split('/\s*,\s*/', $m[1]) ?: [] as $part) {
                    $piece = trim(preg_split('/\s+/', trim($part))[0] ?? '');
                    if ($piece !== '') {
                        $urls[] = html_entity_decode($piece, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                }
            }
        }
    }

    if (preg_match_all('/url\(["\']?([^"\')\s]+)["\']?\)/i', $html, $bg)) {
        foreach ($bg[1] as $u) {
            if (str_contains($u, 'wp-content/uploads') || str_contains($u, '.jpg') || str_contains($u, '.png') || str_contains($u, '.webp') || str_contains($u, '.svg')) {
                $urls[] = html_entity_decode($u, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
    }

    $resolved = [];
    foreach ($urls as $url) {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, 'data:')) {
            continue;
        }
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        } elseif (str_starts_with($url, '/')) {
            $parts = wp_parse_url($pageUrl);
            $scheme = is_string($parts['scheme'] ?? null) ? $parts['scheme'] : 'https';
            $host = is_string($parts['host'] ?? null) ? $parts['host'] : 'localhost';
            $url = $scheme . '://' . $host . $url;
        }
        $resolved[$url] = $url;
    }

    return array_values($resolved);
}

/**
 * @return array{code: int, error: string}
 */
function culvers_head_image(string $url): array
{
    $ch = curl_init($url);
    if ($ch === false) {
        return ['code' => 0, 'error' => 'curl_init failed'];
    }

    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'culvers-broken-image-scan/1.0',
    ]);

    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return ['code' => $code, 'error' => $err];
}

/** @var list<array<string, mixed>> $broken */
$broken = [];
/** @var list<array<string, mixed>> $pageErrors */
$pageErrors = [];
$checkedImages = 0;

foreach ($paths as $path) {
    $pageUrl = $base . $path;
    $ch = curl_init($pageUrl);
    if ($ch === false) {
        $pageErrors[] = ['path' => $path, 'error' => 'curl_init'];
        continue;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'culvers-broken-image-scan/1.0',
    ]);

    $html = curl_exec($ch);
    $pageCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (! is_string($html) || $pageCode >= 400) {
        $pageErrors[] = ['path' => $path, 'http' => $pageCode];
        continue;
    }

    foreach (culvers_extract_image_urls($html, $pageUrl) as $imgUrl) {
        // Only check same-site uploads and theme assets (skip typekit, external CDNs except when broken on page)
        $host = wp_parse_url($imgUrl, PHP_URL_HOST);
        $baseHost = wp_parse_url($base, PHP_URL_HOST);
        $isLocal = is_string($host) && is_string($baseHost) && $host === $baseHost;
        $isUploads = str_contains($imgUrl, '/wp-content/uploads/');
        $isTheme = str_contains($imgUrl, '/wp-content/themes/culvers/');

        if (! $isLocal && ! $isUploads) {
            continue;
        }
        if (! $isUploads && ! $isTheme) {
            continue;
        }

        $checkedImages++;
        $key = $imgUrl;
        static $cache = [];
        if (! isset($cache[$key])) {
            $cache[$key] = culvers_head_image($imgUrl);
        }
        $result = $cache[$key];
        if ($result['code'] < 200 || $result['code'] >= 400) {
            $broken[] = [
                'page' => $path,
                'image' => $imgUrl,
                'http' => $result['code'],
                'curl_error' => $result['error'],
            ];
        }
    }
}

// DB: referenced attachment IDs that no longer exist
global $wpdb;
/** @var list<array<string, mixed>> $missingIds */
$missingIds = [];
$acfMediaSuffix = '/(_image|_logo|_poster|_file|_graphic|_icon|_photo|_illustration|_slide_image|_card_image|_background_image|_brand_logo|_employer_logo|_sidebar_brand_logo|_card_video)(_|$)/i';
$acfMediaExclude = '/(_tilt|_show_|_categories|_opacity|_level|_ratio|_semantic|_tone|_preserve|_source|_flush|_items$|_rows$|_slides$|_meta$|_sections$|_tabs$|_lists$)/i';
$knownKeys = ['shop_logo', 'eat_drink_logo', 'career_employer_logo', 'offer_card_image', 'news_card_image', 'event_card_image', '_thumbnail_id', '_culvers_mega_preview_attachment_id'];

$metaRows = $wpdb->get_results(
    "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
     WHERE (meta_key REGEXP '^components_[0-9]+_' AND meta_value REGEXP '^[0-9]+$')
     OR meta_key IN ('" . implode("','", array_map('esc_sql', $knownKeys)) . "')",
    ARRAY_A
);

foreach ($metaRows ?: [] as $row) {
    $key = (string) ($row['meta_key'] ?? '');
    if (preg_match($acfMediaExclude, $key)) {
        continue;
    }
    if (str_starts_with($key, 'components_') && ! preg_match($acfMediaSuffix, $key)) {
        continue;
    }
    $id = (int) ($row['meta_value'] ?? 0);
    if ($id <= 0) {
        continue;
    }
    $post = get_post($id);
    if ($post instanceof WP_Post && $post->post_type === 'attachment') {
        continue;
    }
    $missingIds[] = [
        'post_id' => (int) ($row['post_id'] ?? 0),
        'meta_key' => $key,
        'attachment_id' => $id,
    ];
}

$reportPath = get_template_directory() . '/storage/broken-images-scan.json';
$report = [
    'generated_at' => gmdate('c'),
    'site' => $base,
    'pages_scanned' => count($paths),
    'local_images_checked' => $checkedImages,
    'broken_images_on_pages' => $broken,
    'page_fetch_errors' => $pageErrors,
    'missing_attachment_ids_in_meta' => $missingIds,
];

file_put_contents($reportPath, wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

\WP_CLI::log('Pages scanned: ' . count($paths));
\WP_CLI::log('Local upload/theme image URLs checked: ' . $checkedImages);
\WP_CLI::log('Broken image URLs on pages: ' . count($broken));
\WP_CLI::log('Missing attachment IDs in meta: ' . count($missingIds));
\WP_CLI::log('Page fetch errors: ' . count($pageErrors));
\WP_CLI::success('Report: ' . $reportPath);

if ($broken !== []) {
    foreach (array_slice($broken, 0, 30) as $item) {
        \WP_CLI::log(sprintf('  [%d] %s -> %s', $item['http'], $item['page'], $item['image']));
    }
    if (count($broken) > 30) {
        \WP_CLI::log('  ... and ' . (count($broken) - 30) . ' more');
    }
}

if ($missingIds !== []) {
    foreach (array_slice($missingIds, 0, 20) as $item) {
        \WP_CLI::log(sprintf('  missing #%d in post %d meta %s', $item['attachment_id'], $item['post_id'], $item['meta_key']));
    }
}
