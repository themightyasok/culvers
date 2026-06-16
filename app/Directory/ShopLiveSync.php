<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Syncs shop singles from culversquare.co.uk `/retailers/{slug}/` pages.
 * Creates missing local posts when a live → local mapping exists.
 */
final class ShopLiveSync
{
    private const SHOPPING_URL = 'https://www.culversquare.co.uk/shopping/';

    private const GENERIC_HERO_URL = 'https://www.culversquare.co.uk/wp-content/uploads/2026/02/CS_Generic-Web-Banner_1800x600px-01-1.png';

    /** @var array<string, string> Live retailer slug → local culvers_shop post_name */
    private const LIVE_TO_LOCAL = [
        'accessorize' => 'accessorize-london',
        'ann-summers' => 'ann-summers',
        'clarks' => 'clarks',
        'colchester-aesthetics-beauty' => 'colchester-aesthetics-beauty',
        'cosmic-tattoo' => 'cosmic-tattoo',
        'eco-vape' => 'ecovape',
        'ernest-jones' => 'ernest-jones',
        'fraser-hart' => 'fraser-hart',
        'hm' => 'hm',
        'hmv' => 'hmv',
        'hotel-chocolat' => 'hotel-chocolat',
        'istore' => 'istore',
        'love-reform-pilates-studio' => 'love-reform',
        'monsoon' => 'monsoon',
        'nerd-base' => 'nerd-base',
        'phoenix-vapes' => 'phoenix-vapes',
        'pandora' => 'pandora',
        'schuh' => 'schuh',
        'skechers' => 'skechers',
        'smiggle' => 'smiggle',
        'sostrene-grene' => 'sostrene-grene',
        'the-fragrance-shop' => 'the-fragrance-shop',
        'tiger' => 'flying-tiger-copenhagen',
        'tk-maxx' => 'tk-maxx',
        'topgift' => 'wye-mobility',
    ];

    /** @var array<string, string> Live ACF category value → local culvers_shop_category slug */
    private const LIVE_CATEGORY_TO_LOCAL = [
        'footwear' => 'fashion',
        'fashion' => 'fashion',
        'jewellery' => 'jewellery',
        'accessories' => 'jewellery',
        'health_and_beauty' => 'beauty-wellbeing',
        'beauty' => 'beauty-wellbeing',
        'specialist_and_services' => 'speciality',
        'toys_and_gifts' => 'toys-gifts',
        'technology' => 'technology',
    ];

