<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Syncs eat & drink venue singles from culversquare.co.uk `/retailers/{slug}/` pages:
 * intro/split copy, hero logo, store contact, and brand Instagram (not centre handles).
 */
final class EatDrinkLiveSync
{
    private const DINING_URL = 'https://www.culversquare.co.uk/dining/';

    /** @var array<string, string> Live retailer slug → local culvers_eat_drink post_name */
    private const LIVE_TO_LOCAL = [
        'chopstix' => 'chopstix',
        'greggs' => 'greggs',
        'subway' => 'subway',
        'toast-coffee-house' => 'toast-coffee',
        'godfreys-creperie' => 'godfreys-creperie',
        'juicy-bar-vitality' => 'juicy-bar-vitality',
    ];

    /**
     * @return array<string, array{
     *     phone: string,
     *     address: string,
     *     instagram_url: string,
     *     instagram_handle: string,
     *     show_social: bool
     * }>
     */
    public static function storeDetailsCatalog(): array
    {
        $unit = "10B Culver St W,\nColchester CO1 1WF";

        return [
            'greggs' => [
                'phone' => '01206 563079',
                'address' => $unit,
                'instagram_url' => 'https://www.instagram.com/greggs_official/',
                'instagram_handle' => '@greggs_official',
                'show_social' => true,
            ],
            'subway' => [
                'phone' => '01206 364466',
                'address' => $unit,
                'instagram_url' => 'https://www.instagram.com/subwayuk/',
                'instagram_handle' => '@subwayuk',
                'show_social' => true,
            ],
            'toast-coffee' => [
                'phone' => '01206 560420',
                'address' => $unit,
                'instagram_url' => 'https://www.instagram.com/lovetoast/',
                'instagram_handle' => '@lovetoast',
                'show_social' => true,
            ],
            'godfreys-creperie' => [
                'phone' => '01206 578830',
                'address' => $unit,
                'instagram_url' => '',
                'instagram_handle' => '',
                'show_social' => false,
            ],
            'juicy-bar-vitality' => [
                'phone' => '07867 788623',
                'address' => $unit,
                'instagram_url' => 'https://www.instagram.com/juicybarvitality/',
                'instagram_handle' => '@juicybarvitality',
                'show_social' => true,
            ],
            'chopstix' => [
                'phone' => '',
                'address' => $unit,
                'instagram_url' => '',
                'instagram_handle' => '',
                'show_social' => false,
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $components
     * @param  array{
     *     title: string,
     *     phone: string,
     *     website: string,
     *     logo_url: string,
     *     paras: list<string>,
     *     lists: list<list<string>>,
     *     opening_hours_rows: list<array{day_label: string, time_range: string, weekday_highlight: string}>
     * }  $page
     * @return list<array<string, mixed>>
     */
    public static function applyLivePageToComponents(
        array $components,
        array $page,
        string $localSlug,
        string $liveSlug,
        string $title
    ): array {
        $catalog = self::storeDetailsCatalog();
        $displayTitle = $title !== '' ? $title : $page['title'];

        foreach ($components as $i => $row) {
            $layout = (string) ($row['acf_fc_layout'] ?? '');

            if ($layout === 'shop_intro_block' && $page['paras'] !== []) {
                $split = ShopLiveIntroCopy::splitForBlocks($displayTitle, $page['paras'], $page['lists']);
                if ($split['intro_html'] !== '') {
                    $row['intro_body'] = $split['intro_html'];
                    $cta = ShopIntroCta::resolve($localSlug, $liveSlug, $displayTitle);
                    if ($cta !== null) {
                        $row['intro_cta_url'] = $cta['url'];
                        $row['intro_cta_label'] = $cta['label'];
                    }
                    $components[$i] = $row;
                }
            }

            if ($layout === 'shop_split_highlight' && $page['paras'] !== []) {
                $split = ShopLiveIntroCopy::splitForBlocks($displayTitle, $page['paras'], $page['lists']);
                $row['split_use_tabs'] = 0;
                $row['split_kicker'] = $split['split_kicker'];
                $row['split_headline'] = $split['split_headline'] !== ''
                    ? $split['split_headline']
                    : sprintf(__('Discover %s', 'culvers'), $displayTitle);
                $row['split_body'] = $split['split_body_html'] !== ''
                    ? $split['split_body_html']
                    : self::fallbackSplitBody($page['paras'], $displayTitle);
                $row['split_cta_label'] = '';
                $row['split_cta_url'] = '';
                $components[$i] = $row;
            }

            if ($layout === 'shop_store_details') {
                $spec = $catalog[$localSlug] ?? [
                    'phone' => '',
                    'address' => "10B Culver St W,\nColchester CO1 1WF",
                    'instagram_url' => '',
                    'instagram_handle' => '',
                    'show_social' => false,
                ];
                if ($page['phone'] !== '') {
                    $spec['phone'] = $page['phone'];
                }
                $row['details_heading'] = $row['details_heading'] ?? __('Store Details', 'culvers');
                $row['details_contact_phone'] = $spec['phone'];
                $row['details_address'] = $spec['address'];
                $row['details_instagram_url'] = $spec['instagram_url'];
                $row['details_instagram_handle'] = $spec['instagram_handle'];
                $row['details_show_social_column'] = $spec['show_social'] ? 1 : 0;
                $components[$i] = $row;
            }

            if ($layout === 'opening_hours' && $page['opening_hours_rows'] !== []) {
                $components[$i] = VenueOpeningHours::mergeIntoOpeningHoursRow($row, $page['opening_hours_rows']);
            }
        }

        return $components;
    }

    /**
     * @param  list<array<string, mixed>>  $components
     */
    public static function syncHoursSummaryFromComponents(int $postId, array $components): void
    {
        foreach ($components as $row) {
            if (($row['acf_fc_layout'] ?? '') !== 'opening_hours') {
                continue;
            }

            $hoursRows = $row['hours_rows'] ?? [];
            if (! is_array($hoursRows) || $hoursRows === []) {
                return;
            }

            $line = OpeningHoursCardLine::lineFromHoursRows($hoursRows);
            if ($line !== null) {
                update_field('eat_drink_hours_summary', $line, $postId);
            }

            return;
        }
    }

    /**
     * @param  list<string>  $paras
     */
    public static function fallbackSplitBody(array $paras, string $title): string
    {
        if ($paras === []) {
            return '';
        }

        $last = $paras[count($paras) - 1] ?? '';

        return $last !== ''
            ? '<p>' . esc_html($last) . '</p>'
            : '<p>' . esc_html(sprintf(__('Visit %s at Culver Square.', 'culvers'), $title)) . '</p>';
    }

    /**
     * @return array<string, string>
     */
    public static function liveToLocalMap(): array
    {
        return self::LIVE_TO_LOCAL;
    }

    /**
     * @return list<string>
     */
    public static function discoverLiveSlugs(): array
    {
        $response = wp_remote_get(self::DINING_URL, [
            'timeout' => 25,
            'user-agent' => 'CulversTheme/1.0 (eat-drink-live-sync)',
        ]);

        if (is_wp_error($response)) {
            return array_keys(self::LIVE_TO_LOCAL);
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $html = $code >= 200 && $code < 300 ? (string) wp_remote_retrieve_body($response) : '';

        if ($html === '' || ! preg_match_all('#href="/retailers/([a-z0-9-]+)/?"#i', $html, $matches)) {
            return array_keys(self::LIVE_TO_LOCAL);
        }

        /** @var list<string> $slugs */
        $slugs = array_values(array_unique(array_map('strval', $matches[1])));

        return $slugs;
    }

    /**
     * @return array{updated: int, skipped: int, failed: int}
     */
    public static function run(bool $dryRun = false, ?string $onlyLocalSlug = null): array
    {
        if (! function_exists('get_field') || ! function_exists('update_field')) {
            throw new \RuntimeException('ACF is required.');
        }

        EatDrinkDirectoryPopulate::loadDependencies();

        $map = self::liveToLocalMap();
        $catalog = self::storeDetailsCatalog();
        $liveSlugs = self::discoverLiveSlugs();

        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($liveSlugs as $liveSlug) {
            $localSlug = $map[$liveSlug] ?? null;
            if ($localSlug === null) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::log(sprintf('skip live "%s" — no local culvers_eat_drink mapping', $liveSlug));
                }
                ++$skipped;
                continue;
            }

            if ($onlyLocalSlug !== null && $onlyLocalSlug !== $localSlug) {
                continue;
            }

            $posts = get_posts([
                'post_type' => 'culvers_eat_drink',
                'name' => $localSlug,
                'post_status' => 'any',
                'posts_per_page' => 1,
            ]);

            if ($posts === []) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::warning(sprintf('no local post for %s (live %s)', $localSlug, $liveSlug));
                }
                ++$failed;
                continue;
            }

            $post = $posts[0];
            $postId = (int) $post->ID;
            $title = get_the_title($post) ?: '';

            $page = VenueLiveRetailerPage::fetch($liveSlug);
            if ($page === null) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::warning(sprintf('fetch failed for %s / %s', $localSlug, $liveSlug));
                }
                ++$failed;
                continue;
            }

