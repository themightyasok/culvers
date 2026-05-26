<?php

declare(strict_types=1);

namespace App\Directory\Cards;

use App\Directory\DirectoryCardImage;
use App\Directory\LogoPreserveColors;
use App\Directory\OpeningHoursCardLine;

/**
 * Per-CPT resolver for {@see DirectoryCardSpec}. The factory is the single
 * place CPT-specific ACF field names and taxonomy slugs live — the canonical
 * card partial only ever sees a flat spec and stays free of CPT branching.
 *
 * Every method mirrors the field/term lookups that the legacy partial it
 * replaces was performing (see git history of
 * `resources/views/partials/directory-{shop|eat-drink|event|offer|news|career}-card.blade.php`)
 * so the migration is a pure refactor with zero visual diff. Resolver
 * additions for new CPTs follow the same shape: one static factory method,
 * one `case` in {@see self::forPostType()}.
 */
final class DirectoryCardSpecFactory
{
    /**
     * Dispatch by post type. Returns `null` for post types that don't have
     * a directory card (so callers can emit a sensible fallback rather than
     * crashing on an unknown CPT).
     */
    public static function forPostType(int $postId, string $postType): ?DirectoryCardSpec
    {
        return match ($postType) {
            'culvers_shop' => self::forShop($postId),
            'culvers_eat_drink' => self::forEatDrink($postId),
            'culvers_career' => self::forCareer($postId),
            'culvers_event' => self::forEvent($postId),
            'culvers_offer' => self::forOffer($postId),
            'culvers_news' => self::forNews($postId),
            default => null,
        };
    }

    public static function forShop(int $postId): DirectoryCardSpec
    {
        $logoFromField = self::imageFieldUrl($postId, 'shop_logo');
        $hoverPhoto = self::featuredPhotoUrl($postId);
        /*
         * Figma MCP sideload historically stored SVG payloads with a `.jpg` extension — see
         * `scripts/fix-shop-jpg-svg-logos.php`. Prefer the authored logo, but reuse the featured storefront
         * so directory / related rows never ship an empty moss panel when the editor omits the logo field.
         */
        $logoUrl = $logoFromField !== '' ? $logoFromField : $hoverPhoto;
        $subtitle = self::cardHoursSubtitle(
            $postId,
            OpeningHoursCardLine::forPost($postId),
            self::stringField($postId, 'opening_hours_summary')
        );

        return new DirectoryCardSpec(
            postId: $postId,
            permalink: (string) get_permalink($postId),
            title: (string) get_the_title($postId),
            sortTitle: self::sortTitle($postId),
            hoverPhotoUrl: $hoverPhoto,
            logoUrl: $logoUrl,
            eyebrowText: (string) get_the_title($postId),
            subtitleText: $subtitle,
            categorySlugs: self::termSlugs($postId, 'culvers_shop_category'),
            typeSlugs: self::termSlugs($postId, 'culvers_shop_type'),
            invertLogoForMossTile: $logoFromField !== '' && ! LogoPreserveColors::shouldPreserveForPost($postId, 'culvers_shop'),
        );
    }

    public static function forEatDrink(int $postId): DirectoryCardSpec
    {
        $logoFromField = self::imageFieldUrl($postId, 'eat_drink_logo');
        $hoverPhoto = self::featuredPhotoUrl($postId);
        $logoUrl = $logoFromField !== '' ? $logoFromField : $hoverPhoto;
        $subtitle = self::cardHoursSubtitle(
            $postId,
            OpeningHoursCardLine::forPost($postId),
            self::stringField($postId, 'eat_drink_hours_summary')
        );

        return new DirectoryCardSpec(
            postId: $postId,
            permalink: (string) get_permalink($postId),
            title: (string) get_the_title($postId),
            sortTitle: self::sortTitle($postId),
            hoverPhotoUrl: $hoverPhoto,
            logoUrl: $logoUrl,
            eyebrowText: (string) get_the_title($postId),
            subtitleText: $subtitle,
            categorySlugs: self::termSlugs($postId, 'culvers_eat_drink_category'),
            typeSlugs: self::termSlugs($postId, 'culvers_eat_drink_type'),
            invertLogoForMossTile: $logoFromField !== '' && ! LogoPreserveColors::shouldPreserveForPost($postId, 'culvers_eat_drink'),
        );
    }