    /**
     * @return array<string, array{
     *     phone: string,
     *     address: string,
     *     instagram_url: string,
     *     instagram_handle: string,
     *     show_social: bool,
     *     shop_type: string
     * }>
     */
    public static function storeDetailsCatalog(): array
    {
        return [
            'clarks' => [
                'phone' => '01206 369473',
                'address' => '4 Culver Square, Colchester, Essex CO1 1WF',
                'instagram_url' => 'https://www.instagram.com/clarks_shoes/',
                'instagram_handle' => '@clarks_shoes',
                'show_social' => true,
                'shop_type' => 'national-store',
            ],
            'fraser-hart' => [
                'phone' => '01206 575276',
                'address' => '10 Shewell Walk, Colchester, Essex CO1 1WG',
                'instagram_url' => 'https://www.instagram.com/fraserhart/',
                'instagram_handle' => '@fraserhart',
                'show_social' => true,
                'shop_type' => 'national-store',
            ],
            'colchester-aesthetics-beauty' => [
                'phone' => '07434923892',
                'address' => "46 St John's Street, Colchester, Essex CO2 7AD",
                'instagram_url' => '',
                'instagram_handle' => '',
                'show_social' => false,
                'shop_type' => 'independent',
            ],
            'nerd-base' => [
                'phone' => '01206 670451',
                'address' => '24 Sir Isaacs Walk, Colchester, Essex CO1 1JJ',
                'instagram_url' => 'https://www.instagram.com/menkind/',
                'instagram_handle' => '@menkind',
                'show_social' => true,
                'shop_type' => 'national-store',
            ],
            'phoenix-vapes' => [
                'phone' => '01206 619646',
                'address' => '39-40 Sir Isaacs Walk, Colchester, Essex CO1 1JJ',
                'instagram_url' => '',
                'instagram_handle' => '',
                'show_social' => false,
                'shop_type' => 'independent',
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
     *     hero_image_url: string,
     *     live_category_values: list<string>,
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
                $split = DirectoryLiveIntroCopy::splitForBlocks($displayTitle, $page['paras'], $page['lists']);
                if ($split['intro_html'] !== '') {
                    $row['intro_body'] = $split['intro_html'];
                    $cta = DirectoryIntroCta::resolve($localSlug, $liveSlug, $displayTitle);
                    if ($cta !== null) {
                        $row['intro_cta_url'] = $cta['url'];
                        $row['intro_cta_label'] = $cta['label'];
                    }
                    $components[$i] = $row;
                }
            }

            if ($layout === 'shop_split_highlight' && $page['paras'] !== []) {
                $split = DirectoryLiveIntroCopy::splitForBlocks($displayTitle, $page['paras'], $page['lists']);
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
                    'address' => "Culver Square Shopping Centre,\nColchester CO1 1JQ",
                    'instagram_url' => '',
                    'instagram_handle' => '',
                    'show_social' => false,
                    'shop_type' => 'national-store',
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
            } elseif ($layout === 'opening_hours') {
                $components[$i] = VenueOpeningHours::applyRetailerPresentationDefaults($row);
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
                update_field('opening_hours_summary', $line, $postId);
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
        $response = wp_remote_get(self::SHOPPING_URL, [
            'timeout' => 25,
            'user-agent' => 'CulversTheme/1.0 (shop-live-sync)',
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
     * @param  list<string>  $liveCategoryValues
     */
    public static function assignTaxonomies(int $postId, array $liveCategoryValues, string $localSlug): void
    {
        $catalog = self::storeDetailsCatalog();
        $shopType = $catalog[$localSlug]['shop_type'] ?? 'national-store';

        $categorySlug = '';
        foreach ($liveCategoryValues as $value) {
            $mapped = self::LIVE_CATEGORY_TO_LOCAL[$value] ?? '';
            if ($mapped !== '') {
                $categorySlug = $mapped;
                break;
            }
        }

        if ($categorySlug !== '') {
            wp_set_object_terms($postId, [$categorySlug], 'culvers_shop_category', false);
        }

        wp_set_object_terms($postId, [$shopType], 'culvers_shop_type', false);
    }

    /** @var list<string> Shops added in the 2026-05 missing-retailers import. */
    public const DEPLOY_SHOP_SLUGS = [
        'clarks',
        'fraser-hart',
        'colchester-aesthetics-beauty',
        'nerd-base',
        'phoenix-vapes',
    ];

    /**
     * Relative wp-content/uploads paths pushed by push-new-shops.sh (--ignore-existing).
     *
     * @var array<string, array{logo: string, hero: string}>
     */
    public const DEPLOY_MEDIA_BY_SLUG = [
        'clarks' => [
            'logo' => '2026/05/clarks-live-logo-f6d19cef49.svg',
            'hero' => '2026/05/clarks-hero-live-logo-aca10a23be.png',
        ],
        'fraser-hart' => [
            'logo' => '2026/05/fraser-hart-live-logo-9bf97daff2-1.svg',
            'hero' => '2026/05/fraser-hart-hero-live-logo-9dc383f734-scaled.jpg',
        ],
        'colchester-aesthetics-beauty' => [
            'logo' => '2026/05/colchester-aesthetics-beauty-live-logo-7acedf677d.svg',
            'hero' => '2026/05/colchester-aesthetics-beauty-hero-live-logo-b1a6088092.jpg',
        ],
        'nerd-base' => [
            'logo' => '2026/05/nerd-base-live-logo-2a12751cf4.svg',
            'hero' => '2026/05/nerd-base-hero-live-logo-14ddb3febe-scaled.png',
        ],
        'phoenix-vapes' => [
            'logo' => '2026/05/phoenix-vapes-live-logo-2e6c366c8f.svg',
            'hero' => '2026/05/phoenix-vapes-hero-live-logo-2c3d8adb0c.png',
        ],
    ];

    /**
     * @return array{updated: int, created: int, skipped: int, failed: int}
     */
    public static function run(
        bool $dryRun = false,
        ?string $onlyLocalSlug = null,
        bool $createMissing = true,
        bool $updateExisting = true,
        bool $useLocalMediaOnly = false
    ): array {
        if (! function_exists('get_field') || ! function_exists('update_field')) {
            throw new \RuntimeException('ACF is required.');
        }

        DirectoryMediaPopulate::loadDependencies();

        $map = self::liveToLocalMap();
        $liveSlugs = self::discoverLiveSlugs();

        $updated = 0;
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($liveSlugs as $liveSlug) {
            $localSlug = $map[$liveSlug] ?? null;
            if ($localSlug === null) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::log(sprintf('skip live "%s" — no local culvers_shop mapping', $liveSlug));
                }
                ++$skipped;
                continue;
            }

            if ($onlyLocalSlug !== null && $onlyLocalSlug !== $localSlug) {
                continue;
            }

            $page = DirectoryLiveRetailerPage::fetch($liveSlug);
            if ($page === null) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::warning(sprintf('fetch failed for live/%s', $liveSlug));
                }
                ++$failed;
                continue;
            }

            $posts = get_posts([
                'post_type' => 'culvers_shop',
                'name' => $localSlug,
                'post_status' => 'any',
                'posts_per_page' => 1,
            ]);

            $isNew = $posts === [];
            if ($isNew && ! $createMissing) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::warning(sprintf('no local post for %s (live %s)', $localSlug, $liveSlug));
                }
                ++$failed;
                continue;
            }

            if (! $isNew && ! $updateExisting) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::log(sprintf('skip %s — already exists (create-only)', $localSlug));
                }
                ++$skipped;
                continue;
            }

            $title = $page['title'] !== '' ? $page['title'] : ucwords(str_replace('-', ' ', $localSlug));

            if ($isNew) {
                if ($dryRun) {
                    if (function_exists('WP_CLI')) {
                        \WP_CLI::log(sprintf('[dry-run] create %s ← live/%s (%s)', $localSlug, $liveSlug, $title));
                    }
                    ++$created;
                    continue;
                }

                $postId = (int) wp_insert_post([
                    'post_type' => 'culvers_shop',
                    'post_name' => $localSlug,
                    'post_title' => $title,
                    'post_status' => 'publish',
                ], true);

                if (is_wp_error($postId) || $postId <= 0) {
                    if (function_exists('WP_CLI')) {
                        \WP_CLI::warning(sprintf('create failed for %s', $localSlug));
                    }
                    ++$failed;
                    continue;
                }

                DirectoryFlexibleDefaults::persistDefaultsForPost($postId);
                ++$created;
            } else {
                $postId = (int) $posts[0]->ID;
                if (! $dryRun && get_the_title($postId) !== $title) {
                    wp_update_post(['ID' => $postId, 'post_title' => $title]);
                }
            }

            $components = get_field('components', $postId);
            if (! is_array($components) || $components === []) {
                if (! $dryRun) {
                    DirectoryFlexibleDefaults::persistDefaultsForPost($postId);
                    $components = get_field('components', $postId);
                }
            }

            if (! is_array($components) || $components === []) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::warning(sprintf('%s has no flexible components', $localSlug));
                }
                ++$failed;
                continue;
            }

            $components = self::applyLivePageToComponents(
                $components,
                $page,
                $localSlug,
                $liveSlug,
                $title
            );

            $logoId = 0;
            $heroId = 0;

            if ($useLocalMediaOnly) {
                $media = self::DEPLOY_MEDIA_BY_SLUG[$localSlug] ?? null;
                if (is_array($media)) {
                    $logoId = DirectoryMediaPopulate::attachmentIdForRelativeUploadPath($media['logo']);
                    $heroId = DirectoryMediaPopulate::attachmentIdForRelativeUploadPath($media['hero']);
                }
            } else {
                if ($page['logo_url'] !== '') {
                    $logoId = DirectoryLiveRetailerPage::sideloadLogo($page['logo_url'], $localSlug);
                }

                $heroUrl = $page['hero_image_url'] !== '' ? $page['hero_image_url'] : self::GENERIC_HERO_URL;
                $heroId = DirectoryLiveRetailerPage::sideloadLogo($heroUrl, $localSlug . '-hero');
            }

            foreach ($components as $i => $row) {
                if (($row['acf_fc_layout'] ?? '') !== 'image_hero') {
                    continue;
                }

                if ($heroId > 0) {
                    $row['hero_image'] = $heroId;
                }
                if ($logoId > 0) {
                    $row['hero_logo'] = $logoId;
                    $row['hero_title_line'] = '';
                    $row['hero_subtitle_line'] = '';
                    $row['hero_title_in_image'] = false;
                }
                $components[$i] = $row;
                break;
            }

            $catalog = self::storeDetailsCatalog();
            $phone = $page['phone'] !== '' ? $page['phone'] : ($catalog[$localSlug]['phone'] ?? '');

            if (function_exists('WP_CLI')) {
                \WP_CLI::log(sprintf(
                    '%s%s ← live/%s | paras=%d | hours=%d | phone=%s | logo=%s | hero=%s',
                    $dryRun ? '[dry-run] ' : ($isNew ? 'create ' : 'update '),
                    $localSlug,
                    $liveSlug,
                    count($page['paras']),
                    count($page['opening_hours_rows']),
                    $phone !== '' ? $phone : '(none)',
                    $logoId > 0 ? 'id ' . $logoId : ($useLocalMediaOnly ? 'local file missing' : ($page['logo_url'] !== '' ? 'sideload failed' : 'none')),
                    $heroId > 0 ? 'id ' . $heroId : ($useLocalMediaOnly ? 'local file missing' : 'none')
                ));
            }

            if ($dryRun) {
                ++$updated;
                continue;
            }

            delete_field('components', $postId);
            update_field('components', $components, $postId);

            if ($logoId > 0) {
                update_field('shop_logo', $logoId, $postId);
            }

            if (self::DEPLOY_LOGO_PRESERVE_COLORS[$localSlug] ?? false) {
                update_field('shop_logo_preserve_colors', 1, $postId);
                foreach ($components as $i => $row) {
                    if (($row['acf_fc_layout'] ?? '') !== 'image_hero') {
                        continue;
                    }
                    $row['hero_logo_preserve_colors'] = 1;
                    $components[$i] = $row;
                    break;
                }
            }

            self::assignTaxonomies($postId, $page['live_category_values'], $localSlug);
            self::syncHoursSummaryFromComponents($postId, $components);

            if (! $isNew) {
                ++$updated;
            }
        }

        return [
            'updated' => $updated,
            'created' => $created,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    /** @var array<string, string> Card logo path overrides (slug → uploads-relative path). */
    public const CARD_LOGO_REPAIR = [
        'cosmic-tattoo' => '2026/05/cosmic-tattoo.svg',
    ];

    /** @var array<string, bool> Slugs whose marks must render without the white CSS filter. */
    public const DEPLOY_LOGO_PRESERVE_COLORS = [
        'colchester-aesthetics-beauty' => true,
        'nerd-base' => true,
        'phoenix-vapes' => true,
        'fraser-hart' => true,
        'cosmic-tattoo' => true,
    ];

    /**
     * @param  list<array<string, mixed>>  $components
     * @return list<array<string, mixed>>
     */
    private static function applyLogoPreserveToComponents(array $components): array
    {
        foreach ($components as $i => $row) {
            if (($row['acf_fc_layout'] ?? '') !== 'image_hero') {
                continue;
            }
            $row['hero_logo_preserve_colors'] = 1;
            $components[$i] = $row;
            break;
        }

        return $components;
    }

    /**
     * Register on-disk deploy logos and wire `shop_logo` + hero `hero_logo`.
     * Also syncs {@see DEPLOY_LOGO_PRESERVE_COLORS} so moss tiles / heroes skip the white filter.
     *
     * @return array{repaired: int, skipped: int, failed: int}
     */
    public static function repairDeployLogos(
        bool $dryRun = false,
        ?string $onlySlug = null,
        bool $fixCosmicCard = false
    ): array {
        if (! function_exists('get_field') || ! function_exists('update_field')) {
            throw new \RuntimeException('ACF is required.');
        }

        DirectoryMediaPopulate::loadDependencies();

        $repaired = 0;
        $skipped = 0;
        $failed = 0;

        $targets = self::DEPLOY_MEDIA_BY_SLUG;
        if ($fixCosmicCard) {
            $targets = array_merge($targets, array_map(
                static fn (string $path): array => ['logo' => $path, 'hero' => ''],
                self::CARD_LOGO_REPAIR
            ));
        }

        $preserveSlugs = array_keys(self::DEPLOY_LOGO_PRESERVE_COLORS);
        $allSlugs = array_values(array_unique(array_merge(array_keys($targets), $preserveSlugs)));

        foreach ($allSlugs as $slug) {
            if ($onlySlug !== null && $onlySlug !== $slug) {
                continue;
            }

            $posts = get_posts([
                'post_type' => 'culvers_shop',
                'name' => $slug,
                'post_status' => 'any',
                'posts_per_page' => 1,
            ]);

            if ($posts === []) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::warning(sprintf('no post for %s', $slug));
                }
                ++$failed;
                continue;
            }

            $postId = (int) $posts[0]->ID;
            $media = $targets[$slug] ?? null;
            $logoPath = is_array($media) ? $media['logo'] : '';
            $needsPreserve = (bool) (self::DEPLOY_LOGO_PRESERVE_COLORS[$slug] ?? false);

            $components = get_field('components', $postId);
            if (! is_array($components)) {
                $components = [];
            }

            $didWork = false;
            $currentLogoId = (int) get_post_meta($postId, 'shop_logo', true);
            $allowReplace = $fixCosmicCard && isset(self::CARD_LOGO_REPAIR[$slug]);
            $shouldRepairLogo = $logoPath !== '' && ($currentLogoId <= 0 || $allowReplace);

            if ($shouldRepairLogo) {
                $logoId = DirectoryMediaPopulate::attachmentIdForRelativeUploadPath($logoPath);
                if ($logoId <= 0) {
                    if (function_exists('WP_CLI')) {
                        \WP_CLI::warning(sprintf('logo register failed for %s (%s)', $slug, $logoPath));
                    }
                    ++$failed;
                    continue;
                }

                foreach ($components as $i => $row) {
                    if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'image_hero') {
                        continue;
                    }
                    $row['hero_logo'] = $logoId;
                    $row['hero_title_line'] = '';
                    $row['hero_subtitle_line'] = '';
                    $row['hero_title_in_image'] = false;
                    $components[$i] = $row;
                    break;
                }

                if (function_exists('WP_CLI')) {
                    \WP_CLI::log(sprintf(
                        '%s%s shop_logo=%d (%s)',
                        $dryRun ? '[dry-run] ' : '',
                        $slug,
                        $logoId,
                        $logoPath
                    ));
                }

                if (! $dryRun) {
                    update_field('shop_logo', $logoId, $postId);
                }

                $didWork = true;
            } elseif ($logoPath !== '' && $currentLogoId > 0) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::log(sprintf('skip %s logo — already set (#%d)', $slug, $currentLogoId));
                }
            }

            if ($needsPreserve) {
                $components = self::applyLogoPreserveToComponents($components);
                if (function_exists('WP_CLI')) {
                    \WP_CLI::log(sprintf(
                        '%s%s preserve_colors=on (shop card + hero)',
                        $dryRun ? '[dry-run] ' : '',
                        $slug
                    ));
                }
                if (! $dryRun) {
                    update_field('shop_logo_preserve_colors', 1, $postId);
                }
                $didWork = true;
            }

            if ($didWork && ! $dryRun && $components !== []) {
                delete_field('components', $postId);
                update_field('components', $components, $postId);
            }

            if ($didWork) {
                ++$repaired;
            } else {
                ++$skipped;
            }
        }

        return ['repaired' => $repaired, 'skipped' => $skipped, 'failed' => $failed];
    }

    /** @var list<string> Shop singles imported from live that need opening-hours repair. */
    public const REPAIR_OPENING_HOURS_SLUGS = [
        'clarks',
        'fraser-hart',
        'colchester-aesthetics-beauty',
        'nerd-base',
        'phoenix-vapes',
    ];

    /**
     * Sets retailer opening-hours heading/context and re-syncs day rows from live when available.
     *
     * @return array{repaired: int, skipped: int, failed: int}
     */
    public static function repairRetailerOpeningHours(bool $dryRun = false, ?string $onlySlug = null): array
    {
        if (! function_exists('get_field') || ! function_exists('update_field')) {
            throw new \RuntimeException('ACF is required.');
        }

        $repaired = 0;
        $skipped = 0;
        $failed = 0;

        foreach (self::REPAIR_OPENING_HOURS_SLUGS as $slug) {
            if ($onlySlug !== null && $onlySlug !== $slug) {
                continue;
            }

            $posts = get_posts([
                'post_type' => 'culvers_shop',
                'name' => $slug,
                'post_status' => 'any',
                'posts_per_page' => 1,
            ]);

            if ($posts === []) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::warning(sprintf('no post for %s', $slug));
                }
                ++$failed;
                continue;
            }

            $postId = (int) $posts[0]->ID;
            $liveSlug = self::localToLiveSlug($slug);
            $liveRows = [];

            if ($liveSlug !== null) {
                $page = DirectoryLiveRetailerPage::fetch($liveSlug);
                if ($page !== null && $page['opening_hours_rows'] !== []) {
                    $liveRows = $page['opening_hours_rows'];
                }
            }

            $components = get_field('components', $postId);
            if (! is_array($components)) {
                $components = [];
            }

            $found = false;
            $changed = false;

            foreach ($components as $i => $row) {
                if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'opening_hours') {
                    continue;
                }

                $found = true;
                $before = $row;

                if ($liveRows !== []) {
                    $row = VenueOpeningHours::mergeIntoOpeningHoursRow($row, $liveRows);
                } else {
                    $row = VenueOpeningHours::applyRetailerPresentationDefaults($row);
                }

                if ($row !== $before) {
                    $changed = true;
                }

                $components[$i] = $row;
                break;
            }

            if (! $found) {
                if (function_exists('WP_CLI')) {
                    \WP_CLI::warning(sprintf('no opening_hours row for %s', $slug));
                }
                ++$failed;
                continue;
            }

            $rowCount = 0;
            foreach ($components as $row) {
                if (is_array($row) && ($row['acf_fc_layout'] ?? '') === 'opening_hours') {
                    $rowCount = count(is_array($row['hours_rows'] ?? null) ? $row['hours_rows'] : []);
                    break;
                }
            }

            if (function_exists('WP_CLI')) {
                \WP_CLI::log(sprintf(
                    '%s%s rows=%d live=%s',
                    $dryRun ? '[dry-run] ' : '',
                    $slug,
                    $rowCount,
                    $liveRows !== [] ? 'yes' : 'heading-only'
                ));
            }

            if (! $changed) {
                ++$skipped;
                continue;
            }

            if (! $dryRun) {
                delete_field('components', $postId);
                update_field('components', $components, $postId);
                self::syncHoursSummaryFromComponents($postId, $components);
            }

            ++$repaired;
        }

        return ['repaired' => $repaired, 'skipped' => $skipped, 'failed' => $failed];
    }

    public static function localToLiveSlug(string $localSlug): ?string
    {
        foreach (self::LIVE_TO_LOCAL as $live => $local) {
            if ($local === $localSlug) {
                return $live;
            }
        }

        return null;
    }
}
