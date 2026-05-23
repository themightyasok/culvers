<?php

/**
 * Updates the `shop_store_details` flexible row on every published `culvers_shop`
 * with researched phone, address, and Instagram (national brand accounts where no
 * local handle exists).
 *
 * From WordPress root (app/public):
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/shops-store-details-populate.php dry-run
 *
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/shops-store-details-populate.php
 *
 * @noinspection PhpUndefinedConstantInspection WP_CLI defined by WP-CLI
 */

if (! defined('WP_CLI') || ! WP_CLI) {
    exit(1);
}

if (! function_exists('update_field') || ! function_exists('get_field')) {
    \WP_CLI::error('ACF is required (get_field / update_field missing).');
}

$cliArgs = $args ?? [];
$dryRun = in_array('dry-run', $cliArgs, true)
    || in_array('--dry-run', $cliArgs, true);

$userId = (int) apply_filters('culvers_shops_store_details_populate_user_id', 1);
if ($userId > 0) {
    wp_set_current_user($userId);
}

/**
 * @return array<string, array{phone: string, address: string, instagram_url: string, instagram_handle: string, show_social: bool}>
 */
function culvers_shop_store_details_catalog(): array
{
    return [
        'accessorize-london' => [
            'phone' => '01206 542690',
            'address' => '10A–10B Culver Square, Colchester, Essex CO1 1WF',
            'instagram_url' => 'https://www.instagram.com/accessorize/',
            'instagram_handle' => '@accessorize',
            'show_social' => true,
        ],
        'ann-summers' => [
            'phone' => '01206 545395',
            'address' => '24 Culver Street West, Colchester, Essex CO1 1JG',
            'instagram_url' => 'https://www.instagram.com/annsummers/',
            'instagram_handle' => '@annsummers',
            'show_social' => true,
        ],
        'hm' => [
            'phone' => '01206 310010',
            'address' => '9–11 Culver Street West, Colchester, Essex CO1 1JQ',
            'instagram_url' => 'https://www.instagram.com/hm/',
            'instagram_handle' => '@hm',
            'show_social' => true,
        ],
        'hmv' => [
            'phone' => '0843 221 0156',
            'address' => '7 Culver Square, Colchester, Essex CO1 1WF',
            'instagram_url' => 'https://www.instagram.com/hmvinstagram/',
            'instagram_handle' => '@hmvinstagram',
            'show_social' => true,
        ],
        'schuh' => [
            'phone' => '01206 547927',
            'address' => 'Unit 14, Culver Square, Colchester, Essex CO1 1WF',
            'instagram_url' => 'https://www.instagram.com/schuh/',
            'instagram_handle' => '@schuh',
            'show_social' => true,
        ],
        'pandora' => [
            'phone' => '01206 573700',
            'address' => '13 Culver Square, Colchester, Essex CO1 1WG',
            'instagram_url' => 'https://www.instagram.com/theofficialpandora/',
            'instagram_handle' => '@theofficialpandora',
            'show_social' => true,
        ],
        'tk-maxx' => [
            'phone' => '01206 368745',
            'address' => '26 Sir Isaac\'s Walk, Culver Square, Colchester, Essex CO1 1JJ',
            'instagram_url' => 'https://www.instagram.com/tkmaxx/',
            'instagram_handle' => '@tkmaxx',
            'show_social' => true,
        ],
        'skechers' => [
            'phone' => '01206 355238',
            'address' => 'Unit 3, Culver Square, Colchester, Essex CO1 1WF',
            'instagram_url' => 'https://www.instagram.com/skechers/',
            'instagram_handle' => '@skechers',
            'show_social' => true,
        ],
        'smiggle' => [
            'phone' => '01206 545625',
            'address' => 'Unit 9, Culver Square, Colchester, Essex CO1 1JQ',
            'instagram_url' => 'https://www.instagram.com/smiggle_uk/',
            'instagram_handle' => '@smiggle_uk',
            'show_social' => true,
        ],
        'monsoon' => [
            'phone' => '01206 542690',
            'address' => '10A–10B Culver Square, Colchester, Essex CO1 1WF',
            'instagram_url' => 'https://www.instagram.com/monsoon/',
            'instagram_handle' => '@monsoon',
            'show_social' => true,
        ],
        'hotel-chocolat' => [
            'phone' => '01206 575462',
            'address' => 'Unit 21, Culver Street West, Colchester, Essex CO1 1JG',
            'instagram_url' => 'https://www.instagram.com/hotelchocolat/',
            'instagram_handle' => '@hotelchocolat',
            'show_social' => true,
        ],
        'the-fragrance-shop' => [
            'phone' => '01206 803811',
            'address' => '15 Culver Square, Colchester, Essex CO1 1WF',
            'instagram_url' => 'https://www.instagram.com/fragranceshopuk/',
            'instagram_handle' => '@fragranceshopuk',
            'show_social' => true,
        ],
        'flying-tiger-copenhagen' => [
            'phone' => '01206 563560',
            'address' => '22 Culver Street West, Culver Square, Colchester, Essex CO1 1JQ',
            'instagram_url' => 'https://www.instagram.com/flyingtigercopenhagen/',
            'instagram_handle' => '@flyingtigercopenhagen',
            'show_social' => true,
        ],
        'ernest-jones' => [
            'phone' => '01206 710595',
            'address' => '1 Shewell Walk, Culver Square, Colchester, Essex CO1 1WG',
            'instagram_url' => 'https://www.instagram.com/ernestjonesjewellers/',
            'instagram_handle' => '@ernestjonesjewellers',
            'show_social' => true,
        ],
        'istore' => [
            'phone' => '01206 581007',
            'address' => '7–8 Shewell Walk, Culver Square, Colchester, Essex CO1 1WG',
            'instagram_url' => 'https://www.instagram.com/istore/',
            'instagram_handle' => '@istore',
            'show_social' => true,
        ],
        'sostrene-grene' => [
            'phone' => '01206 489425',
            'address' => 'Unit 16, Culver Square, Colchester, Essex CO1 1WG',
            'instagram_url' => 'https://www.instagram.com/sostrenegrene/',
            'instagram_handle' => '@sostrenegrene',
            'show_social' => true,
        ],
        'cosmic-tattoo' => [
            'phone' => '01206 575158',
            'address' => '37 Sir Isaac\'s Walk, Culver Square, Colchester, Essex CO1 1JJ',
            'instagram_url' => '',
            'instagram_handle' => '',
            'show_social' => false,
        ],
        'love-reform' => [
            'phone' => '07790 683140',
            'address' => 'Culver Square Shopping Centre, Colchester, Essex CO1 1JQ',
            'instagram_url' => '',
            'instagram_handle' => '',
            'show_social' => false,
        ],
        'ecovape' => [
            'phone' => '07961 283598',
            'address' => '27 Sir Isaac\'s Walk, Culver Square, Colchester, Essex CO1 1JJ',
            'instagram_url' => 'https://www.instagram.com/ecovapeuk/',
            'instagram_handle' => '@ecovapeuk',
            'show_social' => true,
        ],
        'wye-mobility' => [
            'phone' => '',
            'address' => '18b Culver Walk, Culver Square, Colchester, Essex CO1 1JQ',
            'instagram_url' => '',
            'instagram_handle' => '',
            'show_social' => false,
        ],
        'menkind' => [
            'phone' => '01206 364371',
            'address' => '23 Culver Street West, Colchester, Essex CO1 1JG',
            'instagram_url' => 'https://www.instagram.com/menkind/',
            'instagram_handle' => '@menkind',
            'show_social' => true,
        ],
        'all-4-u-care' => [
            'phone' => '0330 043 7015',
            'address' => 'Colchester service area (home care — no retail unit in Culver Square)',
            'instagram_url' => 'https://www.instagram.com/all4ucare/',
            'instagram_handle' => '@all4ucare',
            'show_social' => true,
        ],
    ];
}

