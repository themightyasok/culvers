<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * One-shot importer: live culversquare.co.uk offers + what's-on posts → local CPTs.
 *
 * Sources:
 *   GET /wp-json/wp/v2/offers
 *   GET /wp-json/wp/v2/posts?categories={events|news|gallery}
 *
 * Writes full flexible-component stacks on imported CPT singles only.
 * Does not modify archive option heroes or the /whats-on/ landing page.
 */
final class DirectoryLiveImport
{
    private const LIVE_BASE = 'https://www.culversquare.co.uk';

    /** @var array<int, string> */
    private const LIVE_CATEGORY_MAP = [
        3 => 'culvers_event',
        5 => 'culvers_event',
        2 => 'culvers_news',
    ];

    /** @var array<string, int> */
    private array $imageCache = [];

    /** @var list<string> */
    private array $importedSlugs = [];

    /**
     * @return array{offers: int, events: int, news: int, pruned: int}
     */
    public function run(bool $dryRun = false, bool $prune = false): array
    {
        $counts = ['offers' => 0, 'events' => 0, 'news' => 0, 'pruned' => 0];

        $counts['offers'] = $this->importOffers($dryRun);
        $counts['events'] = $this->importPostsByCategory(3, $dryRun);
        $counts['events'] += $this->importPostsByCategory(5, $dryRun);
        $counts['news'] = $this->importPostsByCategory(2, $dryRun);

        if ($prune) {
            $counts['pruned'] = $this->pruneUnimported($dryRun);
        }

        return $counts;
    }

    private function importOffers(bool $dryRun): int
    {
        $items = $this->fetchJson(self::LIVE_BASE . '/wp-json/wp/v2/offers?per_page=100&orderby=date&order=desc');
        if (! is_array($items)) {
            return 0;
        }

        $count = 0;
        foreach ($items as $item) {
            if (! is_array($item) || ! $this->isActiveOffer($item)) {
                continue;
            }

            $slug = (string) ($item['slug'] ?? '');
            if ($slug === '') {
                continue;
            }

            $acf = is_array($item['acf'] ?? null) ? $item['acf'] : [];
            $title = $this->plainText($item['title']['rendered'] ?? $slug);
            $retailer = $this->retailerName($acf);
            $bodyHtml = is_string($acf['description'] ?? null) ? $acf['description'] : '';
            $intro = $this->firstParagraph($bodyHtml);
            $heroUrl = $this->resolveHeroUrl($item, $acf);
            $splitUrl = $heroUrl;

            $validity = $this->formatDateRange(
                (string) ($acf['start_date'] ?? ''),
                (string) ($acf['end_date'] ?? '')
            );

            if ($dryRun) {
                $this->log("[dry-run] offer: {$slug} ({$title})");
                ++$count;
                $this->importedSlugs[] = 'culvers_offer:' . $slug;
                continue;
            }

            $postId = $this->upsertPost('culvers_offer', $slug, $title, $item);
            if ($postId <= 0) {
                continue;
            }

            update_field('offer_card_validity', $validity !== '' ? $validity : __('While stocks last', 'culvers'), $postId);
            update_field('offer_card_venue', $retailer !== '' ? $retailer : $title, $postId);

            $heroId = $this->sideloadImage($heroUrl, 'offer-' . $slug . '-hero');
            if ($heroId > 0) {
                set_post_thumbnail($postId, $heroId);
            }

            $splitId = $this->sideloadImage($splitUrl, 'offer-' . $slug . '-split');
            if ($splitId <= 0) {
                $splitId = $heroId;
            }

            $components = $this->buildOfferComponents($title, $intro, $bodyHtml, $heroId, $splitId);
            update_field('components', $components, $postId);

            $this->importedSlugs[] = 'culvers_offer:' . $slug;
            ++$count;
            $this->log("offer: {$slug} (#{$postId})");
        }

        return $count;
    }

    /**
     * Import specific live posts by slug into the given CPT (events / news).
     *
     * @param  list<array{slug: string, post_type: string}>  $targets
     * @return array{ok: int, failed: int}
     */
    public function importStoryTargets(array $targets, bool $dryRun = false): array
    {
        $ok = 0;
        $failed = 0;

        foreach ($targets as $target) {
            $slug = sanitize_title($target['slug']);
            $postType = $target['post_type'];
            if ($slug === '' || ! in_array($postType, ['culvers_event', 'culvers_news'], true)) {
                ++$failed;
                $this->log("[error] invalid target: {$slug} / {$postType}");

                continue;
            }

            $items = $this->fetchJson(
                self::LIVE_BASE . '/wp-json/wp/v2/posts?slug=' . rawurlencode($slug) . '&_embed'
            );
            if (! is_array($items) || $items === [] || ! is_array($items[0] ?? null)) {
                ++$failed;
                $this->log("[error] live post not found: {$slug}");

                continue;
            }

            if ($this->importStoryItem($items[0], $postType, $dryRun)) {
                ++$ok;
            } else {
                ++$failed;
            }
        }

        return ['ok' => $ok, 'failed' => $failed];
    }

