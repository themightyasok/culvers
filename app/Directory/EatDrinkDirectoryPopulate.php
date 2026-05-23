<?php

declare(strict_types=1);

namespace App\Directory;

use App\Helpers\Cast;

/**
 * Upserts the five Figma Eat & Drink venues and removes placeholder retailers.
 *
 * @see scripts/eat-drink-directory-populate.php
 */
final class EatDrinkDirectoryPopulate
{
    private static ?bool $depsLoaded = null;

    public static function runSeed(bool $pruneOrphans = true): void
    {
        self::loadDependencies();

        if (! function_exists('update_field')) {
            self::cliError('ACF is required (update_field missing).');
        }

        $userId = Cast::toInt(apply_filters('culvers_eat_drink_directory_populate_user_id', 1));
        if ($userId > 0) {
            wp_set_current_user($userId);
        }

        $created = 0;
        $updated = 0;
        $trashed = 0;

        self::withDirectoryImportUploadFilters(static function () use (&$created, &$updated, &$trashed, $pruneOrphans): void {
            EatDrinkTaxonomySeeder::syncNow();

            foreach (EatDrinkDirectorySeedData::venues() as $row) {
                $postId = self::upsertVenuePost($row, $created, $updated);
                if ($postId <= 0) {
                    continue;
                }
                self::assignTerms($postId, $row['type_slug']);
            }

            if ($pruneOrphans) {
                $trashed = self::pruneOrphanVenues(EatDrinkDirectorySeedData::allowedSlugs());
            }
        });

        self::cliSuccess(
            sprintf(
                'Eat & Drink directory seed complete (%d created, %d updated, %d trashed).',
                $created,
                $updated,
                $trashed
            )
        );
    }

