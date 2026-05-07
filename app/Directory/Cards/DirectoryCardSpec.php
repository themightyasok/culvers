<?php

declare(strict_types=1);

namespace App\Directory\Cards;

/**
 * Flat presentation contract for a single directory tile (Shop / Eat & Drink /
 * Career / Event / Offer / News). One canonical Blade partial
 * ({@see resources/views/partials/directory-card.blade.php}) consumes this and
 * renders the 294px moss tile every directory archive shares — the per-CPT
 * resolvers in {@see DirectoryCardSpecFactory} are the only place CPT-specific
 * field names live.
 *
 * Field semantics (visual mapping into the tile):
 *
 *   • `hoverPhotoUrl` — large featured-image URL shown on hover/focus.
 *     Empty string means "no hover photo" (career cards behave this way today;
 *     the partial skips the overlay markup entirely so reduced-motion targets
 *     never apply).
 *
 *   • `logoUrl` — image logo for the upper logo slot. Rendered as
 *     white (brightness-0 invert) on the moss tile. Empty string means "use
 *     `eyebrowText` as a centred lockup instead" — events / offers / news use
 *     this to surface the primary category as a textual eyebrow.
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
