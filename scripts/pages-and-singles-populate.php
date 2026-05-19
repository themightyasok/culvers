<?php

/**
 * Creates the standard top-level pages and one representative single per
 * non-shop directory CPT, then writes their canonical flexible-content stack
 * into the database.
 *
 * Same pattern as the existing homepage / shop-single populate scripts:
 *
 *   wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *       wp-content/themes/culvers/scripts/pages-and-singles-populate.php
 *
 * Pages built:
 *   - /plan-my-visit/         (Figma 51:5872)
 *   - /contact/               (Figma 51:9353)
 *   - /guest-services/        (Figma 51:8033)
 *   - /leasing-opportunities/ (Figma 51:6500)
 *
 * Singles built (one per CPT — like Accessorize is for shops):
 *   - culvers_career    → senior-supervisor                (Figma 51:6450)
 *   - culvers_event     → valentines-at-hotel-chocolat     (Figma 51:6386)
 *   - culvers_eat_drink → greggs                           (Figma 51:6679)
 *
 * Re-running the script is idempotent — existing entries are updated in
 * place rather than duplicated. Image URLs are sideloaded into the media
 * library on first run via {@see HomepageFlexibleAcfAttach}.
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

use App\Helpers\CptSinglesFlexibleSeedData;
use App\Helpers\HomepageFlexibleAcfAttach;
use App\Helpers\PagesFlexibleSeedData;

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

if (! function_exists('update_field')) {
    \WP_CLI::error('ACF is required (update_field missing).');
}

$userId = (int) apply_filters('culvers_pages_populate_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

/**
 * Find or create a page by slug; returns its ID or 0 on failure.
 */
$ensurePage = static function (string $slug, string $title): int {
    $existing = get_page_by_path($slug, OBJECT, 'page');
    if ($existing instanceof \WP_Post) {
        return (int) $existing->ID;
    }
    $insert = wp_insert_post(
        [
            'post_title' => $title,
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '',
        ],
        true
    );
    if (is_wp_error($insert)) {
        \WP_CLI::warning(sprintf('Failed to create page "%s": %s', $slug, $insert->get_error_message()));

        return 0;
    }

    return (int) $insert;
};

/**
 * Find or create a CPT post by slug; returns its ID or 0 on failure.
 *
 * @param array<string, list<string>> $taxonomies tax => term names to set on the post
 */
$ensureCptPost = static function (
    string $postType,
    string $slug,
    string $title,
    array $taxonomies = []
): int {
    $existing = get_posts([
        'post_type' => $postType,
        'name' => $slug,
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => 1,
        'fields' => 'ids',
        'suppress_filters' => true,
    ]);

    $postId = (is_array($existing) && isset($existing[0])) ? (int) $existing[0] : 0;

    if ($postId === 0) {
        $insert = wp_insert_post(
            [
                'post_title' => $title,
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_type' => $postType,
                'post_content' => '',
            ],
            true
        );
        if (is_wp_error($insert)) {
            \WP_CLI::warning(sprintf('Failed to create %s "%s": %s', $postType, $slug, $insert->get_error_message()));

            return 0;
        }
        $postId = (int) $insert;
    } elseif (get_post_status($postId) !== 'publish') {
        wp_update_post(['ID' => $postId, 'post_status' => 'publish']);
    }

    foreach ($taxonomies as $taxonomy => $termNames) {
        if (! is_array($termNames) || $termNames === []) {
            continue;
        }
        wp_set_object_terms($postId, $termNames, $taxonomy, false);
    }

    return $postId;
};

$savedPages = 0;
$savedSingles = 0;

/* -----------------------------------------------------------------
 * Pages
 * --------------------------------------------------------------- */

$pageDefinitions = [
    [
        'slug' => 'plan-my-visit',
        'title' => __('Plan my visit', 'culvers'),
        'rows' => PagesFlexibleSeedData::planMyVisitPage(),
    ],
    [
        'slug' => 'contact',
        'title' => __('Contact us', 'culvers'),
        'rows' => PagesFlexibleSeedData::contactPage(),
    ],
    [
        'slug' => 'guest-services',
        'title' => __('Guest services', 'culvers'),
        'rows' => PagesFlexibleSeedData::guestServicesPage(),
    ],
    [
        'slug' => 'leasing-opportunities',
        'title' => __('Leasing opportunities', 'culvers'),
        'rows' => PagesFlexibleSeedData::leasingPage(),
    ],
    [
        /*
         * What's On landing page — curates the three top-level sibling
         * archives (/latest-events/, /latest-offers/, /latest-news/).
         * `image_hero` + three CPT-driven `three_card_block` strips +
         * opening_hours, per the Figma What's On landing spec.
         */
        'slug' => 'whats-on',
        'title' => __('What’s on', 'culvers'),
        'rows' => PagesFlexibleSeedData::whatsOnLandingPage(),
    ],
];

