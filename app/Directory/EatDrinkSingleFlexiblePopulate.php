<?php

declare(strict_types=1);

namespace App\Directory;

use App\Helpers\CptSinglesFlexibleSeedData;
use App\Helpers\HomepageFlexibleAcfAttach;
use App\Helpers\PagesFlexibleSeedData;

/**
 * Full flexible `components` stack for every `culvers_eat_drink` single (Greggs-shaped).
 */
final class EatDrinkSingleFlexiblePopulate
{
    /** @var list<string> */
    private const LEGACY_TAIL_LAYOUTS = ['section_header', 'three_card_block'];

    public static function registerAcfLoadSanitizer(): void
    {
        add_filter('acf/load_value/name=components', [self::class, 'filterLoadComponents'], 25, 3);
    }

    /**
     * Drop removed tail layouts so stale DB rows cannot render duplicate headings.
     *
     * @param  mixed  $value
     * @param  string|int|false  $postId
     * @param  array<string, mixed>  $field
     * @return mixed
     */
    public static function filterLoadComponents($value, $postId, array $field)
    {
        unset($field);

        if (! is_numeric($postId)) {
            return $value;
        }

        $pid = (int) $postId;
        if (get_post_type($pid) !== 'culvers_eat_drink' || ! is_array($value)) {
            return $value;
        }

        return self::normalizeComponentsForDisplay($value);
    }

    /**
     * @param  list<array<string, mixed>>  $components
     * @return list<array<string, mixed>>
     */
    public static function normalizeComponentsForDisplay(array $components): array
    {
        $archive = function_exists('get_post_type_archive_link')
            ? (string) get_post_type_archive_link('culvers_eat_drink')
            : '/eat-drink/';

        $normalized = [];
        $hasRelated = false;

        foreach ($components as $row) {
            $layout = (string) ($row['acf_fc_layout'] ?? '');
            if (in_array($layout, self::LEGACY_TAIL_LAYOUTS, true)) {
                continue;
            }

            if ($layout === 'shop_related_eat_drink') {
                if ($hasRelated) {
                    continue;
                }
                $hasRelated = true;
                $row['eat_drink_related_heading'] = __('More flavours to discover', 'culvers');
                $row['eat_drink_related_view_all_url'] = $archive;
                $row['eat_drink_related_view_all_label'] = __('View all', 'culvers');
            }

            $normalized[] = $row;
        }

        return $normalized;
    }

    /**
     * @return array{updated: int, failed: int}
     */
    public static function runAll(bool $dryRun = false, ?string $onlyLocalSlug = null): array
    {
        if (! function_exists('update_field')) {
            throw new \RuntimeException('ACF is required.');
        }

        EatDrinkDirectoryPopulate::loadDependencies();

        $updated = 0;
        $failed = 0;

        $posts = get_posts([
            'post_type' => 'culvers_eat_drink',
            'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
            'posts_per_page' => -1,
            'suppress_filters' => true,
        ]);

        foreach ($posts as $post) {
            $postId = (int) $post->ID;
            $slug = (string) $post->post_name;

            if ($onlyLocalSlug !== null && $onlyLocalSlug !== $slug) {
                continue;
            }

            try {
                $rows = self::buildRowsForPost($postId);
            } catch (\Throwable $e) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::warning(sprintf('%s #%d: %s', $slug, $postId, $e->getMessage()));
                }
                ++$failed;
                continue;
            }

            $attached = HomepageFlexibleAcfAttach::attachFlexibleRows($rows);

            if (function_exists('WP_CLI')) {
                \WP_CLI::log(sprintf(
                    '%s%s %s (#%d) — %d layouts',
                    $dryRun ? '[dry-run] ' : '',
                    $dryRun ? 'would write' : 'ok',
                    $slug,
                    $postId,
                    count($attached)
                ));
            }

            if (! $dryRun) {
                delete_field('components', $postId);
                update_field('components', $attached, $postId);
                EatDrinkLiveSync::syncHoursSummaryFromComponents($postId, $attached);
            }

