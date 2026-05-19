<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Populates demo content for the thin directory CPTs (Eat & Drink, Career,
 * Event, News, Offer) so each archive renders a Figma-shaped 8-12 card grid
 * instead of an empty / single-tile shell.
 *
 * Idempotent: posts are upserted by slug, ACF fields are rewritten on each
 * run, taxonomy terms are created on demand. Featured images and logos are
 * sampled from the existing media library (the {@see ShopDirectoryPopulate}
 * run already sideloads Figma logos + storefronts into the uploads folder,
 * so there is no external dependency here).
 *
 * @see scripts/placeholder-directory-populate.php
 */
final class PlaceholderDirectorySeeder
{
    /** @var list<int>|null */
    private static ?array $logoPool = null;

    /** @var list<int>|null */
    private static ?array $photoPool = null;

    /**
     * @return array<string, array{created:int,updated:int}>
     */
    public static function runSeed(): array
    {
        $report = [
            'eat_drink' => ['created' => 0, 'updated' => 0],
            'career' => ['created' => 0, 'updated' => 0],
            'event' => ['created' => 0, 'updated' => 0],
            'news' => ['created' => 0, 'updated' => 0],
            'offer' => ['created' => 0, 'updated' => 0],
        ];

        self::ensureCoreTaxonomies();

        foreach (self::eatDrinkRows() as $row) {
            self::upsertEatDrink($row, $report['eat_drink']);
        }
        foreach (self::careerRows() as $row) {
            self::upsertCareer($row, $report['career']);
        }
        foreach (self::eventRows() as $row) {
            self::upsertEvent($row, $report['event']);
        }
        foreach (self::newsRows() as $row) {
            self::upsertNews($row, $report['news']);
        }
        foreach (self::offerRows() as $row) {
            self::upsertOffer($row, $report['offer']);
        }

        return $report;
    }

    private static function ensureCoreTaxonomies(): void
    {
        // Offers / News taxonomies ship with only the default seeded term;
        // add a handful so the sidebar filters look real on staging.
        self::ensureTerms('culvers_offer_category', [
            'seasonal' => 'Seasonal',
            'food-drink' => 'Food & Drink',
            'fashion' => 'Fashion',
            'beauty' => 'Beauty',
            'family' => 'Family',
            'tech' => 'Technology',
        ]);
        self::ensureTerms('culvers_news_category', [
            'centre-news' => 'Centre news',
            'community' => 'Community',
            'awards' => 'Awards',
            'tenants' => 'New & returning tenants',
            'events' => 'Events',
            'sustainability' => 'Sustainability',
        ]);
    }

    /**
     * @param array<string, string> $pairs slug => name
     */
    private static function ensureTerms(string $taxonomy, array $pairs): void
    {
        foreach ($pairs as $slug => $name) {
            if (term_exists($slug, $taxonomy)) {
                continue;
            }
            wp_insert_term($name, $taxonomy, ['slug' => $slug]);
        }
    }

    /**
     * @return list<int>
     */
    private static function logoPool(): array
    {
        if (self::$logoPool !== null) {
            return self::$logoPool;
        }
        $ids = [];
        $shops = get_posts(['post_type' => 'culvers_shop', 'posts_per_page' => -1, 'fields' => 'ids']);
        foreach ($shops as $sid) {
            $logo = function_exists('get_field') ? get_field('shop_logo', $sid) : null;
            if (is_array($logo) && isset($logo['ID'])) {
                $ids[] = (int) $logo['ID'];
            }
        }
        self::$logoPool = array_values(array_unique($ids));

        return self::$logoPool;
    }

    /**
     * @return list<int>
     */
    private static function photoPool(): array
    {
        if (self::$photoPool !== null) {
            return self::$photoPool;
        }
        // Re-use the H&M storefront as a baseline; supplement with any large
        // landscape JPEGs from the media library (shop seeders may add more
        // later). Falling back to logo pool keeps the cards from looking
        // identical when the library is small.
        $ids = [];
        $shops = get_posts(['post_type' => 'culvers_shop', 'posts_per_page' => -1, 'fields' => 'ids']);
        foreach ($shops as $sid) {
            $tid = (int) get_post_thumbnail_id($sid);
            if ($tid > 0) {
                $ids[] = $tid;
            }
        }
        self::$photoPool = array_values(array_unique($ids));

        return self::$photoPool;
    }