    public static function forCareer(int $postId): DirectoryCardSpec
    {
        $logoUrl = self::imageFieldUrl($postId, 'career_employer_logo');
        $employmentType = self::stringField($postId, 'career_employment_type');

        /* Employment type lives in a text field; slugify so the shared filter
           module's `data-type-slugs` matches the sidebar's slugified options. */
        $typeSlugs = $employmentType !== '' ? [sanitize_title($employmentType)] : [];

        return new DirectoryCardSpec(
            postId: $postId,
            permalink: (string) get_permalink($postId),
            title: (string) get_the_title($postId),
            sortTitle: self::sortTitle($postId),
            /* Career cards intentionally do NOT show a hover photo today —
               preserved here as empty string so the consolidated partial omits
               the overlay entirely (parity with the legacy partial). */
            hoverPhotoUrl: '',
            logoUrl: $logoUrl,
            eyebrowText: (string) get_the_title($postId),
            subtitleText: $employmentType,
            categorySlugs: self::termSlugs($postId, 'culvers_career_department'),
            typeSlugs: $typeSlugs,
            invertLogoForMossTile: $logoUrl !== '' && ! LogoPreserveColors::shouldPreserveForPost($postId, 'culvers_career'),
        );
    }

    public static function forEvent(int $postId): DirectoryCardSpec
    {
        $date = self::stringField($postId, 'event_card_date');
        $time = self::stringField($postId, 'event_card_time');
        $location = self::stringField($postId, 'event_card_location');

        /* Subtitle priority: date · time > date alone > time alone > location.
           Cards are tight so we surface the most date-anchored line first;
           venues usually live in the title context and are clear from the
           hero photo. */
        if ($date !== '' && $time !== '') {
            $subtitle = $date . ' · ' . $time;
        } elseif ($date !== '') {
            $subtitle = $date;
        } elseif ($time !== '') {
            $subtitle = $time;
        } else {
            $subtitle = $location;
        }

        [$primaryCategoryName, $categorySlugs] = self::primaryTermAndSlugs($postId, 'culvers_event_category');

        return new DirectoryCardSpec(
            postId: $postId,
            permalink: (string) get_permalink($postId),
            title: (string) get_the_title($postId),
            sortTitle: self::sortTitle($postId),
            hoverPhotoUrl: self::storyCardPhotoUrl($postId),
            /* Events have no brand logo — the slot shows the primary category
               name when present, falling back to the post title so a card is
               never blank. The canonical partial reads `eyebrowText` only when
               `logoUrl === ''`. */
            logoUrl: '',
            eyebrowText: $primaryCategoryName !== '' ? $primaryCategoryName : (string) get_the_title($postId),
            subtitleText: $subtitle,
            categorySlugs: $categorySlugs,
            typeSlugs: [],
        );
    }

    public static function forOffer(int $postId): DirectoryCardSpec
    {
        $validity = self::stringField($postId, 'offer_card_validity');
        $venue = self::stringField($postId, 'offer_card_venue');

        /* Venue trumps validity in the bottom band when both are set —
           shoppers find "where" more actionable than "when". Falls back to
           validity alone when no venue is supplied. */
        $subtitle = $venue !== '' ? $venue : $validity;

        [$primaryCategoryName, $categorySlugs] = self::primaryTermAndSlugs($postId, 'culvers_offer_category');

        return new DirectoryCardSpec(
            postId: $postId,
            permalink: (string) get_permalink($postId),
            title: (string) get_the_title($postId),
            sortTitle: self::sortTitle($postId),
            hoverPhotoUrl: self::storyCardPhotoUrl($postId),
            logoUrl: '',
            eyebrowText: $primaryCategoryName !== '' ? $primaryCategoryName : (string) get_the_title($postId),
            subtitleText: $subtitle,
            categorySlugs: $categorySlugs,
            typeSlugs: [],
        );
    }

