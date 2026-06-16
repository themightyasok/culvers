@php
  use App\Helpers\Component;

  $panelLeft = $panelPosition !== 'right';
  $filtersOrderClass = $panelLeft ? 'lg:order-1' : 'lg:order-2';
  $mapOrderClass = $panelLeft ? 'lg:order-2' : 'lg:order-1';
@endphp

{{--
  Two explicit columns on desktop (filters | map). Map height is fixed by aspect-ratio +
  absolute image stage — never tied to accordion height. Accordions use CSS grid-rows
  (same pattern as FAQ / text-image-slider), not x-show, so panels do not flash layout.
--}}
<div
  class="centre-map__map-cell w-full min-h-0 max-lg:order-2 {{ $mapOrderClass }}"
  :class="panelOpen ? '' : 'lg:col-span-full'">
  <div
    x-ref="mapWrap"
    class="centre-map__map-wrap relative w-full overflow-hidden rounded-[10px] bg-deep-moss/60 max-lg:mx-auto max-lg:max-w-[398px] max-lg:aspect-[398/390] lg:max-w-none lg:rounded-2xl"
    :data-active-category="activeCategorySlug || null">
    @if($groups !== [])
      <div
        class="centre-map__status absolute bottom-4 left-4 z-10 hidden items-center gap-3 rounded-full bg-glowleaf/95 px-4 py-1.5 text-deep-moss shadow-[0_2px_6px_rgba(0,0,0,0.18)] md:bottom-6 md:left-6 lg:flex"
        x-show="activeCategorySlug !== '' && ! activeCategorySlug.endsWith('-all')"
        x-cloak
        x-transition.opacity.duration.150ms>
        <span class="font-label text-xs font-semibold uppercase tracking-[0.18em]">
          {{ __('Filtered', 'culvers') }}:
        </span>
        <span class="font-label text-xs font-semibold uppercase tracking-[0.18em]" x-text="activeCategoryLabel"></span>
        <button
          type="button"
          class="-mr-2 inline-flex size-5 items-center justify-center rounded-full text-deep-moss transition hover:bg-deep-moss/15 culvers-focus-ring-compact"
          aria-label="{{ esc_attr__('Clear filter', 'culvers') }}"
          @click="selectAllForOpenGroup()">
          <svg viewBox="0 0 12 12" class="size-2.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <path d="M2 2l8 8M10 2l-8 8" />
          </svg>
        </button>
      </div>
    @endif
    @if($groups !== [])
      <button
        type="button"
        class="centre-map__filter-toggle centre-map__filter-toggle--floating absolute left-4 top-4 z-10 hidden items-center justify-center rounded-full bg-glowleaf px-5 py-2 font-label text-xs font-semibold uppercase tracking-widest text-deep-moss shadow-[0_2px_6px_rgba(0,0,0,0.18)] transition hover:bg-lighter-cream culvers-focus-ring-compact md:left-6 md:top-6 lg:inline-flex"
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
      <div
        class="centre-map__image-stage absolute inset-0 origin-center transition-transform duration-200 ease-out will-change-transform @if(! empty($hasFilterMaps)) centre-map__image-stage--filter-maps @endif"
        :class="{
          'centre-map__image-stage--pannable': zoom > 1,
          'centre-map__image-stage--dragging': isDragging,
        }"
        :style="mapTransformStyle()"
        @pointerdown="onMapPointerDown($event)"
        @pointermove="onMapPointerMove($event)"
        @pointerup="onMapPointerEnd($event)"
        @pointercancel="onMapPointerEnd($event)">
        <img
          :src="currentMapUrl()"
          src="{{ esc_url($imageUrl) }}"
          alt="{{ esc_attr($imageAlt) }}"
          class="pointer-events-none block size-full select-none object-contain max-lg:object-cover"
          draggable="false"
          loading="lazy"
          decoding="async" />
      </div>

      @if($showZoom)
        @include('partials.map-zoom-controls', ['variant' => 'centre-map'])
      @endif
    @else
      <div class="flex size-full min-h-[240px] items-center justify-center px-8 py-12 text-center">
        <span class="font-heading text-2xl text-lighter-cream/40">{{ __('Map image not yet uploaded', 'culvers') }}</span>
      </div>
    @endif
  </div>
</div>

