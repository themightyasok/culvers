<?php

/**
 * Replace 0-byte upload files from staging backup zip.
 *
 * @phpstan-ignore-file
 */

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

$backupZip = '/Users/admin/Work/Society/Culvers/staging-backup-20260601-000949.zip';
$zipPrefix = '.staging-backup-20260601-000949/uploads/';

if (! is_readable($backupZip)) {
    \WP_CLI::error('Backup zip missing.');
}

$uploadDir = wp_upload_dir();
$basedir = is_string($uploadDir['basedir'] ?? null) ? $uploadDir['basedir'] : '';

/** @var list<string> $zeroFiles */
$zeroFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($basedir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
    if (! $fileInfo->isFile() || $fileInfo->getSize() !== 0) {
        continue;
    }
    $zeroFiles[] = $fileInfo->getPathname();
}

if ($zeroFiles === []) {
    \WP_CLI::success('No zero-byte files under uploads.');
    return;
}

\WP_CLI::log('Zero-byte files: ' . count($zeroFiles));

$fixed = 0;
$failed = 0;

foreach ($zeroFiles as $abs) {
    $relative = ltrim(substr($abs, strlen($basedir)), '/');
    $zipPath = $zipPrefix . $relative;

    $cmd = sprintf(
        'unzip -p %s %s > %s',
        escapeshellarg($backupZip),
        escapeshellarg($zipPath),
        escapeshellarg($abs)
    );
    exec($cmd, $out, $code);

    $size = is_readable($abs) ? (int) filesize($abs) : 0;
    if ($code !== 0 || $size <= 0) {
        \WP_CLI::warning("Still empty: $relative");
        $failed++;
        continue;
    }

    \WP_CLI::log("Fixed: $relative ($size bytes)");
    $fixed++;

    $attId = (int) $GLOBALS['wpdb']->get_var(
        $GLOBALS['wpdb']->prepare(
            "SELECT post_id FROM {$GLOBALS['wpdb']->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
            $relative
        )
    );

    if ($attId > 0) {
        $meta = wp_generate_attachment_metadata($attId, $abs);
        if (is_array($meta)) {
            wp_update_attachment_metadata($attId, $meta);
        }
    }
}

\WP_CLI::success("Repaired $fixed files, $failed still empty.");
