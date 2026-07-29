<?php

/**
 * Contact after-submit Lost Property note + Guest Services accordion updates.
 *
 * Usage (from app/public):
 *   ./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
 *     wp-content/themes/culvers/scripts/lost-property-management-office-2026-07.php
 *
 *   ssh 20i-culvers 'cd public_html && php83 /usr/local/bin/wp eval-file \
 *     wp-content/themes/culvers/scripts/lost-property-management-office-2026-07.php'
 */

if (! function_exists('get_field') || ! function_exists('update_field')) {
    fwrite(STDERR, "ACF required.\n");
    exit(1);
}

$lostPropertyVisit = 'If you have lost an item while visiting the shopping centre, please visit the Management Office to enquire about lost property.';

$managementHoursBody = '<p><strong>Management Office opening hours:</strong></p>'
    . '<p>Monday to Friday<br>9:00am – 4:30pm</p>'
    . '<p>If you are enquiring outside of these hours, please contact our Control Room on '
    . '<a href="tel:01206487469">01206 487469</a>. If there is no answer leave a message or email '
    . '<a href="mailto:info@culversquare.co.uk">info@culversquare.co.uk</a>, and a member of the team '
    . 'will get back to you as soon as possible.</p>';

$contactAfterSubmit = '<h3>Lost Property</h3>'
    . '<p>' . esc_html($lostPropertyVisit) . '</p>'
    . $managementHoursBody;

$contactId = (int) (
    get_page_by_path('contact')?->ID
    ?? get_page_by_path('contact-us')?->ID
    ?? 0
);
if ($contactId <= 0) {
    $found = get_posts([
        'post_type' => 'page',
        'name' => 'contact',
        'post_status' => 'any',
        'numberposts' => 1,
        'fields' => 'ids',
    ]);
    $contactId = (int) ($found[0] ?? 0);
}

$guestId = (int) (
    get_page_by_path('guest-services')?->ID
    ?? 0
);

if ($contactId <= 0) {
    fwrite(STDERR, "Contact page not found.\n");
    exit(1);
}
if ($guestId <= 0) {
    fwrite(STDERR, "Guest services page not found.\n");
    exit(1);
}

// --- Contact: after-submit copy on the contact layout ---
$contactComponents = get_field('components', $contactId);
if (! is_array($contactComponents)) {
    fwrite(STDERR, "Contact page has no components.\n");
    exit(1);
}

$contactUpdated = false;
foreach ($contactComponents as $i => $row) {
    if (($row['acf_fc_layout'] ?? '') !== 'contact') {
        continue;
    }
    $contactComponents[$i]['contact_after_submit'] = $contactAfterSubmit;
    $contactUpdated = true;
    break;
}
if (! $contactUpdated) {
    fwrite(STDERR, "No contact layout on Contact page.\n");
    exit(1);
}
update_field('components', $contactComponents, $contactId);
WP_CLI::log("Updated contact_after_submit on page #{$contactId}");

// --- Guest services: Lost Property body + Management Office row ---
$guestComponents = get_field('components', $guestId);
if (! is_array($guestComponents)) {
    fwrite(STDERR, "Guest services has no components.\n");
    exit(1);
}

$guestUpdated = false;
foreach ($guestComponents as $i => $row) {
    if (($row['acf_fc_layout'] ?? '') !== 'text_image_slider') {
        continue;
    }
    $items = $row['tis_items'] ?? [];
    if (! is_array($items)) {
        fwrite(STDERR, "tis_items missing.\n");
        exit(1);
    }

    $lostIndex = null;
    $hasManagement = false;
    foreach ($items as $j => $item) {
        $label = trim((string) ($item['item_label'] ?? ''));
        if (strcasecmp($label, 'Lost Property') === 0) {
            $lostIndex = $j;
        }
        if (strcasecmp($label, 'Management Office') === 0) {
            $hasManagement = true;
        }
    }
    if ($lostIndex === null) {
        fwrite(STDERR, "Lost Property row not found.\n");
        exit(1);
    }

    $existingBody = (string) ($items[$lostIndex]['item_body'] ?? '');
    if (stripos(wp_strip_all_tags($existingBody), 'visit the Management Office') === false) {
        $items[$lostIndex]['item_body'] = '<p>' . esc_html($lostPropertyVisit) . '</p>' . $existingBody;
    }

    if (! $hasManagement) {
        $clone = $items[$lostIndex];
        $imageId = static function ($value) {
            if (is_numeric($value)) {
                return (int) $value;
            }
            if (is_array($value)) {
                return (int) ($value['ID'] ?? $value['id'] ?? 0);
            }

            return 0;
        };
        $managementRow = [
            'item_label' => 'Management Office',
            'item_body' => $managementHoursBody,
            'item_cta_label' => '',
            'item_cta_url' => '',
            'item_cta_new_tab' => 0,
            'item_image_left' => $imageId($clone['item_image_left'] ?? null) ?: null,
            'item_image_right' => $imageId($clone['item_image_right'] ?? null) ?: null,
            'item_image_left_tilt' => $clone['item_image_left_tilt'] ?? 7,
            'item_image_right_tilt' => $clone['item_image_right_tilt'] ?? -6,
        ];
        array_splice($items, $lostIndex + 1, 0, [$managementRow]);
        WP_CLI::log('Inserted Management Office accordion after Lost Property');
    } else {
        foreach ($items as $j => $item) {
            if (strcasecmp(trim((string) ($item['item_label'] ?? '')), 'Management Office') === 0) {
                $items[$j]['item_body'] = $managementHoursBody;
            }
        }
        WP_CLI::log('Updated existing Management Office accordion body');
    }

    $guestComponents[$i]['tis_items'] = $items;
    $guestUpdated = true;
    break;
}

if (! $guestUpdated) {
    fwrite(STDERR, "No text_image_slider on Guest services.\n");
    exit(1);
}

update_field('components', $guestComponents, $guestId);
WP_CLI::log("Updated Guest services accordion on page #{$guestId}");
WP_CLI::success('Lost Property / Management Office content applied.');
