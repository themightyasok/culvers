@php
  use App\CentreMap\CentreMapFilterAssets;
  use App\CentreMap\ShopCentreMapDefaults;
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
   * Map is rendered as a flat image — no interactive pins (Figma has none).
   *
   * Filter-toggle and zoom controls are live (Alpine) — see the `centreMap`
   * Alpine module in `resources/scripts/alpine/centre-map.js`.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $eyebrow = trim((string) ($c['centre_map_eyebrow'] ?? ''));
  $heading = trim((string) ($c['centre_map_heading'] ?? ''));
  $headingTag = Component::headingTagFromComponent($c, 'centre_map_heading_level', 2);

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

  $filterMapUrls = CentreMapFilterAssets::hasFilterMaps() ? CentreMapFilterAssets::urlsBySlug() : [];
  $defaultMapUrl = CentreMapFilterAssets::hasFilterMaps()
      ? CentreMapFilterAssets::defaultUrl()
      : $imageUrl;
  if ($defaultMapUrl === '' && $imageUrl !== '') {
      $defaultMapUrl = $imageUrl;
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
  /*
   * The category list is a single global source (ShopCentreMapDefaults::categoryRows()),
   * not per-post data — the same nav appears on every centre map. Inject it whenever the
   * band shows a filter panel: any band with a heading, or a directory single. A bare
   * image-only band (no heading) keeps an empty list and renders as a plain map.
   */
  $hasFilterPanel = $heading !== '' || is_singular(ShopCentreMapDefaults::supportedPostTypes());
  $rawCategories = $hasFilterPanel ? ShopCentreMapDefaults::categoryRows() : [];
  if ($heading === '' && is_singular(ShopCentreMapDefaults::supportedPostTypes())) {
      $heading = __('Find your way around', 'culvers');
  }
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
      || $imageUrl !== '' || $defaultMapUrl !== '' || $groups !== [] || $isMapOnly;

  $expandedGroupSlug = $groups !== [] ? $groups[0]['slug'] : '';

  $retailerSelection = is_singular(ShopCentreMapDefaults::supportedPostTypes())
      ? ShopCentreMapDefaults::initialSelectionForPost((int) get_the_ID())
      : null;
  if ($retailerSelection !== null && $groups !== []) {
      $expandedGroupSlug = $retailerSelection['group'];
  }

  /** @var array<string, array{slug: string, label: string}> $groupAllByGroup */
  $groupAllByGroup = [];
  $initialCategorySlug = '';
  $initialCategoryLabel = '';
  foreach ($groups as $groupIndex => $group) {
      foreach ($group['items'] as $item) {
          $itemSlug = (string) ($item['slug'] ?? '');
          if ($itemSlug === '' || (! str_ends_with($itemSlug, '-all') && $itemSlug !== 'all')) {
              continue;
          }
          $groupAllByGroup[(string) $group['slug']] = [
              'slug' => $itemSlug,
              'label' => (string) ($item['label'] ?? ''),
          ];
          if ($groupIndex === 0 && $initialCategorySlug === '') {
              $initialCategorySlug = $itemSlug;
              $initialCategoryLabel = (string) ($item['label'] ?? '');
          }
          break;
      }
  }

  if ($retailerSelection !== null) {
      $initialCategorySlug = $retailerSelection['slug'];
      $initialCategoryLabel = $retailerSelection['label'];
  }

  $initialMapUrl = $initialCategorySlug !== '' && $filterMapUrls !== []
      ? CentreMapFilterAssets::urlForSlug($initialCategorySlug)
      : $defaultMapUrl;
  if ($initialMapUrl === '') {
      $initialMapUrl = $defaultMapUrl;
  }

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
      'activeCategorySlug' => $initialCategorySlug,
      'activeCategoryLabel' => $initialCategoryLabel,
      'zoom' => 1,
      'defaultMapUrl' => $defaultMapUrl,
      'mapUrls' => $filterMapUrls,
      'groupAllByGroup' => $groupAllByGroup,
  ], JSON_UNESCAPED_SLASHES);

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
          <p class="font-label text-xs font-semibold uppercase tracking-widest text-glowleaf">
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
      class="{{ LayoutShell::INNER_MAX_GUTTERED }} centre-map__band relative flex flex-col gap-8 pb-12 pt-6 max-lg:gap-6 md:pb-16 md:pt-8 lg:grid lg:items-start lg:gap-x-12 lg:gap-y-10"
      :class="panelOpen
        ? '{{ $panelPosition === 'right' ? 'lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)]' : 'lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]' }}'
        : 'lg:grid-cols-1'">
      @if($heading !== '')
        <{{ $headingTag }} class="centre-map__heading-mobile m-0 text-center lg:hidden {{ Component::sectionHeadingClasses('text-lighter-cream') }}">
          {{ esc_html($heading) }}
        </{{ $headingTag }}>
      @endif

      @include('partials.centre-map-band-layout', [
          'heading' => $heading,
          'headingTag' => $headingTag,
          'groups' => $groups,
          'panelPosition' => $panelPosition,
          'filterButtonLabel' => $filterButtonLabel,
          'filterButtonShowLabel' => $filterButtonShowLabel,
          'imageUrl' => $initialMapUrl !== '' ? $initialMapUrl : ($defaultMapUrl !== '' ? $defaultMapUrl : $imageUrl),
          'imageAlt' => $imageAlt,
          'hasFilterMaps' => $filterMapUrls !== [],
          'showZoom' => $showZoom,
          'isMapOnly' => $isMapOnly,
      ])
    </div>
    @endif
  </section>
@endif
