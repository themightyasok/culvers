<?php

declare(strict_types=1);

namespace App\Directory;

use App\Helpers\PagesFlexibleSeedData;

/**
 * Sideload Figma hero imagery into the Media Library and seed each
 * directory archive's hero option fields ({@see ShopArchiveFields},
 * {@see EatDrinkArchiveFields}, {@see EventArchiveFields},
 * {@see CareerArchiveFields}).
 *
 * The four archive heroes were exported from the Figma developer release
 * once via the MCP plugin (URLs are short-lived) and committed to
 * `resources/images/seeds/` so this populate can run reliably offline:
 *
 *   • shops      — frame 51:5152, hero rectangle 51:5470
 *   • eat-drink  — frame 51:5462, hero rectangle 51:5470 / 51:5469
 *   • whats-on   — frame 51:6386, hero rectangle 51:6044
 *   • careers    — frame 51:5622, hero rectangle 51:5629
 *
 * Each archive uses the static `image_hero` band (Figma 51:9360, 1440×646)
 * — title and subtitle stack vertically, image is ~half-viewport tall on
 * desktop. The full-viewport `hero_slider` is reserved for the homepage.
 *
 * Run via WP-CLI through the Local wrapper:
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh \
 *     wp eval-file wp-content/themes/culvers/scripts/archives-hero-populate.php
 *
 * @see scripts/archives-hero-populate.php
 */
final class DirectoryArchiveHeroPopulate
{
    /**
     * Per-archive seed payload (image filename, copy, ACF field prefix).
     *
     * @return list<array{
     *     label: string,
     *     image_file: string,
     *     attachment_basename: string,
     *     title: string,
     *     subtitle: string,
     *     overlay: int,
     *     tone: string,
     *     prefix: string,
     *     intro_copy: string,
     *     legacy_slider_field?: string
     * }>
     */
    private static function archives(): array
    {
        return [
            [
                'label' => 'shops',
                'image_file' => 'archive-shops-hero.jpg',
                'attachment_basename' => 'shops-archive-hero',
                'title' => __('Good vibes, Great finds.', 'culvers'),
                'subtitle' => __('Find your newest look', 'culvers'),
                'overlay' => 35,
                'tone' => 'glowleaf',
                'prefix' => ShopArchiveFields::FIELD_PREFIX,
                'intro_copy' => __(
                    "At Culver Square, enjoy a variety of shops, dining, and entertainment all in one place."
                        . " From fashion and unique gifts to tasty meals, there's something for everyone. Join us and explore!",
                    'culvers'
                ),
                /* Pre-image-hero migration left a slider repeater here — null it out so editors aren't confused. */
                'legacy_slider_field' => 'shops_archive_hero_slides',
            ],
            [
                'label' => 'eat-drink',
                'image_file' => 'archive-eat-drink-hero.jpg',
                'attachment_basename' => 'eat-drink-archive-hero',
                'title' => __('Good brews, Great bites.', 'culvers'),
                'subtitle' => __('From quick bites to long lunches', 'culvers'),
                'overlay' => 35,
                'tone' => 'glowleaf',
                'prefix' => EatDrinkArchiveFields::FIELD_PREFIX,
                'intro_copy' => __(
                    'From quick coffee stops to long, lazy lunches — find your next favourite spot in the centre.',
                    'culvers'
                ),
                'legacy_slider_field' => 'eat_drink_archive_hero_slides',
            ],
            [
                'label' => 'latest-events',
                'image_file' => 'archive-whats-on-hero.jpg',
                'attachment_basename' => 'latest-events-archive-hero',
                'title' => __('Latest Events.', 'culvers'),
                'subtitle' => __('Workshops, performances and family days', 'culvers'),
                'overlay' => 35,
                'tone' => 'glowleaf',
                'prefix' => EventArchiveFields::FIELD_PREFIX,
                'intro_copy' => __(
                    'Workshops, performances, family days and seasonal moments — see what’s coming up at Culver Square.',
                    'culvers'
                ),
                'legacy_slider_field' => 'events_archive_hero_slides',
            ],
            [
                'label' => 'latest-offers',
                'image_file' => 'archive-latest-offers-hero.jpg',
                'attachment_basename' => 'latest-offers-archive-hero',
                'title' => __('Latest Offers.', 'culvers'),
                'subtitle' => __('Opportunities around every corner', 'culvers'),
                'overlay' => 35,
                'tone' => 'glowleaf',
                'prefix' => OfferArchiveFields::FIELD_PREFIX,
                'intro_copy' => __(
                    'Promotions, discounts and brand campaigns from across the centre — pick something for your next visit.',
                    'culvers'
                ),
            ],
            [
                'label' => 'latest-news',
                'image_file' => 'archive-latest-news-hero.jpg',
                'attachment_basename' => 'latest-news-archive-hero',
                'title' => __('Latest News.', 'culvers'),
                'subtitle' => __('Centre updates and editorial', 'culvers'),
                'overlay' => 35,
                'tone' => 'glowleaf',
                'prefix' => NewsArchiveFields::FIELD_PREFIX,
                'intro_copy' => __(
                    'Centre updates, retailer announcements and editorial from the Culver Square team.',
                    'culvers'
                ),
            ],
            [
                'label' => 'careers',
                'image_file' => 'archive-careers-hero.jpg',
                'attachment_basename' => 'careers-archive-hero',
                'title' => __('Powered by Great People.', 'culvers'),
                'subtitle' => __('Opportunities around every corner', 'culvers'),
                'overlay' => 35,
                'tone' => 'glowleaf',
                'prefix' => CareerArchiveFields::FIELD_PREFIX,
                'intro_copy' => __(
                    'Roles in the centre team and across our retail, hospitality and operations partners. Browse open positions below.',
                    'culvers'
                ),
                'legacy_slider_field' => 'careers_archive_hero_slides',
            ],
        ];
    }