    private static function pickLogo(int $index): int
    {
        $pool = self::logoPool();
        if ($pool === []) {
            return 0;
        }

        return $pool[$index % count($pool)];
    }

    private static function pickPhoto(int $index): int
    {
        $pool = self::photoPool();
        if ($pool === []) {
            // Cascade to logos so cards aren't all photo-less.
            return self::pickLogo($index);
        }

        return $pool[$index % count($pool)];
    }

    /**
     * @param array{title:string,slug:string,status?:string} $row
     * @param array{created:int,updated:int} $counter
     */
    private static function upsertPost(array $row, string $postType, array &$counter): int
    {
        $slug = $row['slug'];
        $existing = get_posts([
            'post_type' => $postType,
            'name' => $slug,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'suppress_filters' => true,
        ]);

        $postId = isset($existing[0]) ? (int) $existing[0] : 0;
        $payload = [
            'post_title' => $row['title'],
            'post_name' => $slug,
            'post_status' => $row['status'] ?? 'publish',
            'post_type' => $postType,
        ];

        if ($postId > 0) {
            $payload['ID'] = $postId;
            $res = wp_update_post($payload, true);
            $counter['updated']++;
        } else {
            $res = wp_insert_post($payload, true);
            $counter['created']++;
        }

        if (is_wp_error($res)) {
            return 0;
        }

        $postId = (int) $res;
        if (DirectoryFlexibleDefaults::layoutKeysForPostType($postType) !== []) {
            DirectoryFlexibleDefaults::persistDefaultsForPost($postId);
        }

        return $postId;
    }

    // ---------------------------------------------------------------------
    // Eat & Drink
    // ---------------------------------------------------------------------

    /**
     * @return list<array{title:string,slug:string,category:string,type:string,hours:string}>
     */
    private static function eatDrinkRows(): array
    {
        return [
            ['title' => 'Greggs Bakery', 'slug' => 'greggs', 'category' => 'bakery', 'type' => 'takeaway', 'hours' => EatDrinkDirectorySeedData::DEFAULT_HOURS_LINE],
            ['title' => 'Toast Coffee', 'slug' => 'toast-coffee', 'category' => 'coffee-cake', 'type' => 'cafe', 'hours' => EatDrinkDirectorySeedData::DEFAULT_HOURS_LINE],
            ['title' => 'Subway', 'slug' => 'subway', 'category' => 'burgers-grill', 'type' => 'takeaway', 'hours' => EatDrinkDirectorySeedData::DEFAULT_HOURS_LINE],
            [
                'title' => 'Juicy Bar Vitality',
                'slug' => 'juicy-bar-vitality',
                'category' => 'healthy',
                'type' => 'takeaway',
                'hours' => EatDrinkDirectorySeedData::DEFAULT_HOURS_LINE,
            ],
            [
                'title' => "Godfrey's Creperie",
                'slug' => 'godfreys-creperie',
                'category' => 'sweet-treats',
                'type' => 'restaurant',
                'hours' => EatDrinkDirectorySeedData::DEFAULT_HOURS_LINE,
            ],
        ];
    }

    /**
     * @param array{title:string,slug:string,category:string,type:string,hours:string} $row
     * @param array{created:int,updated:int} $counter
     */
    private static function upsertEatDrink(array $row, array &$counter): void
    {
        $postId = self::upsertPost($row, 'culvers_eat_drink', $counter);
        if ($postId <= 0) {
            return;
        }
        $index = $counter['created'] + $counter['updated'] - 1;

        self::acfSet('eat_drink_hours_summary', $row['hours'], $postId);
        $logoId = self::pickLogo($index);
        self::acfSet('eat_drink_logo', $logoId > 0 ? $logoId : false, $postId);

        $photoId = self::pickPhoto($index);
        if ($photoId > 0) {
            set_post_thumbnail($postId, $photoId);
        }

        self::setSingleTerm($postId, 'culvers_eat_drink_category', $row['category']);
        self::setSingleTerm($postId, 'culvers_eat_drink_type', $row['type']);
    }