            $components = get_field('components', $postId);
            if (! is_array($components) || $components === []) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::warning(sprintf('%s has no flexible components', $localSlug));
                }
                ++$failed;
                continue;
            }

            $hasIntro = false;
            foreach ($components as $row) {
                if (is_array($row) && ($row['acf_fc_layout'] ?? '') === 'shop_intro_block') {
                    $hasIntro = true;
                    break;
                }
            }

            if (! $hasIntro) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::warning(sprintf('%s missing shop_intro_block', $localSlug));
                }
                ++$failed;
                continue;
            }

            if ($page['paras'] === [] && function_exists('WP_CLI')) {
                \WP_CLI::log(sprintf('note %s — no intro paragraphs on live page', $localSlug));
            }

            $components = self::applyLivePageToComponents(
                $components,
                $page,
                $localSlug,
                $liveSlug,
                $title
            );

            $logoId = 0;
            if ($page['logo_url'] !== '') {
                $logoId = VenueLiveRetailerPage::sideloadLogo($page['logo_url'], $localSlug);
                if ($logoId > 0) {
                    if (! $dryRun) {
                        update_field('eat_drink_logo', $logoId, $postId);
                    }
                    foreach ($components as $i => $row) {
                        if (($row['acf_fc_layout'] ?? '') !== 'image_hero') {
                            continue;
                        }
                        $row['hero_logo'] = $logoId;
                        $row['hero_title_line'] = '';
                        $row['hero_subtitle_line'] = '';
                        $row['hero_title_in_image'] = false;
                        $components[$i] = $row;
                        break;
                    }
                }
            }

            $phone = $page['phone'] !== '' ? $page['phone'] : ($catalog[$localSlug]['phone'] ?? '');
            $hoursCount = count($page['opening_hours_rows']);

            if (function_exists('WP_CLI')) {
                \WP_CLI::log(sprintf(
                    '%s%s ← live/%s | paras=%d | hours=%d | phone=%s | logo=%s',
                    $dryRun ? '[dry-run] ' : '',
                    $localSlug,
                    $liveSlug,
                    count($page['paras']),
                    $hoursCount,
                    $phone !== '' ? $phone : '(none)',
                    $logoId > 0 ? 'id ' . $logoId : ($page['logo_url'] !== '' ? 'sideload failed' : 'none')
                ));
            }

            if (! $dryRun) {
                delete_field('components', $postId);
                update_field('components', $components, $postId);
                self::syncHoursSummaryFromComponents($postId, $components);
                ++$updated;
            } else {
                ++$updated;
            }
        }

        return ['updated' => $updated, 'skipped' => $skipped, 'failed' => $failed];
    }
}
