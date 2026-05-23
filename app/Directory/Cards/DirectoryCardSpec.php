<?php

declare(strict_types=1);

namespace App\Directory\Cards;

/**
 * Flat presentation contract for a single directory tile (Shop / Eat & Drink /
 * Career / Event / Offer / News). One canonical Blade partial
 * ({@see resources/views/partials/directory-card.blade.php}) consumes this and
 * renders the fixed 294px moss tile used on directory archives: the upper band and
 * title/subtitle band split available height internally (rule moves up when copy wraps).
 * The per-CPT resolvers in {@see DirectoryCardSpecFactory} are the only place CPT-specific
 * field names live.
 *
 * Field semantics (visual mapping into the tile):
 *
   *   • `hoverPhotoUrl` — large featured-image URL. On logo cards it appears on
   *     hover/focus; on no-logo CPTs (events / offers / news) it is shown by
   *     default because there is no brand mark on the moss tile.
 *
 *   • `logoUrl` — image for the upper logo slot; may duplicate
 *     `hoverPhotoUrl` when the factory falls back to the featured image.
 *     When {@see $invertLogoForMossTile} is true, the partial applies
 *     brightness/invert so raster marks read as white on moss. Empty string
 *     means "use `eyebrowText` as a centred lockup instead" — events / offers /
 *     news use this to surface the primary category as a textual eyebrow.
 *
 *   • `eyebrowText` — text fallback used when `logoUrl === ''`. Always
 *     populated with at least the post title so a card never goes blank.
 *
 *   • `subtitleText` — small line under the title (opening hours / date+time /
 *     publish date / employment type). Empty string suppresses the `<p>`
 *     entirely — matches the per-CPT conditional behaviour of the legacy
 *     partials.
 *
 *   • `categorySlugs` / `typeSlugs` — drive the shared `directoryArchive`
 *     Alpine module's `data-category-slugs` / `data-type-slugs` attributes.
 *     CPTs without a secondary filter (event / offer / news) emit an empty
 *     `typeSlugs` array, which the partial renders as `data-type-slugs=""`.
 *     The legacy event / offer / news partials skipped the attribute entirely
 *     — the Alpine module short-circuits identically on both an absent and
 *     an empty value (`(card.dataset.typeSlugs || '').split(',').filter(Boolean)`
 *     yields `[]` either way), so emitting it always is functionally
 *     equivalent and keeps the canonical partial branch-free.
 *
 * BEM root: every card uses the `directory-shop-card` class for its CSS
 * hooks, including career cards (which used `directory-career-card` in the
 * legacy career partial). No CSS rule or JS module targets the legacy
 * `directory-career-card*` namespace — the Alpine filter module hooks on
 * `[data-directory-card]` and the gradient + reduced-motion CSS rules
 * target `directory-shop-card__*` only — so the unification is invisible
 * to users while collapsing two near-identical class hierarchies into one.
 */
final class DirectoryCardSpec
{
    /**
     * @param list<string> $categorySlugs
     * @param list<string> $typeSlugs
     */
    public function __construct(
        public readonly int $postId,
        public readonly string $permalink,
        public readonly string $title,
        public readonly string $sortTitle,
        public readonly string $hoverPhotoUrl,
        public readonly string $logoUrl,
        public readonly string $eyebrowText,
        public readonly string $subtitleText,
        public readonly array $categorySlugs,
        public readonly array $typeSlugs,
        /**
         * When true, moss-tile logos use brightness/invert Tailwind utilities so raster marks read as white.
         * Disable when {@see $logoUrl} duplicates {@see $hoverPhotoUrl} (featured image reused in both slots).
         */
        public readonly bool $invertLogoForMossTile = false,
    ) {
    }

    public function hasHoverPhoto(): bool
    {
        return $this->hoverPhotoUrl !== '';
    }

    public function hasLogoImage(): bool
    {
        return $this->logoUrl !== '';
    }

    public function hasSubtitle(): bool
    {
        return $this->subtitleText !== '';
    }

    public function categorySlugsAttr(): string
    {
        return implode(',', $this->categorySlugs);
    }

    public function typeSlugsAttr(): string
    {
        return implode(',', $this->typeSlugs);
    }
}