    // ---------------------------------------------------------------------
    // Careers
    // ---------------------------------------------------------------------

    /**
     * @return list<array{title:string,slug:string,department:string,employment_type:string}>
     */
    private static function careerRows(): array
    {
        return [
            ['title' => 'Senior Supervisor — Subway', 'slug' => 'senior-supervisor', 'department' => 'in-store-roles', 'employment_type' => 'Full-time'],
            ['title' => 'Sales Associate — Pandora', 'slug' => 'sales-associate-pandora', 'department' => 'in-store-roles', 'employment_type' => 'Part-time'],
            ['title' => 'Centre Marketing Manager', 'slug' => 'centre-marketing-manager', 'department' => 'marketing-events', 'employment_type' => 'Full-time'],
            ['title' => 'Security Officer (Nights)', 'slug' => 'security-officer-nights', 'department' => 'security', 'employment_type' => 'Full-time'],
            ['title' => 'Customer Experience Host', 'slug' => 'customer-experience-host', 'department' => 'customer-experience', 'employment_type' => 'Part-time'],
            ['title' => 'Maintenance Technician', 'slug' => 'maintenance-technician', 'department' => 'operations-maintenance', 'employment_type' => 'Full-time'],
            ['title' => 'Cleaning Operative (Weekends)', 'slug' => 'cleaning-operative-weekends', 'department' => 'cleaning', 'employment_type' => 'Weekend'],
            ['title' => 'Events Coordinator', 'slug' => 'events-coordinator', 'department' => 'marketing-events', 'employment_type' => 'Contract'],
            ['title' => 'Centre Duty Manager', 'slug' => 'centre-duty-manager', 'department' => 'centre-management', 'employment_type' => 'Full-time'],
            ['title' => 'Barista — Caffè Nero', 'slug' => 'barista-caffe-nero', 'department' => 'in-store-roles', 'employment_type' => 'Part-time'],
        ];
    }

    /**
     * @param array{title:string,slug:string,department:string,employment_type:string} $row
     * @param array{created:int,updated:int} $counter
     */
    private static function upsertCareer(array $row, array &$counter): void
    {
        $postId = self::upsertPost($row, 'culvers_career', $counter);
        if ($postId <= 0) {
            return;
        }
        $index = $counter['created'] + $counter['updated'] - 1;

        self::acfSet('career_employment_type', $row['employment_type'], $postId);
        $logoId = self::pickLogo($index);
        self::acfSet('career_employer_logo', $logoId > 0 ? $logoId : false, $postId);

        self::setSingleTerm($postId, 'culvers_career_department', $row['department']);
    }

    // ---------------------------------------------------------------------
    // Events
    // ---------------------------------------------------------------------

