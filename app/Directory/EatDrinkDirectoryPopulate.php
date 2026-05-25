<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Media sideload helpers for Eat & Drink live sync (logos from retailer pages).
 */
final class EatDrinkDirectoryPopulate
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

    public static function sideloadFromUrlPublic(string $url, string $basenameHint): int
    {
        self::loadDependencies();

        return self::withDirectoryImportUploadFilters(
            static fn (): int => self::sideloadFromUrl($url, $basenameHint)
        );
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

    private static function sideloadFromUrl(string $url, string $basenameHint): int
    {
        $tmp = download_url($url, 90);
        if (is_wp_error($tmp)) {
            self::cliWarning('[download] ' . $tmp->get_error_message() . ' (' . $url . ')');

            return 0;
        }

        return self::sideloadTmpFile($tmp, $basenameHint, $url);
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

        $id = media_handle_sideload($fileArray, 0, __('Culver Square Eat & Drink import', 'culvers'));
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

    private static function cliWarning(string $msg): void
    {
        if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI')) {
            \WP_CLI::warning($msg);
        }
    }
}