@if(! $isMapOnly && ($heading !== '' || $groups !== []))
  <div
    class="centre-map__filters flex w-full min-h-0 flex-col max-lg:order-3 {{ $filtersOrderClass }}"
    x-bind:class="panelOpen ? '' : 'max-lg:!block lg:hidden'">
    <div
      class="centre-map__panel-header mb-6 hidden flex-wrap items-center gap-x-4 gap-y-3 lg:flex @if($heading !== '') justify-between @else justify-end @endif">
      @if($heading !== '')
        <{{ $headingTag }} class="centre-map__heading min-w-0 flex-1 text-balance {{ Component::sectionHeadingClasses('text-lighter-cream', 'm-0') }}">
          {{ esc_html($heading) }}
        </{{ $headingTag }}>
      @endif
      @if($groups !== [])
        <button
          type="button"
          class="centre-map__filter-toggle shrink-0 self-start sm:self-center inline-flex items-center justify-center rounded-full bg-glowleaf px-5 py-2 font-label text-xs font-semibold uppercase tracking-widest text-deep-moss transition hover:bg-lighter-cream culvers-focus-ring-compact"
          aria-controls="centre-map-panel-groups"
          :aria-expanded="panelOpen.toString()"
          @click="panelOpen = false">
          {{ esc_html($filterButtonLabel) }}
        </button>
      @endif
    </div>

    @if($groups !== [])
      <div class="centre-map__mobile-filters flex flex-col gap-6 lg:hidden">
        <nav
          class="centre-map__mobile-tabs flex flex-wrap items-center justify-center gap-4"
          aria-label="{{ esc_attr__('Map categories', 'culvers') }}">
          @foreach($groups as $group)
            @php($groupSlugJson = json_encode($group['slug'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES))
            <button
              type="button"
              class="shrink-0 cursor-pointer transition culvers-focus-ring-compact {{ Component::centreMapFilterLabelClasses() }}"
              :class="openGroup === {{ e($groupSlugJson) }}
                ? 'rounded-full bg-glowleaf px-4 py-2 text-[0.8125rem] tracking-wide text-deep-moss'
                : 'text-dustleaf hover:text-lighter-cream'"
              :aria-pressed="(openGroup === {{ e($groupSlugJson) }}).toString()"
              @click="selectGroup({{ e($groupSlugJson) }})">
              {{ esc_html($group['label']) }}
            </button>
          @endforeach
        </nav>
        <hr class="m-0 border-0 border-t border-lighter-cream/15" />
        @foreach($groups as $group)
          @php($groupSlugJson = json_encode($group['slug'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES))
          <ul
            class="centre-map__mobile-category-grid m-0 grid list-none grid-cols-2 gap-x-6 gap-y-3 p-0"
            x-bind:class="openGroup === {{ e($groupSlugJson) }} ? '' : 'hidden'">
            @foreach($group['items'] as $cat)
              @php($catSlugJson = json_encode($cat['slug'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES))
              @php($catLabelJson = json_encode($cat['label'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES))
              @php($isAll = str_ends_with($cat['slug'], '-all') || $cat['slug'] === 'all')
              <li class="grid min-w-0">
                @include('partials.centre-map-category-option', [
                    'cat' => $cat,
                    'catSlugJson' => $catSlugJson,
                    'catLabelJson' => $catLabelJson,
                    'isAll' => $isAll,
                    'filterOnly' => true,
                ])
              </li>
            @endforeach
          </ul>
        @endforeach
      </div>

      <ul
        id="centre-map-panel-groups"
        class="centre-map__groups hidden divide-y divide-lighter-cream/15 border-y border-lighter-cream/15 lg:block">
        @foreach($groups as $group)
          @php($groupSlugAttr = esc_attr($group['slug']))
          @php($groupSlugJson = json_encode($group['slug'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES))
          <li class="centre-map__group" :class="openGroup === {{ e($groupSlugJson) }} ? 'centre-map__group--open' : ''">
            <h3 class="m-0">
              <button
                type="button"
                class="centre-map__group-toggle flex w-full cursor-pointer items-center justify-between gap-4 py-4 text-left text-glowleaf transition hover:text-lighter-cream culvers-focus-ring-compact {{ Component::centreMapFilterLabelClasses() }}"
                :aria-expanded="(openGroup === {{ e($groupSlugJson) }}).toString()"
                aria-controls="centre-map-group-{{ $groupSlugAttr }}"
                @click="toggleGroup({{ e($groupSlugJson) }})">
                <span>{{ esc_html($group['label']) }}</span>
                <span class="relative inline-flex size-4 shrink-0 items-center justify-center" aria-hidden="true">
                  <span class="block h-px w-4 bg-current"></span>
                  <span class="absolute h-4 w-px bg-current transition" :class="openGroup === {{ e($groupSlugJson) }} ? 'opacity-0' : 'opacity-100'"></span>
                </span>
              </button>
            </h3>
            <div
              id="centre-map-group-{{ $groupSlugAttr }}"
              class="centre-map__group-panel grid min-h-0 grid-rows-[0fr] transition-[grid-template-rows] duration-300 ease-out motion-reduce:transition-none"
              :class="openGroup === {{ e($groupSlugJson) }} ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
              role="region"
              :aria-hidden="(openGroup !== {{ e($groupSlugJson) }}).toString()">
              <div class="centre-map__group-panel-inner min-h-0 overflow-hidden">
                <ul class="centre-map__category-list flex flex-col gap-1 pb-5">
                  @foreach($group['items'] as $cat)
                    @php($catSlugJson = json_encode($cat['slug'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES))
                    @php($catLabelJson = json_encode($cat['label'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES))
                    @php($isAll = str_ends_with($cat['slug'], '-all') || $cat['slug'] === 'all')
                    <li>
                      @include('partials.centre-map-category-option', [
                          'cat' => $cat,
                          'catSlugJson' => $catSlugJson,
                          'catLabelJson' => $catLabelJson,
                          'isAll' => $isAll,
                      ])
                    </li>
                  @endforeach
                </ul>
              </div>
            </div>
          </li>
        @endforeach
      </ul>
    @endif
  </div>
@endif
