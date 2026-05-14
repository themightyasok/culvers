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
    /* Logo fade is only meaningful when there's a hover photo to fade IN —
       otherwise we'd transition for nothing and force the GPU. Mirrors the
       legacy partials' `$logoFadeClasses` ternary exactly. */
    $logoFadeClasses = $spec->hasHoverPhoto()
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
      <div class="relative h-[294px] w-full bg-dustleaf">
        @if ($spec->hasHoverPhoto())
          <img
            src="{{ esc_url($spec->hoverPhotoUrl) }}"
            alt=""
            class="directory-shop-card__hover-photo absolute inset-0 z-10 size-full rounded-[11px] object-cover opacity-0 transition-opacity duration-300 ease-out motion-reduce:transition-none group-hover:opacity-100 group-focus-within:opacity-100"
            loading="lazy"
            decoding="async" />
          <div
            class="directory-shop-card__photo-overlay pointer-events-none absolute inset-0 z-20 rounded-[11px] opacity-0 transition-opacity duration-300 ease-out motion-reduce:transition-none group-hover:opacity-100 group-focus-within:opacity-100"
            aria-hidden="true"></div>
        @endif

        @if ($spec->hasLogoImage())
          {{-- Brand marks on moss: monochrome invert unless the slot reuses the storefront photo. Repair SVG-served-as-.jpg via `scripts/fix-shop-jpg-svg-logos.php`. --}}
          <div
            class="directory-shop-card__logo-slot pointer-events-none absolute inset-x-0 top-0 z-30 flex h-[213px] items-center justify-center px-8 {{ $logoFadeClasses }}">
            <img
              src="{{ esc_url($spec->logoUrl) }}"
              alt=""
              class="max-h-[120px] w-auto max-w-[85%] object-contain {{ $spec->invertLogoForMossTile ? 'brightness-0 invert' : '' }}"
              loading="lazy"
              decoding="async" />
          </div>
        @else
          {{-- Text eyebrow lockup: events / offers / news / "no-logo" shops. --}}
          <div
            class="directory-shop-card__logo-slot pointer-events-none absolute inset-x-0 top-0 z-30 flex h-[213px] items-center justify-center px-6 {{ $logoFadeClasses }}">
            <span class="text-center font-heading text-2xl font-normal text-white">{{ esc_html($spec->eyebrowText) }}</span>
          </div>
        @endif

        <div class="pointer-events-none absolute left-0 right-0 top-[213px] z-40 h-px bg-white" aria-hidden="true"></div>

        {{-- Figma directory card title: 22 px Halyard Display Medium, snapped to text-2xl (24 px).
             `!font-sans` is important because the base layer `h1–h6 { font-heading }` rule
             otherwise wins over a plain `font-sans` utility in Tailwind v4. --}}
        <h2 class="absolute left-[23px] top-[233px] z-40 max-w-[calc(100%-46px)] !font-sans text-2xl font-medium leading-tight text-white">
          {{ $spec->title }}
        </h2>
        @if ($spec->hasSubtitle())
          <p class="absolute left-[23px] top-[263px] z-40 max-w-[calc(100%-46px)] font-sans text-sm font-light text-white">
            {{ esc_html($spec->subtitleText) }}
          </p>
        @endif
      </div>
    </a>
  </article>
@endif
