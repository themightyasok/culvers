<?php

/**
 * Read-only audit: which media library attachments are referenced by the site.
 *
 * Run (local):
 *   cd app/public && ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/audit-media-library-usage.php
 *
 * Optional report path (default: wp-content/themes/culvers/storage/media-library-audit.json):
 *   ... audit-media-library-usage.php -- /absolute/or/relative/report.json
 *
 * Does not delete or modify posts, meta, or files.
 *
 * @phpstan-ignore-file
 */

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    fwrite(STDERR, "Run via WP-CLI (wp eval-file).\n");
    exit(1);
}

global $wpdb;

$argv = $_SERVER['argv'] ?? [];
$reportPath = '';
foreach ($argv as $i => $arg) {
    if ($arg === '--' && isset($argv[$i + 1])) {
        $reportPath = (string) $argv[$i + 1];
        break;
    }
}

if ($reportPath === '') {
    $reportPath = get_template_directory() . '/storage/media-library-audit.json';
} elseif (! str_starts_with($reportPath, '/')) {
    $reportPath = ABSPATH . ltrim($reportPath, '/');
}

/**
 * @return array<int, true>
 */
function culvers_audit_attachment_index(): array
{
    global $wpdb;

    /** @var array<int, true> $index */
    $index = [];
    $rows = $wpdb->get_results(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_type = 'attachment'
         AND post_status IN ('inherit', 'publish', 'private', 'future')",
        ARRAY_A
    );

    foreach ($rows ?: [] as $row) {
        $id = (int) ($row['ID'] ?? 0);
        if ($id > 0) {
            $index[$id] = true;
        }
    }

    return $index;
}

/**
 * @param array<int, true> $attachments
 */
function culvers_audit_mark_used(int $id, string $source, array &$used, array $attachments, array &$usedSources): void
{
    if ($id <= 0 || ! isset($attachments[$id])) {
        return;
    }

    $used[$id] = true;
    if (! isset($usedSources[$id])) {
        $usedSources[$id] = [];
    }
    if (! in_array($source, $usedSources[$id], true)) {
        $usedSources[$id][] = $source;
    }
}

/**
 * @param array<string, int> $pathToId
 */
function culvers_audit_mark_paths_in_string(string $haystack, string $source, array &$used, array $attachments, array &$usedSources, array $pathToId): void
{
    if ($haystack === '') {
        return;
    }

    if (preg_match_all('#wp-content/uploads/([^\s"\'<>]+)#i', $haystack, $matches)) {
        foreach ($matches[1] as $relative) {
            $relative = rawurldecode((string) $relative);
            $relative = preg_replace('#\?.*$#', '', $relative) ?? $relative;
            $relative = ltrim(str_replace('\\', '/', $relative), '/');

            if ($relative === '') {
                continue;
            }

            if (isset($pathToId[$relative])) {
                culvers_audit_mark_used($pathToId[$relative], $source . ':url', $used, $attachments, $usedSources);
            }

            // WordPress scaled variants: strip -WxH before extension.
            if (preg_match('#^(.+)-\d+x\d+(\.[a-z0-9]+)$#i', $relative, $scaled)) {
                $base = $scaled[1] . $scaled[2];
                if (isset($pathToId[$base])) {
                    culvers_audit_mark_used($pathToId[$base], $source . ':url-scaled', $used, $attachments, $usedSources);
                }
            }
        }
    }

    // Serialized ACF image arrays often store "ID";i:123 or "id";i:123
    if (preg_match_all('/["\']ID["\'];i:(\d+)/', $haystack, $idMatches)) {
        foreach ($idMatches[1] as $rawId) {
            culvers_audit_mark_used((int) $rawId, $source . ':acf-id', $used, $attachments, $usedSources);
        }
    }

    // wp-image-123 in block editor HTML
    if (preg_match_all('/wp-image-(\d+)/', $haystack, $wpImage)) {
        foreach ($wpImage[1] as $rawId) {
            culvers_audit_mark_used((int) $rawId, $source . ':wp-image-class', $used, $attachments, $usedSources);
        }
    }

    // gallery shortcode ids="123,456"
    if (preg_match_all('/ids=["\']([^"\']+)["\']/', $haystack, $gallery)) {
        foreach ($gallery[1] as $idList) {
            foreach (preg_split('/\s*,\s*/', (string) $idList) ?: [] as $piece) {
                if (ctype_digit(trim($piece))) {
                    culvers_audit_mark_used((int) trim($piece), $source . ':gallery', $used, $attachments, $usedSources);
                }
            }
        }
    }
}

/**
 * @return array<string, int>
 */
