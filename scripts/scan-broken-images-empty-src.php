<?php

/**
 * Find empty <img src> or 404 uploads on rendered pages.
 *
 * @phpstan-ignore-file
 */

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

$base = rtrim(home_url(), '/');
/** @var list<string> $paths */
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
    if ($postType !== 'page') {
        $archive = get_post_type_archive_link($postType);
        if (is_string($archive) && $archive !== '') {
            $path = wp_parse_url($archive, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                $paths[] = $path;
            }
        }
    }

    $posts = get_posts([
        'post_type' => $postType,
        'post_status' => 'publish',
        'numberposts' => -1,
    ]);

    foreach ($posts as $post) {
        $link = get_permalink($post);
        if (! is_string($link)) {
            continue;
        }
        $path = wp_parse_url($link, PHP_URL_PATH);
        if (is_string($path) && $path !== '') {
            $paths[] = $path;
        }
    }
}

$paths = array_values(array_unique($paths));

/** @var list<array<string, mixed>> $issues */
$issues = [];

foreach ($paths as $path) {
    $url = $base . $path;
    $html = @file_get_contents($url);
    if (! is_string($html) || $html === '') {
        $issues[] = ['page' => $path, 'issue' => 'page_fetch_failed'];
        continue;
    }

    if (! preg_match_all('/<img\b[^>]*>/i', $html, $tags)) {
        continue;
    }

    foreach ($tags[0] as $tag) {
        if (! preg_match('/\bsrc=["\']([^"\']*)["\']/i', $tag, $m)) {
            continue;
        }

        $src = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($src === '') {
            $issues[] = ['page' => $path, 'issue' => 'empty_img_src', 'tag' => substr($tag, 0, 160)];
            continue;
        }

        if (str_starts_with($src, 'data:')) {
            continue;
        }

        $full = str_starts_with($src, '/') ? $base . $src : $src;
        if (! str_contains($full, '/wp-content/uploads/')) {
            continue;
        }

        $headers = @get_headers($full);
        $code = 0;
        if (is_array($headers) && isset($headers[0]) && preg_match('/ (\d{3}) /', (string) $headers[0], $c)) {
            $code = (int) $c[1];
        }

        if ($code === 0 || $code >= 400) {
            $issues[] = ['page' => $path, 'issue' => 'upload_404', 'src' => $full, 'http' => $code];
        }
    }
}

\WP_CLI::log('Pages: ' . count($paths));
\WP_CLI::log('Issues: ' . count($issues));
foreach ($issues as $issue) {
    \WP_CLI::log(wp_json_encode($issue) ?: '');
}
