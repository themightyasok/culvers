@php
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;

  /**
   * Centre map — Figma developer release (Greggs page 51:6808 + adjacent boards).
   * Dark deep-moss band with two columns:
   *   • LEFT panel: heading + filter-toggle pill + collapsible category groups
   *     (Shop / Eat & Drink / Guest Services). Categories are grouped on render
   *     by their `category_group` field so editors can keep one flat repeater.
   *   • RIGHT map: stylised centre map graphic with the optional zoom-control
   *     pill stack overlaid in the bottom-right corner.
   *
   * Map is rendered as a flat image — no interactive pins. The historic
   * `centre_map_pins` repeater is preserved on the ACF schema for backwards
   * compatibility with any existing post meta but is intentionally not painted
   * here (Figma references show no pins on the centre map).
   *
   * Filter-toggle and zoom controls are live (Alpine) — see the `centreMap`
   * Alpine module in `resources/scripts/alpine/centre-map.js`.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $eyebrow = trim((string) ($c['centre_map_eyebrow'] ?? ''));
  $heading = trim((string) ($c['centre_map_heading'] ?? ''));
  $headingTag = Component::headingTag($c['centre_map_heading_level'] ?? 2);

  $bodyRaw = trim((string) ($c['centre_map_body'] ?? ''));
  $bodyWithBreaks = preg_replace('/<br\s*\/?>/i', "\n", $bodyRaw);
  $bodyLines = [];
  if ($bodyWithBreaks !== '') {
      $bodyLines = array_values(array_filter(array_map(static function (string $line): string {
          return trim($line);
      }, preg_split('/\r\n|\r|\n/', wp_strip_all_tags($bodyWithBreaks)))));
  }

  $image = is_array($c['centre_map_image'] ?? null) ? $c['centre_map_image'] : null;
  $imageUrl = is_array($image) && is_string($image['url'] ?? null) ? (string) $image['url'] : '';
  $imageAlt = is_array($image) && is_string($image['alt'] ?? null) ? (string) $image['alt'] : '';
  if ($imageAlt === '') {
      $imageAlt = __('Centre floor plan', 'culvers');
  }

  $panelPosition = ($c['centre_map_panel_position'] ?? 'left') === 'right' ? 'right' : 'left';
  $filterButtonLabel = trim((string) ($c['centre_map_filter_button_label'] ?? ''));
  if ($filterButtonLabel === '') {
      $filterButtonLabel = __('Hide filter', 'culvers');
  }
  /** Show-state label uses the same string with “Hide” swapped for “Show” when present. */
  $filterButtonShowLabel = stripos($filterButtonLabel, 'hide') !== false
      ? str_ireplace('hide', __('Show', 'culvers'), $filterButtonLabel)
      : __('Show filter', 'culvers');

  $showZoom = ! array_key_exists('centre_map_show_zoom_controls', $c)
      || ! empty($c['centre_map_show_zoom_controls']);

  /**
   * Fold the flat ACF repeater into ordered groups so editors keep one list
   * but the panel renders one accordion per `category_group` value.
   * Categories with no group fall under a single "Categories" bucket.
   *
   * @var array<int, array{label: string, slug: string, items: list<array{label: string, slug: string, url: string}>}> $groups
   */
  $rawCategories = is_array($c['centre_map_categories'] ?? null) ? $c['centre_map_categories'] : [];
  $groupOrder = [];
  $groupBuckets = [];
  foreach ($rawCategories as $row) {
      if (! is_array($row)) {
          continue;
      }
      $label = trim((string) ($row['category_label'] ?? ''));
      $slug = sanitize_title((string) ($row['category_slug'] ?? ''));
      if ($label === '' || $slug === '') {
          continue;
      }
      $groupLabel = trim((string) ($row['category_group'] ?? ''));
      $groupKey = $groupLabel !== '' ? sanitize_title($groupLabel) : '_default';
      if (! isset($groupBuckets[$groupKey])) {
          $groupBuckets[$groupKey] = [
              'label' => $groupLabel !== '' ? $groupLabel : __('Categories', 'culvers'),
              'slug' => $groupKey,
              'items' => [],
          ];
          $groupOrder[] = $groupKey;
      }
      $groupBuckets[$groupKey]['items'][] = [
          'label' => $label,
          'slug' => $slug,
          'url' => trim((string) ($row['category_url'] ?? '')),
      ];
  }
  $groups = [];
  foreach ($groupOrder as $key) {
      $groups[] = $groupBuckets[$key];
  }

  /*
   * Map-only mode: when the author has supplied only an image (no heading,
   * no categories), render a single full-width map and skip the filter
   * panel entirely. Mirrors the Figma Contact page where the centre map
   * sits as a 1401×570 graphic between the form and the opening-hours
   * block — no sidebar.
   */
  $isMapOnly = $heading === '' && $eyebrow === '' && $bodyLines === [] && $groups === [];

  /* With the Google Maps embed fallback in place, an empty centre_map block
     still renders a functional map — so always consider this component as
     having content unless we're explicitly suppressed. */
  $hasContent = $heading !== '' || $eyebrow !== '' || $bodyLines !== []
      || $imageUrl !== '' || $groups !== [] || $isMapOnly;

  $expandedGroupSlug = $groups !== [] ? $groups[0]['slug'] : '';
  /**
   * Pre-compute Alpine init state once so we don't re-encode JSON inline.
   *   • panelOpen — visibility of the left filter panel (toggled by the pill button)
   *   • openGroup — open accordion (one at a time, defaults to the first group)
   *   • activeCategorySlug — selected radio in the open group (visual only)
   *   • zoom — current map zoom level (1..2.5 in 0.25 steps)
   */
  $alpineInit = wp_json_encode([
      'panelOpen' => true,
      'openGroup' => $expandedGroupSlug,
      'activeCategorySlug' => '',
      'activeCategoryLabel' => '',
      'zoom' => 1,
  ], JSON_UNESCAPED_SLASHES);

  $hideLabelJson = wp_json_encode($filterButtonLabel, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES);
  $showLabelJson = wp_json_encode($filterButtonShowLabel, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES);