    private function importPostsByCategory(int $categoryId, bool $dryRun): int
    {
        $postType = self::LIVE_CATEGORY_MAP[$categoryId] ?? null;
        if ($postType === null) {
            return 0;
        }

        $items = $this->fetchJson(
            self::LIVE_BASE . '/wp-json/wp/v2/posts?categories=' . $categoryId . '&per_page=100&orderby=date&order=desc&_embed'
        );
        if (! is_array($items)) {
            return 0;
        }

        $count = 0;
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if ($this->importStoryItem($item, $postType, $dryRun)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function importStoryItem(array $item, string $postType, bool $dryRun): bool
    {
        $slug = (string) ($item['slug'] ?? '');
        if ($slug === '') {
            return false;
        }

        $acf = is_array($item['acf'] ?? null) ? $item['acf'] : [];
        $title = $this->plainText($item['title']['rendered'] ?? $slug);
        $bodyHtml = is_string($acf['article_body'] ?? null) ? $acf['article_body'] : '';
        if ($bodyHtml === '' && is_string($item['content']['rendered'] ?? null)) {
            $bodyHtml = $item['content']['rendered'];
        }
        $closing = is_string($acf['closing_text'] ?? null) ? $acf['closing_text'] : '';
        if ($closing !== '') {
            $bodyHtml .= $closing;
        }

        $intro = $this->firstParagraph($bodyHtml);
        $heroUrl = $this->resolvePostHeroUrl($item, $acf);
        $splitUrl = $heroUrl;

        if ($dryRun) {
            $this->log("[dry-run] {$postType}: {$slug} ({$title})");
            $this->importedSlugs[] = $postType . ':' . $slug;

            return true;
        }

        $postId = $this->upsertPost($postType, $slug, $title, $item);
        if ($postId <= 0) {
            return false;
        }

        if ($postType === 'culvers_event') {
            update_field('event_card_date', $this->formatDateRange(
                (string) ($acf['start_date'] ?? ''),
                (string) ($acf['end_date'] ?? '')
            ), $postId);
            update_field('event_card_time', __('See event details', 'culvers'), $postId);
            update_field('event_card_location', __('Culver Square', 'culvers'), $postId);
            $heroId = $this->sideloadImage($heroUrl, 'event-' . $slug . '-hero');
            $splitId = $this->sideloadImage($splitUrl, 'event-' . $slug . '-split');
            if ($splitId <= 0) {
                $splitId = $heroId;
            }
            $components = $this->buildEventComponents($title, $intro, $bodyHtml, $heroId, $splitId);
        } else {
            $heroId = $this->sideloadImage($heroUrl, 'news-' . $slug . '-hero');
            $splitId = $this->sideloadImage($splitUrl, 'news-' . $slug . '-split');
            if ($splitId <= 0) {
                $splitId = $heroId;
            }
            $components = $this->buildNewsComponents($title, $intro, $bodyHtml, $heroId, $splitId);
        }

        if ($heroId > 0) {
            set_post_thumbnail($postId, $heroId);
        }

        update_field('components', $components, $postId);

        $this->importedSlugs[] = $postType . ':' . $slug;
        $this->log("{$postType}: {$slug} (#{$postId})");

        return true;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function upsertPost(string $postType, string $slug, string $title, array $item): int
    {
        $existing = get_posts([
            'post_type' => $postType,
            'name' => $slug,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ]);

        $postDate = isset($item['date']) ? (string) $item['date'] : current_time('mysql');
        $postArgs = [
            'post_title' => $title,
            'post_name' => $slug,
            'post_type' => $postType,
            'post_status' => 'publish',
            'post_date' => $postDate,
            'post_date_gmt' => isset($item['date_gmt']) ? (string) $item['date_gmt'] : get_gmt_from_date($postDate),
        ];

        if ($existing !== []) {
            $postArgs['ID'] = (int) $existing[0];
            $result = wp_update_post($postArgs, true);
        } else {
            $result = wp_insert_post($postArgs, true);
        }

        if (is_wp_error($result)) {
            $this->log('[error] ' . $result->get_error_message() . " ({$postType}/{$slug})");

            return 0;
        }

        return (int) $result;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function isActiveOffer(array $item): bool
    {
        $acf = is_array($item['acf'] ?? null) ? $item['acf'] : [];
        $end = trim((string) ($acf['end_date'] ?? ''));
        if ($end === '') {
            return false;
        }

        $endTs = $this->parseUkDate($end);

        return $endTs !== null && $endTs >= strtotime('today');
    }

    /**
     * @param array<string, mixed> $acf
     */
    private function retailerName(array $acf): string
    {
        $retailers = $acf['retailer'] ?? null;
        if (! is_array($retailers) || $retailers === []) {
            return '';
        }

        $first = $retailers[0];

        return is_array($first) ? trim((string) ($first['post_title'] ?? '')) : '';
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $acf
     */
    private function resolveHeroUrl(array $item, array $acf): string
    {
        $embedded = $item['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '';
        if (is_string($embedded) && $embedded !== '') {
            return $embedded;
        }

        $thumb = $acf['thumbnail_image'] ?? null;
        if (is_array($thumb) && is_string($thumb['url'] ?? null) && $thumb['url'] !== '') {
            return $thumb['url'];
        }

        $retailers = $acf['retailer'] ?? null;
        if (is_array($retailers) && isset($retailers[0]['ID'])) {
            $retailer = $this->fetchJson(self::LIVE_BASE . '/wp-json/wp/v2/retailers/' . (int) $retailers[0]['ID']);
            if (is_array($retailer)) {
                $logo = $retailer['acf']['logo'] ?? null;
                if (is_array($logo) && is_string($logo['url'] ?? null)) {
                    return $logo['url'];
                }
            }
        }

        return self::LIVE_BASE . '/wp-content/uploads/2024/07/CS_Generic-Web-Banner_1800x600px-04-2048x683.png';
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $acf
     */
    private function resolvePostHeroUrl(array $item, array $acf): string
    {
        $embedded = $item['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '';
        if (is_string($embedded) && $embedded !== '') {
            return $embedded;
        }

        $thumb = $acf['thumbnail_image'] ?? null;
        if (is_array($thumb) && is_string($thumb['url'] ?? null) && $thumb['url'] !== '') {
            return $thumb['url'];
        }

        return self::LIVE_BASE . '/wp-content/uploads/2024/07/CS_Generic-Web-Banner_1800x600px-04-2048x683.png';
    }

    private function sideloadImage(string $url, string $basenameHint): int
    {
        if ($url === '') {
            return 0;
        }

        if (isset($this->imageCache[$url])) {
            return $this->imageCache[$url];
        }

        $id = DirectoryMediaPopulate::sideloadFromUrlPublic($url, sanitize_file_name($basenameHint));
        if ($id > 0) {
            $this->imageCache[$url] = $id;
        }

        return $id;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildOfferComponents(
        string $title,
        string $intro,
        string $bodyHtml,
        int $heroId,
        int $splitId
    ): array {
        return [
            $this->imageHeroRow($heroId),
            $this->sectionHeaderRow($title, $intro !== '' ? $intro : $this->plainText($bodyHtml)),
            $this->splitHighlightRow($title, $bodyHtml !== '' ? $bodyHtml : $intro, $splitId),
            $this->socialShareRow(),
            $this->relatedCptRow('culvers_offer', __('More Offers', 'culvers'), __('See all of the brilliant offers happening at Culver Square', 'culvers')),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildEventComponents(
        string $title,
        string $intro,
        string $bodyHtml,
        int $heroId,
        int $splitId
    ): array {
        return [
            $this->imageHeroRow($heroId),
            $this->sectionHeaderRow($title, $intro !== '' ? $intro : $this->plainText($bodyHtml)),
            $this->splitHighlightRow($title, $bodyHtml !== '' ? $bodyHtml : $intro, $splitId),
            $this->socialShareRow(),
            $this->relatedCptRow('culvers_event', __('More Events', 'culvers'), __('Discover what else is happening at Culver Square', 'culvers')),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildNewsComponents(
        string $title,
        string $intro,
        string $bodyHtml,
        int $heroId,
        int $splitId
    ): array {
        return [
            $this->imageHeroRow($heroId),
            $this->sectionHeaderRow($title, $intro !== '' ? $intro : $this->plainText($bodyHtml)),
            $this->splitHighlightRow($title, $bodyHtml !== '' ? $bodyHtml : $intro, $splitId),
            $this->sectionHeaderRow(__('Keep reading', 'culvers'), ''),
            $this->relatedCptRow('culvers_news', __('Latest News', 'culvers'), __('Centre updates and stories from Culver Square', 'culvers')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function imageHeroRow(int $attachmentId): array
    {
        return [
            'acf_fc_layout' => 'image_hero',
            'hero_image' => $attachmentId > 0 ? $attachmentId : '',
            'hero_image_mobile' => '',
            'hero_logo' => '',
            'hero_logo_source' => 'uploaded',
            'hero_title_line' => '',
            'hero_subtitle_line' => '',
            'hero_overlay_opacity' => 40,
            'hero_title_in_image' => '',
            'hero_title_tone' => 'white',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionHeaderRow(string $heading, string $body): array
    {
        return [
            'acf_fc_layout' => 'section_header',
            'header_eyebrow' => '',
            'header_heading' => $heading,
            'header_heading_level' => 'h2',
            'header_body' => $body,
            'header_align' => 'center',
            'header_max_width' => 'medium',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function splitHighlightRow(string $headline, string $bodyHtml, int $imageId): array
    {
        return [
            'acf_fc_layout' => 'shop_split_highlight',
            'split_ratio' => '50-50',
            'split_use_tabs' => 0,
            'split_copy_background' => 'olive',
            'split_kicker' => '',
            'split_headline' => $headline,
            'split_body' => $bodyHtml,
            'split_cta_label' => '',
            'split_cta_url' => '',
            'split_image' => $imageId > 0 ? $imageId : '',
            'split_tabs' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function socialShareRow(): array
    {
        return [
            'acf_fc_layout' => 'social_share',
            'share_heading' => __('Share with a friend', 'culvers'),
            'share_heading_level' => 'h2',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function relatedCptRow(string $postType, string $heading, string $body): array
    {
        $archive = (string) get_post_type_archive_link($postType);

        return [
            'acf_fc_layout' => 'three_card_block',
            'cards_subheading' => '',
            'cards_heading' => $heading,
            'cards_heading_level' => 'h2',
            'cards_body' => '<p>' . esc_html($body) . '</p>',
            'cards_media_overlay_opacity' => 25,
            'cards_source' => 'cpt',
            'cards_blog_categories' => [],
            'cards_blog_per_category' => 3,
            'cards_cpt_post_type' => [$postType],
            'cards_cpt_count' => 3,
            'cards_view_all_url' => $archive,
            'cards_view_all_label' => __('View all', 'culvers'),
        ];
    }

    private function pruneUnimported(bool $dryRun): int
    {
        $pruned = 0;
        $map = [
            'culvers_offer' => 'culvers_offer:',
            'culvers_event' => 'culvers_event:',
            'culvers_news' => 'culvers_news:',
        ];

        foreach ($map as $postType => $prefix) {
            $posts = get_posts([
                'post_type' => $postType,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
            ]);

            foreach ($posts as $postId) {
                $slug = (string) get_post_field('post_name', $postId);
                $key = $prefix . $slug;
                if (in_array($key, $this->importedSlugs, true)) {
                    continue;
                }

                if ($dryRun) {
                    $this->log("[dry-run] prune {$postType}/{$slug}");
                    ++$pruned;
                    continue;
                }

                wp_update_post(['ID' => (int) $postId, 'post_status' => 'draft']);
                ++$pruned;
                $this->log("pruned {$postType}/{$slug} → draft");
            }
        }

        return $pruned;
    }

    private function fetchJson(string $url): mixed
    {
        $response = wp_remote_get($url, [
            'timeout' => 60,
            'sslverify' => false,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            $this->log('[fetch] ' . $response->get_error_message() . ' — ' . $url);

            return null;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            $this->log("[fetch] HTTP {$code} — {$url}");

            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function plainText(string $html): string
    {
        return trim(html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function firstParagraph(string $html): string
    {
        $text = trim($this->plainText($html));
        if ($text === '') {
            return '';
        }

        $parts = preg_split('/\n\s*\n/', $text);
        if (! is_array($parts)) {
            return $text;
        }

        return trim($parts[0]);
    }

    private function formatDateRange(string $start, string $end): string
    {
        $start = trim($start);
        $end = trim($end);

        if ($start !== '' && $end !== '' && $start !== $end) {
            return sprintf(
                /* translators: 1: start date, 2: end date */
                __('%1$s – %2$s', 'culvers'),
                $this->formatUkDateLabel($start),
                $this->formatUkDateLabel($end)
            );
        }

        if ($end !== '') {
            return sprintf(
                /* translators: %s: end date */
                __('Until %s', 'culvers'),
                $this->formatUkDateLabel($end)
            );
        }

        if ($start !== '') {
            return $this->formatUkDateLabel($start);
        }

        return '';
    }

    private function formatUkDateLabel(string $value): string
    {
        $ts = $this->parseUkDate($value);

        return $ts !== null ? wp_date('j M Y', $ts) : $value;
    }

    private function parseUkDate(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $dt = \DateTimeImmutable::createFromFormat('d/m/Y', $value);
        if ($dt instanceof \DateTimeImmutable) {
            return $dt->getTimestamp();
        }

        $ts = strtotime($value);

        return $ts !== false ? $ts : null;
    }

    private function log(string $message): void
    {
        if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI')) {
            \WP_CLI::log($message);
        }
    }
}