function culvers_audit_build_path_index(): array
{
    global $wpdb;

    /** @var array<string, int> $map */
    $map = [];
    $rows = $wpdb->get_results(
        "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file'",
        ARRAY_A
    );

    foreach ($rows ?: [] as $row) {
        $id = (int) ($row['post_id'] ?? 0);
        $path = is_string($row['meta_value'] ?? null) ? trim($row['meta_value']) : '';
        if ($id > 0 && $path !== '') {
            $map[$path] = $id;
        }
    }

    return $map;
}

/**
 * @param array<int, true> $attachments
 * @param array<string, int> $pathToId
 */
function culvers_audit_scan_options(array &$used, array $attachments, array &$usedSources, array $pathToId): void
{
    global $wpdb;

    $optionKeys = [
        'site_icon',
        'custom_logo',
        'culvers_figma_panel_attachment_map',
    ];

    foreach ($optionKeys as $key) {
        $val = get_option($key);
        if (is_numeric($val)) {
            culvers_audit_mark_used((int) $val, 'option:' . $key, $used, $attachments, $usedSources);
        } elseif (is_array($val)) {
            foreach ($val as $maybeId) {
                if (is_numeric($maybeId)) {
                    culvers_audit_mark_used((int) $maybeId, 'option:' . $key, $used, $attachments, $usedSources);
                }
            }
            culvers_audit_mark_paths_in_string(wp_json_encode($val) ?: '', 'option:' . $key, $used, $attachments, $usedSources, $pathToId);
        }
    }

    $mods = get_option('theme_mods_culvers');
    if (is_array($mods)) {
        if (isset($mods['custom_logo']) && is_numeric($mods['custom_logo'])) {
            culvers_audit_mark_used((int) $mods['custom_logo'], 'theme_mod:custom_logo', $used, $attachments, $usedSources);
        }
        culvers_audit_mark_paths_in_string(wp_json_encode($mods) ?: '', 'theme_mods_culvers', $used, $attachments, $usedSources, $pathToId);
    }

    // Any option value mentioning uploads (Customizer, ACF options pages, etc.)
    $like = '%' . $wpdb->esc_like('wp-content/uploads/') . '%';
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options}
             WHERE option_value LIKE %s
             AND option_name NOT LIKE %s
             LIMIT 5000",
            $like,
            $wpdb->esc_like('_transient_%')
        ),
        ARRAY_A
    );

    foreach ($rows ?: [] as $row) {
        $name = (string) ($row['option_name'] ?? '');
        $value = is_string($row['option_value'] ?? null) ? $row['option_value'] : '';
        culvers_audit_mark_paths_in_string($value, 'option:' . $name, $used, $attachments, $usedSources, $pathToId);
        if (ctype_digit(trim($value))) {
            culvers_audit_mark_used((int) $value, 'option-numeric:' . $name, $used, $attachments, $usedSources);
        }
    }
}

$attachments = culvers_audit_attachment_index();
$pathToId = culvers_audit_build_path_index();

/** @var array<int, true> $used */
$used = [];
/** @var array<int, list<string>> $usedSources */
$usedSources = [];

// 1. Featured images
$thumbRows = $wpdb->get_results(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id'",
    ARRAY_A
);
foreach ($thumbRows ?: [] as $row) {
    culvers_audit_mark_used((int) ($row['meta_value'] ?? 0), 'postmeta:_thumbnail_id', $used, $attachments, $usedSources);
}

// 2. Nav mega previews
$previewRows = $wpdb->get_results(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_culvers_mega_preview_attachment_id'",
    ARRAY_A
);
foreach ($previewRows ?: [] as $row) {
    culvers_audit_mark_used((int) ($row['meta_value'] ?? 0), 'postmeta:_culvers_mega_preview_attachment_id', $used, $attachments, $usedSources);
}

// 3a. ACF flexible rows: components_{n}_* keys that store image / file attachment IDs.
$acfMediaRows = $wpdb->get_results(
    "SELECT meta_key, meta_value FROM {$wpdb->postmeta}
     WHERE meta_key REGEXP '^components_[0-9]+_'
     AND meta_value REGEXP '^[0-9]+$'
     AND LENGTH(meta_value) <= 8",
    ARRAY_A
);
$acfMediaSuffix = '/(_image|_logo|_video|_poster|_file|_graphic|_icon|_photo|_map|_illustration|_slide_image|_card_image|_background_image|_brand_logo|_employer_logo|_sidebar_brand_logo)(_|$)/i';

foreach ($acfMediaRows ?: [] as $row) {
    $key = (string) ($row['meta_key'] ?? '');
    if (! preg_match($acfMediaSuffix, $key)) {
        continue;
    }
    culvers_audit_mark_used((int) ($row['meta_value'] ?? 0), 'acf-flex:' . $key, $used, $attachments, $usedSources);
}