@endphp

@if(! $hasContent)
  @if(current_user_can('edit_posts'))
    @include('partials.component-editor-placeholder', [
        'wrapperClasses' => $root,
        'message' => __('Centre map — add a heading, map image and at least one category to display this band.', 'culvers'),
    ])
  @endif
@else
  <section
    {{-- Stable anchor for header nav deep-link (sheet feedback row 4: "Centre Map menu item
         should take you to the map section on the page, not the top"). --}}
    id="centre-map"
    class="centre-map {{ esc_attr($root) }} bg-deep-moss text-lighter-cream scroll-mt-32"
    data-component-root
    data-centre-map
    data-panel-position="{{ esc_attr($panelPosition) }}"
    x-data="centreMap({{ esc_attr($alpineInit) }})">
    @if($eyebrow !== '' || $bodyLines !== [])
      <div class="{{ LayoutShell::INNER_MAX_GUTTERED }} pt-12 md:pt-16">
        @if($eyebrow !== '')
          <p class="font-sans text-xs font-semibold uppercase tracking-widest text-glowleaf">
            {{ esc_html($eyebrow) }}
          </p>
        @endif
        @if($bodyLines !== [])
          <p class="@if($eyebrow !== '') mt-3 @endif max-w-3xl font-sans text-base font-light leading-7 text-lighter-cream/85 md:text-lg">
            @foreach($bodyLines as $i => $line)
              @if($i > 0)<br />@endif
              {{ esc_html(trim($line)) }}
            @endforeach
          </p>
        @endif
      </div>
    @endif

    {{-- Heading bar (filter toggle moves into the panel/map columns below to
         match Figma 51:7122 — "Hide filter" lives at the top of the filter
         column when panelOpen, and "Show filter" floats absolutely over the
         top-left of the map when panelOpen is false). --}}
    @if(! $isMapOnly && $heading !== '')
      <div class="{{ LayoutShell::INNER_MAX_GUTTERED }} centre-map__toolbar flex flex-wrap items-center justify-between gap-4 pt-12 md:pt-16 lg:pt-20">
        {{-- Section H2 (64px desktop / 48px mobile) — see Component::sectionHeadingClasses(). --}}
        <{{ $headingTag }} class="centre-map__heading {{ Component::sectionHeadingClasses('text-lighter-cream', 'm-0') }}">
          {{ esc_html($heading) }}
        </{{ $headingTag }}>
      </div>
    @endif

    {{-- Two-column band. When the filter panel is closed (`panelOpen === false`) the grid collapses
         to a single column so the map fills the band — handled by `:class` switching the lg grid.
         Map-only mode (no heading / categories) skips the band markup entirely below. --}}
    @if($isMapOnly && $imageUrl !== '' && is_array($image))
      {{-- Figma contact page (frame `51:9436`): the map is a fixed 1401×570
           graphic in a wider band. We crop the supplied asset to that aspect
           with `object-cover` so authors can re-use any map artwork without
           a custom export. --}}
      <div class="{{ LayoutShell::INNER_MAX_GUTTERED }} centre-map__band centre-map__band--map-only relative py-12 md:py-16">
        <div class="centre-map__map-only relative aspect-[1401/570] w-full overflow-hidden rounded-[12px]">
          {!! Image::render($image, [
              'class' => 'absolute inset-0 size-full object-cover',
              'alt' => isset($image['alt']) && is_string($image['alt']) ? $image['alt'] : '',
              'width' => isset($image['width']) ? (int) $image['width'] : 1401,
              'height' => isset($image['height']) ? (int) $image['height'] : 570,
              'loading' => 'lazy',
              'decoding' => 'async',
          ]) !!}
        </div>
      </div>
    @elseif($isMapOnly)
      {{-- Sheet feedback row 23: when no centre-map artwork is uploaded, fall back to a
           functional Google Maps embed for Culver Square so the band stays useful. The
           free Embed iframe needs no API key, so this works on any environment. --}}
      <div class="{{ LayoutShell::INNER_MAX_GUTTERED }} centre-map__band centre-map__band--map-only relative py-12 md:py-16">
        <div class="centre-map__map-only relative aspect-[1401/570] w-full overflow-hidden rounded-[12px]">
          <iframe
            class="absolute inset-0 size-full border-0"
            src="https://www.google.com/maps?q=Culver%20Square%20Colchester%20CO1%201JN&output=embed"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="{{ esc_attr__('Culver Square on Google Maps', 'culvers') }}"
            aria-label="{{ esc_attr__('Culver Square on Google Maps', 'culvers') }}"></iframe>
        </div>
      </div>
    @else
    <div
      class="{{ LayoutShell::INNER_MAX_GUTTERED }} centre-map__band relative grid grid-cols-1 gap-y-10 pb-12 pt-8 md:pb-16 md:pt-10 lg:gap-x-12"
      :class="panelOpen
        ? '{{ $panelPosition === 'right' ? 'lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)]' : 'lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]' }}'
        : 'lg:grid-cols-1'">
      <div
        class="centre-map__panel"
        :class="panelOpen
          ? '{{ $panelPosition === 'right' ? 'lg:order-2' : 'lg:order-1' }}'
          : 'lg:hidden'">
        @if($groups !== [])
          {{-- Hide-filter pill sits at the top of the filter column when the
               panel is open (matches Figma 51:7122 where the toggle floats
               over the map at top-left when closed and lives next to the
               category list when open). --}}
          <div class="centre-map__panel-toolbar mb-6 flex justify-end" x-show="panelOpen" x-transition.opacity.duration.150ms>
            <button
              type="button"
              class="centre-map__filter-toggle inline-flex items-center justify-center rounded-full bg-glowleaf px-5 py-2 font-sans text-xs font-semibold uppercase tracking-widest text-deep-moss transition hover:bg-lighter-cream culvers-focus-ring-compact"
              aria-controls="centre-map-panel-groups"
              :aria-expanded="panelOpen.toString()"
              @click="panelOpen = false">
              {{ esc_html($filterButtonLabel) }}
            </button>
          </div>
          <ul
            id="centre-map-panel-groups"
            class="centre-map__groups divide-y divide-lighter-cream/15 border-y border-lighter-cream/15"
            x-show="panelOpen"
            x-transition.opacity.duration.150ms>
            @foreach($groups as $group)
              @php($groupSlugAttr = esc_attr($group['slug']))
              @php($groupSlugJson = json_encode($group['slug'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES))
              <li class="centre-map__group" :class="openGroup === {{ e($groupSlugJson) }} ? 'centre-map__group--open' : ''">
                <h3 class="m-0">
                  <button
                    type="button"
                    class="centre-map__group-toggle flex w-full items-center justify-between gap-4 py-4 text-left font-sans text-sm font-semibold uppercase tracking-widest text-glowleaf transition hover:text-lighter-cream culvers-focus-ring-compact"
                    aria-expanded="false"
                    :aria-expanded="(openGroup === {{ e($groupSlugJson) }}).toString()"
                    aria-controls="centre-map-group-{{ $groupSlugAttr }}"
                    @click="openGroup = openGroup === {{ e($groupSlugJson) }} ? '' : {{ e($groupSlugJson) }}">
                    <span>{{ esc_html($group['label']) }}</span>
                    <span class="relative inline-flex size-4 shrink-0 items-center justify-center" aria-hidden="true">
                      <span class="block h-px w-4 bg-current"></span>
                      <span class="absolute h-4 w-px bg-current transition" :class="openGroup === {{ e($groupSlugJson) }} ? 'opacity-0' : 'opacity-100'"></span>
                    </span>
                  </button>
                </h3>
                <div
                  id="centre-map-group-{{ $groupSlugAttr }}"
                  class="centre-map__group-panel overflow-hidden"
                  x-show="openGroup === {{ e($groupSlugJson) }}"
                  x-transition.opacity.duration.150ms
                  style="display: none;">
                  <ul class="centre-map__category-list flex flex-col gap-1 pb-5">
                    @foreach($group['items'] as $cat)
                      @php($catSlugJson = json_encode($cat['slug'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES))
                      @php($catLabelJson = json_encode($cat['label'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES))
                      @php($isAll = str_ends_with($cat['slug'], '-all') || $cat['slug'] === 'all')
                      <li>
                        {{-- Category rows are highlight-toggles, not nav links — clicking
                             one updates `activeCategorySlug` so the map wrap surfaces a
                             status pill and any future SVG layers can light up matching
                             shops via the `[data-active-category]` hook. The original
                             URL is preserved as `data-deep-link` so authors can still
                             link out from a "View all in {category}" CTA elsewhere. --}}
                        <button
                          type="button"
                          class="centre-map__category group flex w-full items-center gap-3 rounded-md px-1 py-1.5 text-left font-sans text-sm font-medium uppercase tracking-[0.18em] text-lighter-cream transition hover:text-glowleaf culvers-focus-ring-compact"
                          @if($cat['url'] !== '') data-deep-link="{{ esc_url($cat['url']) }}" @endif
                          :class="activeCategorySlug === {{ e($catSlugJson) }} ? 'text-glowleaf' : ''"
                          @click="@if($isAll)activeCategorySlug = ''; activeCategoryLabel = '';@else activeCategorySlug = activeCategorySlug === {{ e($catSlugJson) }} ? '' : {{ e($catSlugJson) }}; activeCategoryLabel = activeCategorySlug === {{ e($catSlugJson) }} ? {{ e($catLabelJson) }} : '';@endif">
                          <span
                            class="centre-map__category-bullet inline-flex size-3 shrink-0 items-center justify-center rounded-full border border-lighter-cream/60 transition"
                            :class="activeCategorySlug === {{ e($catSlugJson) }} ? 'centre-map__category-bullet--active border-glowleaf bg-glowleaf' : ''"
                            aria-hidden="true">
                          </span>
                          <span>{{ esc_html($cat['label']) }}</span>
                        </button>
                      </li>
                    @endforeach
                  </ul>
                </div>
              </li>
            @endforeach
          </ul>
        @endif
      </div>

      <div
        class="centre-map__map-wrap relative w-full overflow-hidden rounded-2xl bg-deep-moss/60"
        :class="panelOpen
          ? '{{ $panelPosition === 'right' ? 'lg:order-1' : 'lg:order-2' }}'
          : 'lg:order-1'"
        :data-active-category="activeCategorySlug || null">
        @if($groups !== [])
          {{-- Status pill: visible whenever a category is active. Until the
               SVG export with per-shop layers arrives this is the visible
               feedback that a selection took effect. Sits at the bottom-left
               so it doesn't clash with the floating Show-filter pill or the
               zoom controls. --}}
          <div
            class="centre-map__status absolute bottom-4 left-4 z-10 flex items-center gap-3 rounded-full bg-glowleaf/95 px-4 py-1.5 text-deep-moss shadow-[0_2px_6px_rgba(0,0,0,0.18)] md:bottom-6 md:left-6"
            x-show="activeCategorySlug !== ''"
            x-cloak
            x-transition.opacity.duration.150ms>
            <span class="font-sans text-[11px] font-semibold uppercase tracking-[0.18em]">
              {{ __('Filtered', 'culvers') }}:
            </span>
            <span class="font-sans text-[11px] font-semibold uppercase tracking-[0.18em]" x-text="activeCategoryLabel"></span>
            <button
              type="button"
              class="-mr-2 inline-flex size-5 items-center justify-center rounded-full text-deep-moss transition hover:bg-deep-moss/15 culvers-focus-ring-compact"
              aria-label="{{ esc_attr__('Clear filter', 'culvers') }}"
              @click="activeCategorySlug = ''; activeCategoryLabel = ''">
              <svg viewBox="0 0 12 12" class="size-2.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                <path d="M2 2l8 8M10 2l-8 8" />
              </svg>
            </button>
          </div>
        @endif
        @if($groups !== [])
          {{-- Show-filter pill — absolute top-left of the map (Figma 51:7122).
               Only rendered when the panel is closed; clicking reopens it. --}}
          <button
            type="button"
            class="centre-map__filter-toggle centre-map__filter-toggle--floating absolute left-4 top-4 z-10 inline-flex items-center justify-center rounded-full bg-glowleaf px-5 py-2 font-sans text-xs font-semibold uppercase tracking-widest text-deep-moss shadow-[0_2px_6px_rgba(0,0,0,0.18)] transition hover:bg-lighter-cream culvers-focus-ring-compact md:left-6 md:top-6"
            aria-controls="centre-map-panel-groups"
            :aria-expanded="panelOpen.toString()"
            x-show="!panelOpen"
            x-cloak
            x-transition.opacity.duration.150ms
            @click="panelOpen = true">
            {{ esc_html($filterButtonShowLabel) }}
          </button>
        @endif
        @if($imageUrl !== '')
          {{-- Image is wrapped so we can apply `transform: scale()` for the zoom buttons without
               re-painting the rounded clipping container. `will-change-transform` keeps the
               transform on its own compositor layer so zooms feel instant on slower iGPUs. --}}
          <div
            class="centre-map__image-stage origin-center transition-transform duration-200 ease-out will-change-transform"
            :style="`transform: scale(${zoom});`">
            <img
              src="{{ esc_url($imageUrl) }}"
              alt="{{ esc_attr($imageAlt) }}"
              class="block size-full object-contain"
              loading="lazy"
              decoding="async" />
          </div>

          @if($showZoom)
            <div
              class="centre-map__zoom-controls absolute bottom-4 right-4 flex flex-col gap-2 md:bottom-6 md:right-6">
              <button
                type="button"
                class="centre-map__zoom-button inline-flex size-10 items-center justify-center rounded-full bg-glowleaf text-deep-moss transition hover:bg-lighter-cream culvers-focus-ring-compact disabled:cursor-not-allowed disabled:opacity-40 md:size-11"
                aria-label="{{ esc_attr__('Zoom in', 'culvers') }}"
                :disabled="zoom >= 2.5"
                @click="zoom = Math.min(2.5, Math.round((zoom + 0.25) * 100) / 100)">
                <svg viewBox="0 0 16 16" class="size-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                  <path d="M8 1v14M1 8h14" />
                </svg>
              </button>
              <button
                type="button"
                class="centre-map__zoom-button inline-flex size-10 items-center justify-center rounded-full bg-glowleaf text-deep-moss transition hover:bg-lighter-cream culvers-focus-ring-compact disabled:cursor-not-allowed disabled:opacity-40 md:size-11"
                aria-label="{{ esc_attr__('Zoom out', 'culvers') }}"
                :disabled="zoom <= 1"
                @click="zoom = Math.max(1, Math.round((zoom - 0.25) * 100) / 100)">
                <svg viewBox="0 0 16 16" class="size-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                  <path d="M1 8h14" />
                </svg>
              </button>
            </div>
          @endif
        @else
          <div class="flex aspect-[4/3] items-center justify-center px-8 py-12 text-center">
            <span class="font-heading text-2xl text-lighter-cream/40">{{ __('Map image not yet uploaded', 'culvers') }}</span>
          </div>
        @endif
      </div>
    </div>
    @endif
  </section>
@endif