    public static function loadDependencies(): void
    {
        if (self::$depsLoaded === true) {
            return;
        }
        if (! function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (! function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
        if (! function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        self::$depsLoaded = true;
    }

    /**
     * @param array{
     *     title: string,
     *     slug: string,
     *     logo_url: string|null,
     *     logo_theme_file: string|null,
     *     featured_url: string|null,
     *     type_slug: string
     * } $row
     */
    private static function upsertVenuePost(array $row, int &$created, int &$updated): int
    {
        $title = trim($row['title']);
        $slug = sanitize_title($row['slug']);
        if ($title === '' || $slug === '') {
            return 0;
        }

        $existing = get_posts([
            'post_type' => 'culvers_eat_drink',
            'name' => $slug,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'suppress_filters' => true,
        ]);

        $postId = isset($existing[0]) ? (int) $existing[0] : 0;

        $payload = [
            'post_title' => $title,
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'culvers_eat_drink',
        ];

        if ($postId > 0) {
            $payload['ID'] = $postId;
            $result = wp_update_post($payload, true);
            if (is_wp_error($result)) {
                self::cliWarning($result->get_error_message());

                return 0;
            }
            $postId = (int) $result;
            $updated++;
        } else {
            $result = wp_insert_post($payload, true);
            if (is_wp_error($result)) {
                self::cliWarning($result->get_error_message());

                return 0;
            }
            $postId = (int) $result;
            $created++;
        }

        self::acfUpdateField('eat_drink_hours_summary', EatDrinkDirectorySeedData::DEFAULT_HOURS_LINE, $postId);

        $logoUrl = $row['logo_url'] !== null ? trim($row['logo_url']) : '';
        $themeFile = $row['logo_theme_file'] !== null ? trim($row['logo_theme_file']) : '';
        $featUrl = $row['featured_url'] !== null ? trim($row['featured_url']) : '';

        if ($logoUrl !== '' || $themeFile !== '') {
            self::acfUpdateField('eat_drink_logo', false, $postId);
            $logoId = 0;
            if ($logoUrl !== '') {
                $logoId = self::sideloadFromUrl($logoUrl, $slug . '-logo');
            } elseif ($themeFile !== '') {
                $logoId = self::sideloadFromThemeFile($themeFile, $slug . '-logo');
            }
            if ($logoId > 0) {
                self::acfUpdateField('eat_drink_logo', $logoId, $postId);
            }
        }

        if ($featUrl !== '') {
            delete_post_thumbnail($postId);
            $fid = self::sideloadFromUrl($featUrl, $slug . '-featured');
            if ($fid > 0) {
                set_post_thumbnail($postId, $fid);
            }
        }

        DirectoryFlexibleDefaults::persistDefaultsForPost($postId);

        return $postId;
    }

    /**
     * @param list<string> $allowedSlugs
     */
    private static function pruneOrphanVenues(array $allowedSlugs): int
    {
        $allowed = array_fill_keys($allowedSlugs, true);
        $trashed = 0;

        $posts = get_posts([
            'post_type' => 'culvers_eat_drink',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'suppress_filters' => true,
        ]);

        foreach ($posts as $postId) {
            $post = get_post((int) $postId);
            if (! $post instanceof \WP_Post) {
                continue;
            }
            if (isset($allowed[$post->post_name])) {
                continue;
            }
            if (wp_trash_post((int) $post->ID)) {
                $trashed++;
                self::cliWarning(sprintf('Trashed placeholder venue "%s" (%s).', $post->post_title, $post->post_name));
            }
        }

        return $trashed;
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private static function withDirectoryImportUploadFilters(callable $callback)
    {
        $uploadMimes = static function (array $mimes): array {
            $mimes['webp'] = 'image/webp';
            $mimes['svg'] = 'image/svg+xml';

            return $mimes;
        };

        $mimeFix = static function (array $data, string $file, string $filename, $_mimes): array {
            unset($_mimes);

            return self::coerceImageFiletypeForSideload($data, $file, $filename);
        };

        add_filter('upload_mimes', $uploadMimes);
        add_filter('wp_check_filetype_and_ext', $mimeFix, 99, 4);

        try {
            return $callback();
        } finally {
            remove_filter('upload_mimes', $uploadMimes);
            remove_filter('wp_check_filetype_and_ext', $mimeFix, 99);
        }
    }

    private static function assignTerms(int $postId, string $typeSlug): void
    {
        $type = get_term_by('slug', $typeSlug, 'culvers_eat_drink_type');
        if ($type instanceof \WP_Term) {
            wp_set_object_terms($postId, [(int) $type->term_id], 'culvers_eat_drink_type', false);
            wp_set_object_terms($postId, [], 'culvers_eat_drink_category', false);
        } else {
            self::cliWarning(sprintf('Missing Eat & Drink filter slug "%s".', $typeSlug));
        }
    }

    public static function sideloadFromUrlPublic(string $url, string $basenameHint): int
    {
        self::loadDependencies();

        return self::withDirectoryImportUploadFilters(
            static fn (): int => self::sideloadFromUrl($url, $basenameHint)
        );
    }

    private static function sideloadFromUrl(string $url, string $basenameHint): int
    {
        $tmp = download_url($url, 90);
        if (is_wp_error($tmp)) {
            self::cliWarning('[download] ' . $tmp->get_error_message() . ' (' . $url . ')');

            return 0;
        }

        return self::sideloadTmpFile($tmp, $basenameHint, $url);
    }

    private static function sideloadFromThemeFile(string $filename, string $basenameHint): int
    {
        $path = EatDrinkDirectorySeedData::themeSeedAssetPath($filename);
        if (! is_readable($path)) {
            self::cliWarning('[theme file] missing: ' . $path);

            return 0;
        }

        $tmp = wp_tempnam($basenameHint);
        if ($tmp === '') {
            return 0;
        }
        if (! copy($path, $tmp)) {
            self::cliWarning('[theme file] could not copy: ' . $path);

            return 0;
        }

        return self::sideloadTmpFile($tmp, $basenameHint, $path);
    }

    private static function sideloadTmpFile(string $tmp, string $basenameHint, string $sourceHint): int
    {
        $ext = 'jpg';
        if (function_exists('mime_content_type') && is_readable($tmp)) {
            $mime = mime_content_type($tmp);
            if (is_string($mime)) {
                if (str_contains($mime, 'png')) {
                    $ext = 'png';
                } elseif (str_contains($mime, 'webp')) {
                    $ext = 'webp';
                } elseif (str_contains($mime, 'svg')) {
                    $ext = 'svg';
                }
            }
        }

        $fileArray = [
            'name' => $basenameHint . '-' . substr(md5($sourceHint), 0, 10) . '.' . $ext,
            'tmp_name' => $tmp,
        ];

        $id = media_handle_sideload($fileArray, 0, __('Culver Square Eat & Drink seed', 'culvers'));
        if (is_wp_error($id)) {
            if ($tmp !== '' && file_exists($tmp)) {
                unlink($tmp);
            }
            self::cliWarning('[sideload] ' . $id->get_error_message());

            return 0;
        }

        return (int) $id;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function coerceImageFiletypeForSideload(array $data, string $file, string $_filename): array
    {
        $ext = isset($data['ext']) ? $data['ext'] : false;
        $type = isset($data['type']) ? $data['type'] : false;
        if (is_string($ext) && $ext !== '' && is_string($type) && $type !== '') {
            return $data;
        }

        if (! is_readable($file)) {
            return $data;
        }

        $mime = false;
        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->file($file);
            $mime = is_string($detected) ? $detected : false;
        }

        if (($mime === false || $mime === 'application/octet-stream' || $mime === 'binary/octet-stream') && function_exists('getimagesize')) {
            $info = @getimagesize($file);
            if (is_array($info)) {
                $mime = $info['mime'];
            }
        }

        if (! is_string($mime)) {
            return $data;
        }

        /** @var array<string, array{0: string, 1: string}> $pairs */
        $pairs = [
            'image/jpeg' => ['jpg', 'image/jpeg'],
            'image/png' => ['png', 'image/png'],
            'image/webp' => ['webp', 'image/webp'],
            'image/gif' => ['gif', 'image/gif'],
            'image/svg+xml' => ['svg', 'image/svg+xml'],
        ];

        if (! isset($pairs[$mime])) {
            return $data;
        }

        $data['ext'] = $pairs[$mime][0];
        $data['type'] = $pairs[$mime][1];

        return $data;
    }

    /**
     * @param int|string|false $postId
     */
    private static function acfUpdateField(string $selector, mixed $value, mixed $postId = false): bool
    {
        if (! function_exists('update_field')) {
            return false;
        }

        return (bool) update_field($selector, $value, $postId);
    }

    private static function cliSuccess(string $msg): void
    {
        if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI')) {
            \WP_CLI::success($msg);
        }
    }

    private static function cliWarning(string $msg): void
    {
        if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI')) {
            \WP_CLI::warning($msg);
        }
    }

    private static function cliError(string $msg): void
    {
        if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI')) {
            \WP_CLI::error($msg);
        }
        exit(1);
    }
}
