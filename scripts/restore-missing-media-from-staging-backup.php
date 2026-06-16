<?php

/**
 * Restore attachment posts + files referenced in ACF options but removed by media cleanup.
 *
 * Reads paths from staging-backup SQL and copies files from staging uploads zip.
 *
 *   cd app/public && ./wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/restore-missing-media-from-staging-backup.php
 *
 * @phpstan-ignore-file
 */

if (! defined('WPINC') || ! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

$backupSql = '/Users/admin/Work/Society/Culvers/staging-backup-20260601-000949.sql';
$backupZip = '/Users/admin/Work/Society/Culvers/staging-backup-20260601-000949.zip';

if (! is_readable($backupSql) || ! is_readable($backupZip)) {
    \WP_CLI::error('Staging backup SQL/zip not found in project root.');
}

global $wpdb;

/** @var list<int> $neededIds */
$neededIds = [];

$rows = $wpdb->get_results(
    "SELECT option_name, option_value FROM {$wpdb->options}
     WHERE option_name LIKE 'options\\_%'
     AND option_value REGEXP '^[0-9]+$'
     AND LENGTH(option_value) <= 6",
    ARRAY_A
);

foreach ($rows ?: [] as $row) {
    $name = (string) ($row['option_name'] ?? '');
    if (! preg_match('/_(hero_image|hero_image_mobile|footer_newsletter_image|footer_newsletter_image_mobile|slide_image)$/', $name)
        && ! preg_match('/_hero_slides_[0-9]+_slide_image$/', $name)) {
        continue;
    }
    $id = (int) ($row['option_value'] ?? 0);
    if ($id > 0 && ! get_post($id)) {
        $neededIds[$id] = $id;
    }
}

if ($neededIds === []) {
    \WP_CLI::success('No missing option-linked attachments to restore.');
    return;
}

\WP_CLI::log('Missing attachment IDs from options: ' . implode(', ', array_values($neededIds)));

$sql = (string) file_get_contents($backupSql);
/** @var array<int, string> $paths */
$paths = [];

foreach ($neededIds as $id) {
    if (preg_match('/,' . $id . ',\'_wp_attached_file\',\'([^\']+)\'/', $sql, $m)) {
        $paths[$id] = $m[1];
    }
}

if ($paths === []) {
    \WP_CLI::error('Could not resolve file paths from staging SQL for missing IDs.');
}

$uploadDir = wp_upload_dir();
$basedir = is_string($uploadDir['basedir'] ?? null) ? $uploadDir['basedir'] : '';
$restored = 0;
$failed = 0;

foreach ($paths as $oldId => $relative) {
    $dest = $basedir . '/' . $relative;
    if (is_readable($dest)) {
        \WP_CLI::log("File already on disk: $relative");
    } else {
        $zipPath = '.staging-backup-20260601-000949/uploads/' . $relative;
        $dir = dirname($dest);
        if (! is_dir($dir)) {
            wp_mkdir_p($dir);
        }
        $tmp = $dest . '.restore-tmp';
        $cmd = sprintf(
            'unzip -p %s %s > %s',
            escapeshellarg($backupZip),
            escapeshellarg($zipPath),
            escapeshellarg($tmp)
        );
        exec($cmd, $out, $code);
        if ($code !== 0 || ! is_readable($tmp) || (int) filesize($tmp) <= 0) {
            @unlink($tmp);
            \WP_CLI::warning("Could not extract $zipPath from backup zip");
            $failed++;
            continue;
        }
        rename($tmp, $dest);
        \WP_CLI::log('Extracted: ' . $relative . ' (' . filesize($dest) . ' bytes)');
    }

    $existing = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
            $relative
        )
    );

    if ($existing) {
        \WP_CLI::log("Attachment already registered for $relative (#$existing)");
        if ((int) get_option('options_shops_archive_hero_image') === $oldId && ! get_post($oldId)) {
            update_option('options_shops_archive_hero_image', (string) (int) $existing);
        }
        $restored++;
        continue;
    }

    $title = basename($relative);
    $newId = wp_insert_attachment([
        'post_mime_type' => wp_check_filetype($dest)['type'] ?: 'image/jpeg',
        'post_title' => $title,
        'post_status' => 'inherit',
        'post_content' => '',
        'guid' => $uploadDir['baseurl'] . '/' . $relative,
    ], $dest);

    if (! is_numeric($newId) || (int) $newId <= 0) {
        \WP_CLI::warning("wp_insert_attachment failed for $relative");
        $failed++;
        continue;
    }

    $newId = (int) $newId;
    $metadata = wp_generate_attachment_metadata($newId, $dest);
    if (is_array($metadata)) {
        wp_update_attachment_metadata($newId, $metadata);
    }

    \WP_CLI::log("Registered #$newId for $relative (was #$oldId)");

    // Remap any options still pointing at the deleted ID.
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->options} SET option_value = %s WHERE option_value = %s",
            (string) $newId,
            (string) $oldId
        )
    );

    $restored++;
}

\WP_CLI::success("Restore pass done. Restored=$restored failed=$failed");
\WP_CLI::log('shops_archive_hero_image option now: ' . get_option('options_shops_archive_hero_image'));
\WP_CLI::log('get_field shops_archive_hero_image: ' . (get_field('shops_archive_hero_image', 'option') ? 'set' : 'empty'));