    /**
     * @return list<array{title:string,slug:string,category:string,date:string,time:string,location:string}>
     */
    private static function eventRows(): array
    {
        $row = static fn (string $t, string $s, string $c, string $d, string $tm, string $l): array
            => ['title' => $t, 'slug' => $s, 'category' => $c, 'date' => $d, 'time' => $tm, 'location' => $l];

        return [
            $row('Culver Square Easter Egg Hunt', 'easter-egg-hunt', 'family', 'Sat 4 Apr', '10am - 2pm', 'Lower mall'),
            $row('Late-night Shopping Thursday', 'late-night-shopping-thursday', 'seasonal', 'Every Thu', 'until 8pm', 'Centre-wide'),
            $row("Mother's Day Bouquet Workshop", 'mothers-day-bouquet-workshop', 'workshop', 'Sat 14 Mar', '11am & 2pm', 'Upper concourse'),
            $row("Children's Storytime", 'childrens-storytime', 'family', 'Every Wed', '10.30am', 'Waterstones'),
            $row('Spring Wellness Week', 'spring-wellness-week', 'wellbeing', '6 – 13 Apr', 'All week', 'Centre-wide'),
            $row('Live Acoustic Sessions', 'live-acoustic-sessions', 'music', 'Fri 27 Mar', '6pm – 8pm', 'Atrium stage'),
            $row('Charity Bake Sale — Cancer Research', 'charity-bake-sale', 'community', 'Sat 21 Mar', '10am – 4pm', 'Lower mall'),
            $row('Meet the Easter Bunny', 'meet-the-easter-bunny', 'seasonal', '4 – 12 Apr', '11am – 4pm', 'Grotto'),
            $row('Father’s Day Workshop', 'fathers-day-workshop', 'workshop', 'Sat 20 Jun', '11am & 2pm', 'Upper concourse'),
            $row('Culver Square Christmas Lights Switch-on', 'christmas-lights-switch-on', 'seasonal', 'Sat 14 Nov', '5pm', 'Town Square'),
        ];
    }

    /**
     * @param array{title:string,slug:string,category:string,date:string,time:string,location:string} $row
     * @param array{created:int,updated:int} $counter
     */
    private static function upsertEvent(array $row, array &$counter): void
    {
        $postId = self::upsertPost($row, 'culvers_event', $counter);
        if ($postId <= 0) {
            return;
        }
        $index = $counter['created'] + $counter['updated'] - 1;

        self::acfSet('event_card_date', $row['date'], $postId);
        self::acfSet('event_card_time', $row['time'], $postId);
        self::acfSet('event_card_location', $row['location'], $postId);

        $photoId = self::pickPhoto($index);
        if ($photoId > 0) {
            set_post_thumbnail($postId, $photoId);
        }

        self::setSingleTerm($postId, 'culvers_event_category', $row['category']);
    }

    // ---------------------------------------------------------------------
    // News
    // ---------------------------------------------------------------------

    /**
     * @return list<array{title:string,slug:string,category:string,eyebrow:string,published:string}>
     */
    private static function newsRows(): array
    {
        $row = static fn (string $t, string $s, string $c, string $e, string $p): array
            => ['title' => $t, 'slug' => $s, 'category' => $c, 'eyebrow' => $e, 'published' => $p];

        return [
            $row('Spring 2026 line-up at Culver Square', 'spring-2026-lineup', 'centre-news', 'Centre news', '12 March 2026'),
            $row('Welcoming Søstrene Grene this April', 'welcoming-sostrene-grene', 'tenants', 'New tenant', '4 April 2026'),
            $row('Local college students take over Pandora window', 'pandora-window-takeover', 'community', 'Community', '28 February 2026'),
            $row('Culver Square named Essex Centre of the Year', 'essex-centre-of-the-year', 'awards', 'Award', '6 January 2026'),
            $row('Live music returns to the atrium', 'live-music-returns-atrium', 'events', 'What’s on', '20 March 2026'),
            $row('Recycling milestone — 80% diverted from landfill', 'recycling-milestone-80', 'sustainability', 'Sustainability', '14 February 2026'),
            $row('Refurbished centre map unveiled', 'refurbished-centre-map-unveiled', 'centre-news', 'Centre news', '22 January 2026'),
            $row('Wellbeing Wednesdays now permanent', 'wellbeing-wednesdays-permanent', 'events', 'What’s on', '10 January 2026'),
            $row('New solar array switched on', 'new-solar-array-switched-on', 'sustainability', 'Sustainability', '2 February 2026'),
            $row('Christmas trading hours announced', 'christmas-trading-hours-announced', 'centre-news', 'Centre news', '18 December 2025'),
        ];
    }