            ++$updated;
        }

        return ['updated' => $updated, 'failed' => $failed];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function buildRowsForPost(int $postId): array
    {
        $slug = (string) get_post_field('post_name', $postId);
        $title = (string) get_the_title($postId);
        $liveSlug = self::liveSlugForLocal($slug);

        $page = $liveSlug !== null ? VenueLiveRetailerPage::fetch($liveSlug) : null;
        $catalog = EatDrinkLiveSync::storeDetailsCatalog();
        $spec = $catalog[$slug] ?? null;

        $rows = CptSinglesFlexibleSeedData::greggs();
        $rows = self::applyVenueIdentity($rows, $postId, $title, $page);
        $effectiveSlug = $liveSlug ?? $slug;
        $rows = $page !== null
            ? EatDrinkLiveSync::applyLivePageToComponents($rows, $page, $slug, $effectiveSlug, $title)
            : $rows;
        $rows = self::applyStoreDetails($rows, $spec, $page);
        $rows = self::replaceSharedRows($rows, $slug, $page);

        return $rows;
    }

    public static function liveSlugForLocal(string $localSlug): ?string
    {
        $flipped = array_flip(EatDrinkLiveSync::liveToLocalMap());

        return $flipped[$localSlug] ?? null;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>|null  $page
     * @return list<array<string, mixed>>
     */
    private static function applyVenueIdentity(
        array $rows,
        int $postId,
        string $title,
        ?array $page
    ): array {
        $heroUrl = self::heroImageUrl($postId, $page);
        $logoId = 0;

        if ($page !== null && $page['logo_url'] !== '') {
            $logoId = VenueLiveRetailerPage::sideloadLogo(
                $page['logo_url'],
                (string) get_post_field('post_name', $postId)
            );
            if ($logoId > 0) {
                update_field('eat_drink_logo', $logoId, $postId);
            }
        }

        foreach ($rows as $i => $row) {
            if (($row['acf_fc_layout'] ?? '') !== 'image_hero') {
                continue;
            }

            if ($heroUrl !== '') {
                $row['hero_image'] = ['url' => $heroUrl, 'alt' => $title];
            }

            if ($logoId > 0) {
                $row['hero_logo'] = $logoId;
                $row['hero_title_line'] = '';
                $row['hero_subtitle_line'] = '';
            } else {
                $row['hero_title_line'] = $title;
                $row['hero_subtitle_line'] = '';
            }

            $row['hero_title_in_image'] = false;
            $rows[$i] = $row;
            break;
        }

        return $rows;
    }

    /** @param array<string, mixed>|null $page */
    private static function heroImageUrl(int $postId, ?array $page): string
    {
        unset($page);

        $thumbId = (int) get_post_thumbnail_id($postId);
        if ($thumbId > 0) {
            $url = wp_get_attachment_image_url($thumbId, 'full');

            return is_string($url) ? $url : '';
        }

        return PagesFlexibleSeedData::seedAssetUrl('greggs-hero-frame.jpg');
    }

    /**
     * @param  array<string, mixed>|null  $spec
     * @param  array<string, mixed>|null  $page
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private static function applyStoreDetails(array $rows, ?array $spec, ?array $page): array
    {
        if ($spec === null) {
            return $rows;
        }

        $phone = $page['phone'] ?? '';
        if (is_string($phone) && $phone !== '') {
            $spec['phone'] = $phone;
        }

        foreach ($rows as $i => $row) {
            if (($row['acf_fc_layout'] ?? '') !== 'shop_store_details') {
                continue;
            }

            $row['details_heading'] = __('Store Details', 'culvers');
            $row['details_contact_phone'] = $spec['phone'];
            $row['details_address'] = $spec['address'];
            $row['details_instagram_url'] = $spec['instagram_url'];
            $row['details_instagram_handle'] = $spec['instagram_handle'];
            $row['details_show_social_column'] = $spec['show_social'] ? 1 : 0;
            $rows[$i] = $row;
            break;
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    /**
     * @param  array<string, mixed>|null  $page
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private static function replaceSharedRows(array $rows, string $currentSlug, ?array $page = null): array
    {
        unset($currentSlug);

        $replaced = [];

        foreach ($rows as $row) {
            $layout = (string) ($row['acf_fc_layout'] ?? '');

            if ($layout === 'opening_hours') {
                $hoursRow = PagesFlexibleSeedData::openingHoursRow();
                if ($page !== null && ($page['opening_hours_rows'] ?? []) !== []) {
                    $hoursRow = VenueOpeningHours::mergeIntoOpeningHoursRow(
                        $hoursRow,
                        $page['opening_hours_rows']
                    );
                }
                $replaced[] = $hoursRow;
                continue;
            }

            if ($layout === 'centre_map') {
                $mapRow = PagesFlexibleSeedData::centreMapRow();
                $mapRow['centre_map_heading'] = __('Find your way around', 'culvers');
                $mapAttachmentId = self::centreMapAttachmentIdFromPlanMyVisit();
                if ($mapAttachmentId > 0) {
                    $mapRow['centre_map_image'] = $mapAttachmentId;
                }
                $replaced[] = $mapRow;
                continue;
            }

            if ($layout === 'section_header' || $layout === 'three_card_block') {
                continue;
            }

            if ($layout === 'shop_related_eat_drink') {
                $archive = function_exists('get_post_type_archive_link')
                    ? (string) get_post_type_archive_link('culvers_eat_drink')
                    : '/eat-drink/';
                $row['eat_drink_related_heading'] = __('More flavours to discover', 'culvers');
                $row['eat_drink_related_view_all_url'] = $archive;
                $row['eat_drink_related_view_all_label'] = __('View all', 'culvers');
                $row['eat_drink_related_posts'] = [];
                $replaced[] = $row;
                continue;
            }

            $replaced[] = $row;
        }

        return $replaced;
    }

    private static function centreMapAttachmentIdFromPlanMyVisit(): int
    {
        $page = get_page_by_path('plan-my-visit', OBJECT, 'page');
        if (! $page instanceof \WP_Post) {
            return 0;
        }

        $components = get_field('components', $page->ID);
        if (! is_array($components)) {
            return 0;
        }

        foreach ($components as $row) {
            if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'centre_map') {
                continue;
            }

            $image = $row['centre_map_image'] ?? null;
            if (is_array($image)) {
                $id = (int) ($image['ID'] ?? $image['id'] ?? 0);

                return $id > 0 ? $id : 0;
            }

            if (is_numeric($image) && (int) $image > 0) {
                return (int) $image;
            }
        }

        return 0;
    }
}