    public static function forNews(int $postId): DirectoryCardSpec
    {
        $eyebrow = self::stringField($postId, 'news_card_eyebrow');
        [$primaryCategoryName, $categorySlugs] = self::primaryTermAndSlugs($postId, 'culvers_news_category');

        /* Eyebrow override → primary category → post type label fallback so
           the upper slot is never blank even on an uncategorised article. */
        if ($eyebrow === '') {
            $eyebrow = $primaryCategoryName !== '' ? $primaryCategoryName : __('News', 'culvers');
        }

        $publishOverride = self::stringField($postId, 'news_card_published_on');
        $subtitle = $publishOverride !== ''
            ? $publishOverride
            : (string) get_the_date('j F Y', $postId);

        return new DirectoryCardSpec(
            postId: $postId,
            permalink: (string) get_permalink($postId),
            title: (string) get_the_title($postId),
            sortTitle: self::sortTitle($postId),
            hoverPhotoUrl: self::storyCardPhotoUrl($postId),
            logoUrl: '',
            eyebrowText: $eyebrow,
            subtitleText: $subtitle,
            categorySlugs: $categorySlugs,
            typeSlugs: [],
        );
    }

    /* ------------------------------------------------------------------ *
     * Internal helpers — keep the per-CPT factories above readable.
     * ------------------------------------------------------------------ */

    private static function stringField(int $postId, string $fieldName): string
    {
        if (! function_exists('get_field')) {
            return '';
        }
        $value = get_field($fieldName, $postId);

        return is_string($value) ? trim($value) : '';
    }

    private static function imageFieldUrl(int $postId, string $fieldName): string
    {
        if (! function_exists('get_field')) {
            return '';
        }

        return self::acfImagePublicUrl(get_field($fieldName, $postId));
    }

    /**
     * ACF image fields may store an attachment ID, an array `{url,ID}`, or (legacy) `{url}` only.
     *
     * @param mixed $value Raw {@see get_field} return value.
     */
    private static function acfImagePublicUrl(mixed $value): string
    {
        if (is_numeric($value) && (int) $value > 0) {
            $u = wp_get_attachment_image_url((int) $value, 'full');

            return is_string($u) && $u !== '' ? $u : '';
        }

        if (! is_array($value)) {
            return '';
        }

        $maybeUrl = $value['url'] ?? null;
        if (is_string($maybeUrl) && trim($maybeUrl) !== '') {
            return trim($maybeUrl);
        }

        $id = (int) ($value['ID'] ?? $value['id'] ?? 0);
        if ($id > 0) {
            $u = wp_get_attachment_image_url($id, 'full');

            return is_string($u) && $u !== '' ? $u : '';
        }

        return '';
    }

    private static function featuredPhotoUrl(int $postId): string
    {
        $url = get_the_post_thumbnail_url($postId, 'large');

        return is_string($url) ? $url : '';
    }

    private static function storyCardPhotoUrl(int $postId): string
    {
        return DirectoryCardImage::resolve($postId)['url'];
    }

    private static function sortTitle(int $postId): string
    {
        return strtolower((string) get_the_title($postId));
    }

    private static function cardHoursSubtitle(int $postId, ?string $fromOpeningHours, string $summaryFallback): string
    {
        if ($fromOpeningHours !== null && $fromOpeningHours !== '') {
            return $fromOpeningHours;
        }

        if ($summaryFallback !== '') {
            return $summaryFallback;
        }

        return __('Opening hours TBC', 'culvers');
    }

    /**
     * @return list<string>
     */
    private static function termSlugs(int $postId, string $taxonomy): array
    {
        $slugs = wp_get_post_terms($postId, $taxonomy, ['fields' => 'slugs']);

        return is_array($slugs) ? array_values(array_map('strval', $slugs)) : [];
    }

    /**
     * Resolve the primary term name + every slug attached to `$taxonomy` in a
     * single WP query (vs the cards' historical pattern of two calls).
     *
     * `wp_get_post_terms()` without an explicit `fields` arg returns
     * `WP_Term[]|WP_Error` — once the `WP_Error` branch is filtered out we
     * know every entry is a `WP_Term`, so no redundant inner `instanceof`.
     *
     * @return array{0: string, 1: list<string>}
     */
    private static function primaryTermAndSlugs(int $postId, string $taxonomy): array
    {
        $terms = wp_get_post_terms($postId, $taxonomy);
        if (! is_array($terms)) {
            return ['', []];
        }

        $slugs = [];
        $primary = '';
        foreach ($terms as $term) {
            $slugs[] = (string) $term->slug;
            if ($primary === '') {
                $primary = (string) $term->name;
            }
        }

        return [$primary, $slugs];
    }
}