    /**
     * @param array{title:string,slug:string,category:string,eyebrow:string,published:string} $row
     * @param array{created:int,updated:int} $counter
     */
    private static function upsertNews(array $row, array &$counter): void
    {
        $postId = self::upsertPost($row, 'culvers_news', $counter);
        if ($postId <= 0) {
            return;
        }
        $index = $counter['created'] + $counter['updated'] - 1;

        self::acfSet('news_card_eyebrow', $row['eyebrow'], $postId);
        self::acfSet('news_card_published_on', $row['published'], $postId);

        $photoId = self::pickPhoto($index);
        if ($photoId > 0) {
            set_post_thumbnail($postId, $photoId);
        }

        self::setSingleTerm($postId, 'culvers_news_category', $row['category']);
    }

    // ---------------------------------------------------------------------
    // Offers
    // ---------------------------------------------------------------------

    /**
     * @return list<array{title:string,slug:string,category:string,validity:string,venue:string}>
     */
    private static function offerRows(): array
    {
        $row = static fn (string $t, string $s, string $c, string $v, string $vn): array
            => ['title' => $t, 'slug' => $s, 'category' => $c, 'validity' => $v, 'venue' => $vn];

        return [
            $row("Valentine's at Hotel Chocolat", 'valentines-at-hotel-chocolat', 'seasonal', 'Until 14 Feb', 'Hotel Chocolat'),
            $row("Mother's Day at The Body Shop", 'mothers-day-body-shop', 'seasonal', 'Until 14 Mar', 'The Body Shop'),
            $row('2-for-1 at Wagamama (weekdays)', 'wagamama-2-for-1', 'food-drink', 'Mon – Thu', 'Wagamama'),
            $row('20% off at Schuh this weekend', 'schuh-weekend-20', 'fashion', 'Sat & Sun', 'Schuh'),
            $row('Family Bowling — Kids Free', 'family-bowling-kids-free', 'family', 'Sun all day', 'Hollywood Bowl'),
            $row('Buy-one-get-one at Boots No7', 'boots-no7-bogo', 'beauty', 'This week', 'Boots'),
            $row('£10 off your first JD order', 'jd-10-off-first-order', 'fashion', 'New customers', 'JD Sports'),
            $row('Free coffee with breakfast at Pret', 'pret-free-coffee-breakfast', 'food-drink', 'Weekdays before 11am', 'Pret a Manger'),
            $row('Three trade-in event', 'three-trade-in-event', 'tech', 'This month', 'Three'),
            $row('Smiggle back-to-school bundle', 'smiggle-back-to-school-bundle', 'family', 'August', 'Smiggle'),
            $row('January Sale — up to 50% off', 'january-sale-up-to-50', 'seasonal', 'All January', 'Centre-wide'),
        ];
    }

    /**
     * @param array{title:string,slug:string,category:string,validity:string,venue:string} $row
     * @param array{created:int,updated:int} $counter
     */
    private static function upsertOffer(array $row, array &$counter): void
    {
        $postId = self::upsertPost($row, 'culvers_offer', $counter);
        if ($postId <= 0) {
            return;
        }
        $index = $counter['created'] + $counter['updated'] - 1;

        self::acfSet('offer_card_validity', $row['validity'], $postId);
        self::acfSet('offer_card_venue', $row['venue'], $postId);

        $photoId = self::pickPhoto($index);
        if ($photoId > 0) {
            set_post_thumbnail($postId, $photoId);
        }

        self::setSingleTerm($postId, 'culvers_offer_category', $row['category']);
    }

    /**
     * Thin wrapper so PHPStan stays happy on `update_field` (ACF declares it
     * at plugin runtime, not in this file's static analysis context).
     */
    private static function acfSet(string $field, mixed $value, int $postId): void
    {
        if (! function_exists('update_field')) {
            return;
        }
        update_field($field, $value, $postId);
    }

    private static function setSingleTerm(int $postId, string $taxonomy, string $termSlug): void
    {
        $term = get_term_by('slug', $termSlug, $taxonomy);
        if ($term instanceof \WP_Term) {
            wp_set_object_terms($postId, [(int) $term->term_id], $taxonomy, false);
        }
    }
}
