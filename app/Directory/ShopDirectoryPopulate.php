<?php

declare(strict_types=1);

namespace App\Directory;

use App\Helpers\Cast;
use App\Nav\ShopDirectoryNavSync;

/**
 * Sideloads Figma MCP imagery and upserts {@see DirectoryPostTypes culvers_shop} posts for local/staging demos.
 *
 * @see scripts/shops-directory-populate.php
 */
final class ShopDirectoryPopulate
{
    private static ?bool $depsLoaded = null;

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
     * Run full seed (shops + optional hero option field). Intended for WP-CLI only.
     */
    public static function runSeed(bool $replaceHero = true): void
    {
        self::loadDependencies();

        if (! function_exists('update_field')) {
            self::cliError('ACF is required (update_field missing).');
        }

        $userId = Cast::toInt(apply_filters('culvers_shop_directory_populate_user_id', 1));
        if ($userId > 0) {
            wp_set_current_user($userId);
        }

        $created = 0;
        $updated = 0;

        self::withDirectoryImportUploadFilters(static function () use (&$created, &$updated, $replaceHero): void {
            ShopTaxonomySeeder::maybeSeed();

            foreach (ShopDirectorySeedData::retailers() as $row) {
                $postId = self::upsertShopPost($row, $created, $updated);
                if ($postId <= 0) {
                    continue;
                }
                self::assignTerms($postId, $row['category_slug'], $row['type_slug']);
            }

            if ($replaceHero) {
                /* The /shops/ hero now uses the static `image_hero` band
                   (~half-viewport image banner with stacked title +
                   subtitle), seeded centrally for every directory archive
                   by {@see DirectoryArchiveHeroPopulate} and triggered by
                   `scripts/archives-hero-populate.php`. We delegate here
                   rather than duplicate the slider→banner migration logic. */
                DirectoryArchiveHeroPopulate::runSeed(true);
            }

            ShopDirectoryNavSync::syncPrimaryShopMegaLinks();
        });

        self::cliSuccess(
            sprintf(
                'Shop directory seed complete (%d created, %d updated). Navigation mega links synced.',
                $created,
                $updated
            )
        );
    }

    /**
     * @param array{title: string, logo_url: string|null, featured_url: string|null, category_slug: string, type_slug: string} $row
     */
    private static function upsertShopPost(array $row, int &$created, int &$updated): int
    {
        $title = trim($row['title']);
        if ($title === '') {
            return 0;
        }

        $slug = sanitize_title($title);
        $existing = get_posts([
            'post_type' => 'culvers_shop',
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
            'post_type' => 'culvers_shop',
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

        self::acfUpdateField('opening_hours_summary', ShopDirectorySeedData::DEFAULT_HOURS_LINE, $postId);

        $logoUrl = $row['logo_url'] !== null ? trim($row['logo_url']) : '';
        $featUrl = $row['featured_url'] !== null ? trim($row['featured_url']) : '';

        delete_post_thumbnail($postId);
        self::acfUpdateField('shop_logo', false, $postId);

        if ($logoUrl !== '') {
            $aid = self::sideloadFromUrl($logoUrl, sanitize_file_name($slug . '-logo'));
            if ($aid > 0) {
                self::acfUpdateField('shop_logo', $aid, $postId);
            }
        }

        if ($featUrl !== '') {
            $fid = self::sideloadFromUrl($featUrl, sanitize_file_name($slug . '-featured'));
            if ($fid > 0) {
                set_post_thumbnail($postId, $fid);
            }
        }

        return $postId;
    }

    /**
     * Relax upload MIME checks during directory seeding (Figma MCP CDN often uses generic types).
     *
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

            return ShopDirectoryPopulate::coerceImageFiletypeForSideload($data, $file, $filename);
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

    private static function assignTerms(int $postId, string $categorySlug, string $typeSlug): void
    {
        $cat = get_term_by('slug', $categorySlug, 'culvers_shop_category');
        if ($cat instanceof \WP_Term) {
            wp_set_object_terms($postId, [(int) $cat->term_id], 'culvers_shop_category', false);
        } else {
            self::cliWarning(sprintf('Missing shop category slug "%s" — assign terms manually.', $categorySlug));
        }

        $type = get_term_by('slug', $typeSlug, 'culvers_shop_type');
        if ($type instanceof \WP_Term) {
            wp_set_object_terms($postId, [(int) $type->term_id], 'culvers_shop_type', false);
        } else {
            self::cliWarning(sprintf('Missing retailer type slug "%s".', $typeSlug));
        }
    }

    private static function sideloadFromUrl(string $url, string $basenameHint): int
    {
        $tmp = download_url($url, 90);
        if (is_wp_error($tmp)) {
            self::cliWarning('[download] ' . $tmp->get_error_message() . ' (' . $url . ')');

            return 0;
        }

        $ext = 'jpg';
        if (function_exists('mime_content_type') && is_readable($tmp)) {
            $mime = mime_content_type($tmp);
            if (is_string($mime)) {
                if (str_contains($mime, 'png')) {
                    $ext = 'png';
                } elseif (str_contains($mime, 'webp')) {
                    $ext = 'webp';
                }
            }
        }

        $fileArray = [
            'name' => $basenameHint . '-' . substr(md5($url), 0, 10) . '.' . $ext,
            'tmp_name' => $tmp,
        ];

        $id = media_handle_sideload($fileArray, 0, __('Culver Square directory seed', 'culvers'));
        if (is_wp_error($id)) {
            if ($tmp !== '' && file_exists($tmp)) {
                unlink($tmp);
            }
            self::cliWarning('[sideload] ' . $id->get_error_message());

            return 0;
        }

        return (int) $id;
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
