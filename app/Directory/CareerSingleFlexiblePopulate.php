<?php

declare(strict_types=1);

namespace App\Directory;

use App\Helpers\CptSinglesFlexibleSeedData;
use App\Helpers\HomepageFlexibleAcfAttach;

/**
 * Applies the Senior Supervisor flexible stack (Figma 51:6450) to every
 * `culvers_career` single so editors have a full template to customise.
 */
final class CareerSingleFlexiblePopulate
{
    private const DEFAULT_LOCATION = 'Culver Square, Colchester';

    private const DEFAULT_APPLY_URL = 'https://culversquare.co.uk/careers/';

    private const SUBWAY_APPLY_URL = 'https://www.subway.com/en-gb/careers';

    /**
     * @return array{updated: int, failed: int}
     */
    public static function runAll(bool $dryRun = false, ?string $onlySlug = null): array
    {
        if (! function_exists('update_field')) {
            throw new \RuntimeException('ACF is required.');
        }

        $updated = 0;
        $failed = 0;

        $posts = get_posts([
            'post_type' => 'culvers_career',
            'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
            'posts_per_page' => -1,
            'suppress_filters' => true,
        ]);

        foreach ($posts as $post) {
            $postId = (int) $post->ID;
            $slug = (string) $post->post_name;

            if ($onlySlug !== null && $onlySlug !== $slug) {
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

            self::syncListingMeta($postId);

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
        $postTitle = (string) get_the_title($postId);
        $parsed = self::parsePostTitle($postTitle);

        $employer = trim((string) (get_field('career_employer', $postId) ?: ''));
        if ($employer === '') {
            $employer = $parsed['employer'];
        }

        $jobTitle = $parsed['job'] !== '' ? $parsed['job'] : $postTitle;
        $employment = trim((string) (get_field('career_employment_type', $postId) ?: ''));
        if ($employment === '') {
            $employment = __('Full time', 'culvers');
        }

        $location = trim((string) (get_field('career_location', $postId) ?: ''));
        if ($location === '') {
            $location = __(self::DEFAULT_LOCATION, 'culvers');
        }

        $applyUrl = self::resolveApplyUrl((string) get_post_field('post_name', $postId), $employer);

        $rows = CptSinglesFlexibleSeedData::seniorSupervisor();

        return self::applyPostIdentity(
            $rows,
            $postId,
            $jobTitle,
            $employer,
            $employment,
            $location,
            $applyUrl
        );
    }

    /**
     * @return array{job: string, employer: string}
     */
    public static function parsePostTitle(string $title): array
    {
        $title = html_entity_decode(trim($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match('/^(.+?)\s*[—–-]\s*(.+)$/u', $title, $matches) === 1) {
            return [
                'job' => trim($matches[1]),
                'employer' => trim($matches[2]),
            ];
        }

        return [
            'job' => $title,
            'employer' => '',
        ];
    }

    public static function resolveApplyUrl(string $slug, string $employer): string
    {
        if ($slug === 'senior-supervisor' || strcasecmp($employer, 'Subway') === 0) {
            return self::SUBWAY_APPLY_URL;
        }

        return self::DEFAULT_APPLY_URL;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private static function applyPostIdentity(
        array $rows,
        int $postId,
        string $jobTitle,
        string $employer,
        string $employment,
        string $location,
        string $applyUrl
    ): array {
        $heroLine = $employer !== '' ? $employer : $jobTitle;
        $logoId = (int) get_field('career_employer_logo', $postId);

        foreach ($rows as $i => $row) {
            $layout = (string) ($row['acf_fc_layout'] ?? '');

            if ($layout === 'image_hero') {
                $row['hero_title_line'] = $heroLine;
                $row['hero_subtitle_line'] = __('Now hiring at Culver Square', 'culvers');
                $row['hero_logo'] = $logoId > 0 ? $logoId : ($row['hero_logo'] ?? null);
                $rows[$i] = $row;
            }

            if ($layout === 'career_detail') {
                $row['career_job_title'] = $jobTitle;
                $row['career_sidebar_brand_logo'] = null;
                $row['career_meta'] = [
                    [
                        'item_label' => __('Contract Type', 'culvers'),
                        'item_value' => $employment,
                    ],
                    [
                        'item_label' => __('Location', 'culvers'),
                        'item_value' => $location,
                    ],
                    [
                        'item_label' => __('Pay', 'culvers'),
                        'item_value' => __('£12.40 per hour', 'culvers'),
                    ],
                ];
                $row['career_apply_label'] = __('Apply Now', 'culvers');
                $row['career_apply_url'] = $applyUrl;
                $rows[$i] = $row;
            }

            if ($layout === 'info_block') {
                $row['info_cta_url'] = $applyUrl;
                $rows[$i] = $row;
            }
        }

        return $rows;
    }

    private static function syncListingMeta(int $postId): void
    {
        $postTitle = (string) get_the_title($postId);
        $parsed = self::parsePostTitle($postTitle);

        $employer = trim((string) (get_field('career_employer', $postId) ?: ''));
        if ($employer === '' && $parsed['employer'] !== '') {
            update_field('career_employer', $parsed['employer'], $postId);
        }

        $location = trim((string) (get_field('career_location', $postId) ?: ''));
        if ($location === '') {
            update_field('career_location', __(self::DEFAULT_LOCATION, 'culvers'), $postId);
        }

        $employment = trim((string) (get_field('career_employment_type', $postId) ?: ''));
        if ($employment === '') {
            update_field('career_employment_type', __('Full time', 'culvers'), $postId);
        }
    }
}
