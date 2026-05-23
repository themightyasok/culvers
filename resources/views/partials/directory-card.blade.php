@php
  use App\Directory\Cards\DirectoryCardSpec;
  use App\Directory\Cards\DirectoryCardSpecFactory;

  /**
   * Canonical directory tile — Figma "Shopping directory card" frame, reused
   * by every directory archive (shop / eat-drink / careers / latest-events /
   * latest-offers / latest-news) and any three-card / related-items block
   * that needs the same lockup.
   *
   * The partial is driven by a flat {@see DirectoryCardSpec} value object so
   * one Blade file owns the markup and per-CPT logic stays in
   * {@see DirectoryCardSpecFactory}. The legacy
   * `directory-{shop|eat-drink|event|offer|news|career}-card.blade.php`
   * partials are now thin shims that build a spec for the right CPT and
   * include this file — archive templates keep their existing include paths
   * unchanged so rewiring is a pure refactor.
   *
   * Inputs (one of):
   *   • `$directory_card_spec`     — pre-built {@see DirectoryCardSpec}.
   *                                  Preferred: avoids a second resolver hit
   *                                  when the calling shim already built one.
   *   • `$directory_card_post_id`  — integer post ID; the partial dispatches
   *                                  via the post type to the matching
   *                                  factory method. Falls back to the loop
   *                                  post via `get_the_ID()`.
   */
  $spec = $directory_card_spec ?? null;

  if (! ($spec instanceof DirectoryCardSpec)) {
      $resolved_post_id = isset($directory_card_post_id)
          ? (int) $directory_card_post_id
          : (int) get_the_ID();
      $resolved_post_type = (string) get_post_type($resolved_post_id);
      $spec = DirectoryCardSpecFactory::forPostType($resolved_post_id, $resolved_post_type);
  }
@endphp
@if ($spec instanceof DirectoryCardSpec)
  @php
    /*
     * Logo cards: moss + mark by default, storefront photo on hover (logo fades).
     * No-logo CPTs (events / offers / news): show the featured image by default —
     * there is no brand mark to preserve on the moss tile.
     */
    $showPhotoAlways = $spec->hasHoverPhoto() && ! $spec->hasLogoImage();
    $hoverPhotoOpacityClass = $showPhotoAlways
        ? 'opacity-100'
        : 'opacity-0 transition-opacity duration-300 ease-out motion-reduce:transition-none group-hover:opacity-100 group-focus-within:opacity-100';
    $overlayOpacityClass = $showPhotoAlways
        ? 'opacity-100'
        : 'opacity-0 transition-opacity duration-300 ease-out motion-reduce:transition-none group-hover:opacity-100 group-focus-within:opacity-100';
    /* Logo fade only when a mark hides the photo until hover. */
    $logoFadeClasses = ($spec->hasHoverPhoto() && $spec->hasLogoImage())
        ? 'transition-opacity duration-300 ease-out motion-reduce:transition-none group-hover:opacity-0 group-focus-within:opacity-0'
        : '';
  @endphp
  <article
    data-directory-card
    data-category-slugs="{{ esc_attr($spec->categorySlugsAttr()) }}"
    data-type-slugs="{{ esc_attr($spec->typeSlugsAttr()) }}"
    data-sort-title="{{ esc_attr($spec->sortTitle) }}"
    class="directory-shop-card min-w-0 w-full">
    <a
      href="{{ esc_url($spec->permalink) }}"
      class="group directory-shop-card__link relative block w-full max-w-none overflow-hidden rounded-[11px] outline-none culvers-focus-ring">
      {{--
        Moss tile: outer height is fixed at 294 px to keep grid tiles uniform on tablet +
        desktop (as shipped). Figma 51:8467 mobile shop card frame is 334 px tall (divider
        at y=242, title at y=265) so `max-sm:h-[334px]` only changes viewports under 640 px.
        The rule is not at a fixed y-position: the upper band is flex-1 (min-h-0) and
        shrinks so the lower band can grow upward when the title wraps; eyebrow/logo stays
        centred in the band that remains. Title + subtitle stack in normal flow (no absolute overlap).
      --}}
      <div class="relative flex h-[294px] w-full flex-col overflow-hidden bg-dustleaf max-sm:h-[334px]">
        @if ($spec->hasHoverPhoto())
          <img
            src="{{ esc_url($spec->hoverPhotoUrl) }}"
            alt=""
            class="directory-shop-card__hover-photo absolute inset-0 z-10 size-full rounded-[11px] object-cover {{ $hoverPhotoOpacityClass }}"
            loading="lazy"
            decoding="async" />
          <div
            class="directory-shop-card__photo-overlay pointer-events-none absolute inset-0 z-20 rounded-[11px] {{ $overlayOpacityClass }}"
            aria-hidden="true"></div>
        @endif

        @if ($spec->hasLogoImage())
          {{-- Brand marks on moss: monochrome invert unless the slot reuses the storefront photo. Repair SVG-served-as-.jpg via `scripts/fix-shop-jpg-svg-logos.php`. --}}
          <div
            class="directory-shop-card__logo-slot pointer-events-none relative z-30 flex min-h-0 flex-1 items-center justify-center px-8 {{ $logoFadeClasses }}">
            <img
              src="{{ esc_url($spec->logoUrl) }}"
              alt=""
              class="max-h-[120px] w-auto max-w-[85%] object-contain {{ $spec->invertLogoForMossTile ? 'brightness-0 invert' : '' }}"
              loading="lazy"
              decoding="async" />
          </div>
        @elseif(! $showPhotoAlways)
          {{-- Text eyebrow lockup: events / offers / news when no featured image. --}}
          <div
            class="directory-shop-card__logo-slot pointer-events-none relative z-30 flex min-h-0 flex-1 items-center justify-center px-6 {{ $logoFadeClasses }}">
            <span class="text-center font-heading text-2xl font-normal leading-tight text-white">{{ esc_html($spec->eyebrowText) }}</span>
          </div>
        @endif

        <div class="relative z-40 mt-auto w-full shrink-0">
          <div class="pointer-events-none h-px w-full bg-white" aria-hidden="true"></div>

          {{-- Bottom band height follows copy; upper band absorbs the remainder so the outer stays 294px. --}}
          <div class="flex shrink-0 flex-col gap-2 px-[23px] pb-5 pt-3">
            {{-- Figma directory card title: 22 px Halyard Display Medium, snapped to text-2xl (24 px).
                 `!font-sans` is important because the base layer `h1–h6 { font-heading }` rule
                 otherwise wins over a plain `font-sans` utility in Tailwind v4. --}}
            <h2 class="max-w-none !font-sans text-2xl font-medium leading-tight text-white">
              {{ $spec->title }}
            </h2>
            @if ($spec->hasSubtitle())
              <p class="max-w-none font-sans text-sm font-light text-white">
                {{ esc_html($spec->subtitleText) }}
              </p>
            @endif
          </div>
        </div>
      </div>
    </a>
  </article>
@endif