// 3b. Directory / archive / footer ACF fields (non-flexible post meta).
$knownImageMetaKeys = [
    'shop_logo',
    'eat_drink_logo',
    'career_employer_logo',
    'offer_card_image',
    'news_card_image',
    'event_card_image',
    'culvers_footer_newsletter_image',
    'culvers_footer_newsletter_image_mobile',
    'shops_directory_hero_image',
    'shops_directory_hero_image_mobile',
    'eat_drink_directory_hero_image',
    'eat_drink_directory_hero_image_mobile',
    'careers_directory_hero_image',
    'careers_directory_hero_image_mobile',
    'news_directory_hero_image',
    'events_directory_hero_image',
    'offers_directory_hero_image',
];

foreach ($knownImageMetaKeys as $metaKey) {
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value REGEXP '^[0-9]+$'",
            $metaKey
        ),
        ARRAY_A
    );
    foreach ($rows ?: [] as $row) {
        culvers_audit_mark_used((int) ($row['meta_value'] ?? 0), 'acf-field:' . $metaKey, $used, $attachments, $usedSources);
    }
}

// 4. Postmeta + posts: URLs, serialized ACF, wp-image classes (chunked)
$chunk = 500;
$offset = 0;
do {
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
             WHERE meta_key NOT IN ('_wp_attached_file', '_wp_attachment_metadata', '_edit_lock', '_edit_last')
             AND (meta_value LIKE %s OR meta_value REGEXP %s OR meta_value REGEXP %s)
             LIMIT %d OFFSET %d",
            '%wp-content/uploads/%',
            '%"ID";i:%',
            '%wp-image-%',
            $chunk,
            $offset
        ),
        ARRAY_A
    );

    foreach ($rows ?: [] as $row) {
        $value = is_string($row['meta_value'] ?? null) ? $row['meta_value'] : '';
        $key = (string) ($row['meta_key'] ?? '');
        culvers_audit_mark_paths_in_string($value, 'postmeta:' . $key, $used, $attachments, $usedSources, $pathToId);
    }

    $offset += $chunk;
} while (is_array($rows) && count($rows) === $chunk);

// 5. Post content
$postOffset = 0;
do {
    $posts = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_content FROM {$wpdb->posts}
             WHERE post_status NOT IN ('auto-draft', 'trash')
             AND post_type NOT IN ('attachment', 'revision', 'nav_menu_item', 'customize_changeset')
             AND (post_content LIKE %s OR post_content REGEXP %s)
             LIMIT %d OFFSET %d",
            '%wp-content/uploads/%',
            '%wp-image-%',
            $chunk,
            $postOffset
        ),
        ARRAY_A
    );

    foreach ($posts ?: [] as $post) {
        $content = is_string($post['post_content'] ?? null) ? $post['post_content'] : '';
        culvers_audit_mark_paths_in_string($content, 'post_content:' . ($post['ID'] ?? ''), $used, $attachments, $usedSources, $pathToId);
    }

    $postOffset += $chunk;
} while (is_array($posts) && count($posts) === $chunk);

culvers_audit_scan_options($used, $attachments, $usedSources, $pathToId);

// 6. Attachment post_parent (uploaded "to" a post — weak signal; tag separately)
/** @var array<int, int> $parentLinked */
$parentLinked = [];
$parentRows = $wpdb->get_results(
    "SELECT ID, post_parent FROM {$wpdb->posts}
     WHERE post_type = 'attachment' AND post_parent > 0",
    ARRAY_A
);
foreach ($parentRows ?: [] as $row) {
    $id = (int) ($row['ID'] ?? 0);
    $parent = (int) ($row['post_parent'] ?? 0);
    if ($id > 0 && $parent > 0 && get_post_status($parent)) {
        $parentLinked[$id] = $parent;
    }
}

// Build attachment detail rows
$uploadDir = wp_upload_dir();
$basedir = is_string($uploadDir['basedir'] ?? null) ? $uploadDir['basedir'] : '';

/** @var list<array<string, mixed>> $usedList */
$usedList = [];
/** @var list<array<string, mixed>> $unusedList */
$unusedList = [];
/** @var list<array<string, mixed>> $parentOnlyList */
$parentOnlyList = [];

$totalBytes = 0;
$unusedBytes = 0;
$mimeUnused = [];