    /**
     * Run the seed for every archive. Safe to re-run — old hero attachments
     * are removed before the new sideload (`replaceExisting` true) so the
     * media library doesn't accumulate stray files between runs.
     */
    public static function runSeed(bool $replaceExisting = true): void
    {
        if (! function_exists('update_field')) {
            self::cliError('ACF is required (update_field missing).');
        }

        ShopDirectoryPopulate::loadDependencies();

        foreach (self::archives() as $archive) {
            self::seedArchive($archive, $replaceExisting);
        }

        self::cliSuccess('Directory archive hero seed complete (shops, eat-drink, whats-on, careers).');
    }

    /**
     * @param array{
     *     label: string,
     *     image_file: string,
     *     attachment_basename: string,
     *     title: string,
     *     subtitle: string,
     *     overlay: int,
     *     tone: string,
     *     prefix: string,
     *     intro_copy: string,
     *     legacy_slider_field?: string
     * } $archive
     */
    private static function seedArchive(array $archive, bool $replaceExisting): void
    {
        $prefix = $archive['prefix'];
        $heroImageField = $prefix . '_hero_image';

        if ($replaceExisting) {
            self::deleteAttachmentAt($heroImageField);
            self::deleteAttachmentAt($prefix . '_hero_image_mobile');
            if (isset($archive['legacy_slider_field'])) {
                self::deleteLegacySliderAttachments($archive['legacy_slider_field']);
                self::acfUpdate($archive['legacy_slider_field'], false);
            }
        }

        $url = PagesFlexibleSeedData::seedAssetUrl($archive['image_file']);
        $attachmentId = self::sideloadFromUrl($url, $archive['attachment_basename']);
        if ($attachmentId <= 0) {
            self::cliWarning(sprintf(
                'Hero sideload failed for %s — option fields not updated.',
                $archive['label']
            ));

            return;
        }

        self::acfUpdate($heroImageField, $attachmentId);
        self::acfUpdate($prefix . '_hero_title_line', $archive['title']);
        self::acfUpdate($prefix . '_hero_subtitle_line', $archive['subtitle']);
        self::acfUpdate($prefix . '_hero_title_tone', $archive['tone']);
        self::acfUpdate($prefix . '_hero_overlay_opacity', $archive['overlay']);

        if (self::shouldUpdateIntroCopy($prefix . '_intro_copy')) {
            self::acfUpdate($prefix . '_intro_copy', $archive['intro_copy']);
        }

        self::cliSuccess(sprintf('%s hero seeded (attachment #%d).', $archive['label'], $attachmentId));
    }

    /**
     * Don't trample editor-set intro copy. Only set the default when the
     * field is empty; editors retain control after their first save.
     */
    private static function shouldUpdateIntroCopy(string $field): bool
    {
        $current = self::acfGet($field);
        if (! is_string($current)) {
            return true;
        }

        return trim($current) === '';
    }

    /**
     * Delete the attachment currently set on a single image option field.
     */
    private static function deleteAttachmentAt(string $field): void
    {
        $value = self::acfGet($field);
        $id = 0;
        if (is_array($value) && isset($value['ID'])) {
            $id = (int) $value['ID'];
        } elseif (is_numeric($value)) {
            $id = (int) $value;
        }
        if ($id > 0) {
            wp_delete_attachment($id, true);
        }
        self::acfUpdate($field, false);
    }

    /**
     * Wipe attachments left behind by the previous slider-based seed so the
     * media library doesn't accumulate stray hero files when migrating.
     */
    private static function deleteLegacySliderAttachments(string $heroField): void
    {
        $existing = self::acfGet($heroField);
        if (! is_array($existing)) {
            return;
        }

        foreach ($existing as $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach (['slide_image', 'slide_image_mobile'] as $key) {
                $val = $row[$key] ?? null;
                $id = 0;
                if (is_array($val)) {
                    $id = isset($val['ID']) ? (int) $val['ID'] : 0;
                } elseif (is_numeric($val)) {
                    $id = (int) $val;
                }
                if ($id > 0) {
                    wp_delete_attachment($id, true);
                }
            }
        }
    }

    /**
     * Thin wrapper around `update_field` so PHPStan doesn't complain when ACF
     * isn't loaded into the analyser's symbol table. The {@see runSeed} guard
     * already short-circuits when the function is missing.
     *
     * @param mixed $value
     */
    private static function acfUpdate(string $field, $value): bool
    {
        if (! function_exists('update_field')) {
            return false;
        }

        return (bool) update_field($field, $value, 'option');
    }

    /**
     * Thin wrapper around `get_field` (see {@see acfUpdate} for rationale).
     *
     * @return mixed
     */
    private static function acfGet(string $field)
    {
        if (! function_exists('get_field')) {
            return null;
        }

        return get_field($field, 'option');
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

        $id = media_handle_sideload($fileArray, 0, __('Culver Square archive hero seed', 'culvers'));
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