foreach ($pageDefinitions as $page) {
    $postId = $ensurePage($page['slug'], $page['title']);
    if ($postId <= 0) {
        continue;
    }

    $rows = HomepageFlexibleAcfAttach::attachFlexibleRows($page['rows']);
    $result = update_field('components', $rows, $postId);
    if ($result === false) {
        \WP_CLI::warning(
            sprintf('update_field returned false for page "%s" (ID %d).', $page['slug'], $postId)
        );
    }
    \WP_CLI::log(
        sprintf(
            'Page populated: %-22s ID %d (%d layouts)',
            $page['slug'],
            $postId,
            count($rows)
        )
    );
    $savedPages++;
}

/* -----------------------------------------------------------------
 * Singles — one per non-shop directory CPT
 * --------------------------------------------------------------- */

$singleDefinitions = [
    [
        'post_type' => 'culvers_career',
        'slug' => 'senior-supervisor',
        'title' => __('Senior Supervisor — Subway', 'culvers'),
        'taxonomies' => [
            'culvers_career_department' => [__('In-store roles', 'culvers')],
        ],
        'meta' => [
            'career_employment_type' => __('Full time', 'culvers'),
            'career_location' => __('Culver Square, Colchester', 'culvers'),
            'career_employer' => __('Subway', 'culvers'),
        ],
        'rows' => CptSinglesFlexibleSeedData::seniorSupervisor(),
    ],
    [
        /* Representative `culvers_event` — Easter Egg Hunt is the standout
           "Latest Events" tile in the Figma What's On landing three-card. */
        'post_type' => 'culvers_event',
        'slug' => 'easter-egg-hunt',
        'title' => __('Culver Square Easter Egg Hunt', 'culvers'),
        'taxonomies' => [
            'culvers_event_category' => [__('Family', 'culvers')],
        ],
        'meta' => [
            'event_card_date' => __('Sat 4 – Mon 6 April 2026', 'culvers'),
            'event_card_time' => __('10am – 4pm', 'culvers'),
            'event_card_location' => __('Lower Mall, Culver Square', 'culvers'),
        ],
        'rows' => CptSinglesFlexibleSeedData::easterEggHunt(),
    ],
    [
        /* Representative `culvers_offer` — moved from `culvers_event` once
           Offers became their own CPT. The Figma "Latest Offers" grid
           includes Valentine's at Hotel Chocolat as a brand promotion tile. */
        'post_type' => 'culvers_offer',
        'slug' => 'valentines-at-hotel-chocolat',
        'title' => __('Valentine’s at Hotel Chocolat', 'culvers'),
        'taxonomies' => [
            'culvers_offer_category' => [__('Seasonal', 'culvers')],
        ],
        'meta' => [
            'offer_card_validity' => __('1 May – 30 June 2026', 'culvers'),
            'offer_card_venue' => __('Hotel Chocolat, Lower Level', 'culvers'),
        ],
        'rows' => CptSinglesFlexibleSeedData::hotelChocolatOffer(),
    ],
    [
        /* Representative `culvers_news` — Spring 2026 line-up so the new
           news archive ships with at least one editorial article visible. */
        'post_type' => 'culvers_news',
        'slug' => 'spring-2026-lineup',
        'title' => __('Spring 2026 line-up at Culver Square', 'culvers'),
        'taxonomies' => [
            'culvers_news_category' => [__('Centre news', 'culvers')],
        ],
        'meta' => [
            'news_card_eyebrow' => __('Centre news', 'culvers'),
            'news_card_published_on' => __('1 March 2026', 'culvers'),
        ],
        'rows' => CptSinglesFlexibleSeedData::spring2026Lineup(),
    ],
    [
        'post_type' => 'culvers_eat_drink',
        'slug' => 'greggs',
        'title' => __('Greggs Bakery', 'culvers'),
        'taxonomies' => [
            'culvers_eat_drink_type' => [__('Grab & Go', 'culvers')],
        ],
        'meta' => [
            'eat_drink_hours_summary' => __('Open Today 9am – 5.30pm', 'culvers'),
        ],
        'rows' => CptSinglesFlexibleSeedData::greggs(),
    ],
];

foreach ($singleDefinitions as $single) {
    $postId = $ensureCptPost(
        $single['post_type'],
        $single['slug'],
        $single['title'],
        $single['taxonomies']
    );
    if ($postId <= 0) {
        continue;
    }

    foreach ($single['meta'] as $metaKey => $metaValue) {
        update_field($metaKey, $metaValue, $postId);
    }

    $rows = HomepageFlexibleAcfAttach::attachFlexibleRows($single['rows']);
    $result = update_field('components', $rows, $postId);
    if ($result === false) {
        \WP_CLI::warning(
            sprintf(
                'update_field returned false for %s "%s" (ID %d).',
                $single['post_type'],
                $single['slug'],
                $postId
            )
        );
    }
    \WP_CLI::log(
        sprintf(
            'Single populated: %-18s %-32s ID %d (%d layouts)',
            $single['post_type'],
            $single['slug'],
            $postId,
            count($rows)
        )
    );
    $savedSingles++;
}

\WP_CLI::success(
    sprintf(
        'Populated %d page%s and %d directory single%s.',
        $savedPages,
        $savedPages === 1 ? '' : 's',
        $savedSingles,
        $savedSingles === 1 ? '' : 's'
    )
);