foreach (array_keys($attachments) as $id) {
    $file = get_attached_file($id) ?: '';
    $bytes = ($file !== '' && is_readable($file)) ? (int) filesize($file) : 0;
    $totalBytes += $bytes;

    $mime = get_post_mime_type($id) ?: '';
    $title = get_the_title($id);
    $relative = is_string($file) && $basedir !== '' && str_starts_with($file, $basedir)
        ? ltrim(substr($file, strlen($basedir)), '/')
        : $file;

    $row = [
        'id' => $id,
        'title' => $title,
        'mime' => $mime,
        'file' => $relative,
        'bytes' => $bytes,
        'human_size' => size_format($bytes),
        'url' => wp_get_attachment_url($id) ?: '',
    ];

    if (isset($used[$id])) {
        $row['sources'] = $usedSources[$id] ?? [];
        $usedList[] = $row;
        continue;
    }

    if (isset($parentLinked[$id])) {
        $row['post_parent'] = $parentLinked[$id];
        $row['note'] = 'Has post_parent but no content/meta reference found';
        $parentOnlyList[] = $row;
        $unusedBytes += $bytes;
        $mimeUnused[$mime] = ($mimeUnused[$mime] ?? 0) + 1;
        continue;
    }

    $unusedList[] = $row;
    $unusedBytes += $bytes;
    $mimeUnused[$mime] = ($mimeUnused[$mime] ?? 0) + 1;
}

// Files on disk under uploads/ not registered as attachments
/** @var list<string> $orphanFiles */
$orphanFiles = [];
if ($basedir !== '' && is_dir($basedir)) {
    $registeredPaths = array_flip(array_keys($pathToId));
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($basedir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (! $fileInfo->isFile()) {
            continue;
        }
        $abs = $fileInfo->getPathname();
        $rel = ltrim(substr($abs, strlen($basedir)), '/');
        if (! isset($registeredPaths[$rel])) {
            $orphanFiles[] = $rel;
        }
    }
}

usort($unusedList, static fn (array $a, array $b): int => ($b['bytes'] ?? 0) <=> ($a['bytes'] ?? 0));
usort($parentOnlyList, static fn (array $a, array $b): int => ($b['bytes'] ?? 0) <=> ($a['bytes'] ?? 0));

$report = [
    'generated_at' => gmdate('c'),
    'site_url' => home_url('/'),
    'summary' => [
        'attachments_in_library' => count($attachments),
        'referenced_in_content_or_meta' => count($usedList),
        'unreferenced_no_parent' => count($unusedList),
        'parent_only_unreferenced' => count($parentOnlyList),
        'uploads_files_not_in_library' => count($orphanFiles),
        'library_total_bytes' => $totalBytes,
        'library_total_human' => size_format($totalBytes),
        'unreferenced_bytes' => $unusedBytes,
        'unreferenced_human' => size_format($unusedBytes),
        'unreferenced_by_mime' => $mimeUnused,
    ],
    'used_attachments' => $usedList,
    'unused_attachments' => $unusedList,
    'parent_only_attachments' => $parentOnlyList,
    'uploads_files_without_attachment_post' => array_slice($orphanFiles, 0, 500),
    'uploads_files_without_attachment_post_truncated' => count($orphanFiles) > 500,
    'notes' => [
        'unused_attachments are not referenced in scanned postmeta, post_content, or known options.',
        'parent_only_attachments have post_parent set but were not found in flexible content, thumbnails, mega previews, or upload URLs in meta.',
        'Review parent_only and unused lists before deleting — some may be intentional spares or future use.',
        'Deleting unused attachments does not remove generated image sizes until files are removed from uploads/.',
    ],
];

$dir = dirname($reportPath);
if (! is_dir($dir)) {
    wp_mkdir_p($dir);
}

file_put_contents($reportPath, wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

$csvPath = preg_replace('/\.json$/', '-unused.csv', $reportPath) ?: $reportPath . '-unused.csv';
$fh = fopen($csvPath, 'w');
if ($fh !== false) {
    fputcsv($fh, ['id', 'title', 'mime', 'bytes', 'file', 'url']);
    foreach ($unusedList as $item) {
        fputcsv($fh, [
            $item['id'],
            $item['title'],
            $item['mime'],
            $item['bytes'],
            $item['file'],
            $item['url'],
        ]);
    }
    fclose($fh);
}

\WP_CLI::success('Media library audit written.');
\WP_CLI::log('JSON: ' . $reportPath);
\WP_CLI::log('CSV (unused only): ' . $csvPath);
\WP_CLI::log(sprintf(
    'Summary: %d in library, %d used, %d unused, %d parent-only, %d disk files without attachment row',
    $report['summary']['attachments_in_library'],
    $report['summary']['referenced_in_content_or_meta'],
    $report['summary']['unreferenced_no_parent'],
    $report['summary']['parent_only_unreferenced'],
    $report['summary']['uploads_files_not_in_library']
));
\WP_CLI::log('Unreferenced size (unused + parent-only): ' . $report['summary']['unreferenced_human']);