$catalog = culvers_shop_store_details_catalog();
$postIds = get_posts([
    'post_type' => 'culvers_shop',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'suppress_filters' => true,
]);

if (! is_array($postIds) || $postIds === []) {
    \WP_CLI::error('No published culvers_shop posts found.');
}

$updated = 0;
$skipped = 0;
$missingCatalog = [];
$missingLayout = [];

foreach ($postIds as $postId) {
    $postId = (int) $postId;
    $slug = (string) get_post_field('post_name', $postId);
    $title = (string) get_the_title($postId);

    if (! isset($catalog[$slug])) {
        $missingCatalog[] = $slug;
        continue;
    }

    $spec = $catalog[$slug];
    $rows = get_field('components', $postId);
    if (! is_array($rows) || $rows === []) {
        $missingLayout[] = sprintf('%s (#%d): no components', $slug, $postId);
        continue;
    }

    $rowIndex = null;
    foreach ($rows as $index => $row) {
        if (is_array($row) && ($row['acf_fc_layout'] ?? '') === 'shop_store_details') {
            $rowIndex = $index;
            break;
        }
    }

    if ($rowIndex === null) {
        $missingLayout[] = sprintf('%s (#%d): no shop_store_details row', $slug, $postId);
        continue;
    }

    $before = $rows[$rowIndex];
    $rows[$rowIndex]['details_heading'] = $before['details_heading'] ?? __('Store Details', 'culvers');
    $rows[$rowIndex]['details_contact_phone'] = $spec['phone'];
    $rows[$rowIndex]['details_address'] = $spec['address'];
    $rows[$rowIndex]['details_instagram_url'] = $spec['instagram_url'];
    $rows[$rowIndex]['details_instagram_handle'] = $spec['instagram_handle'];
    $rows[$rowIndex]['details_show_social_column'] = $spec['show_social'] ? 1 : 0;

    $unchanged = ($before['details_contact_phone'] ?? '') === $spec['phone']
        && ($before['details_address'] ?? '') === $spec['address']
        && ($before['details_instagram_url'] ?? '') === $spec['instagram_url']
        && ($before['details_instagram_handle'] ?? '') === $spec['instagram_handle']
        && (bool) (int) ($before['details_show_social_column'] ?? 1) === $spec['show_social'];

    if ($unchanged) {
        ++$skipped;
        \WP_CLI::log(sprintf('skip  %s — already current', $slug));
        continue;
    }

    if ($dryRun) {
        \WP_CLI::log(sprintf(
            '[dry-run] %s (#%d %s) phone=%s | social=%s',
            $slug,
            $postId,
            $title,
            $spec['phone'] !== '' ? $spec['phone'] : '(empty)',
            $spec['show_social'] ? $spec['instagram_handle'] : 'off'
        ));
        ++$updated;
        continue;
    }

    update_field('components', $rows, $postId);

    $saved = get_field('components', $postId);
    $savedRow = is_array($saved) ? ($saved[$rowIndex] ?? null) : null;
    $persisted = is_array($savedRow)
        && ($savedRow['details_contact_phone'] ?? '') === $spec['phone']
        && ($savedRow['details_address'] ?? '') === $spec['address']
        && ($savedRow['details_instagram_handle'] ?? '') === $spec['instagram_handle'];

    if (! $persisted) {
        \WP_CLI::warning(sprintf('Could not verify save for %s (#%d)', $slug, $postId));
        continue;
    }

    \WP_CLI::log(sprintf('ok    %s (#%d)', $slug, $postId));
    ++$updated;
}

if ($missingCatalog !== []) {
    \WP_CLI::warning('No catalog entry for slug(s): ' . implode(', ', $missingCatalog));
}

if ($missingLayout !== []) {
    foreach ($missingLayout as $line) {
        \WP_CLI::warning($line);
    }
}

$mode = $dryRun ? 'Would update' : 'Updated';
\WP_CLI::success(sprintf('%s %d shop(s); %d unchanged.', $mode, $updated, $skipped));
