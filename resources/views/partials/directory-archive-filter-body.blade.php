@php
  /**
   * Filter sidebar + card grid shell for Shops, Eat & Drink, and Careers archives.
   *
   * @var string $introHtml
   * @var string $filtersRegionLabel
   * @var string $filterToggleId
   * @var string $filtersControlsId
   * @var int $foundCount
   * @var string $emptyMessage
   * @var string $cardPartial  e.g. partials.directory-shop-card
   * @var list<array<string, mixed>> $filterGroups  {@see partials.directory-filter-group}
   */
  use App\Helpers\LayoutShell;

  $filtersRegionLabel = (string) ($filtersRegionLabel ?? __('Directory filters', 'culvers'));
  $filterToggleId = (string) ($filterToggleId ?? 'directory-filter-toggle');
  $filtersControlsId = (string) ($filtersControlsId ?? 'directory-archive-filters');
  $foundCount = (int) ($foundCount ?? 0);
  $emptyMessage = (string) ($emptyMessage ?? '');
  $cardPartial = (string) ($cardPartial ?? 'partials.directory-shop-card');
  $filterGroups = is_array($filterGroups ?? null) ? $filterGroups : [];
@endphp

<section class="directory-archive pb-16 md:pb-28" x-data="directoryArchive">
  <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
    @include('partials.directory-archive-intro', ['introHtml' => $introHtml ?? ''])

    <div class="flex flex-col gap-[22px]">
      <div class="directory-archive__toolbar flex justify-center lg:justify-start">
        @include('partials.directory-filter-pill', [
            'toggle_id' => $filterToggleId,
            'controls_id' => $filtersControlsId,
        ])
      </div>

      <div
        class="directory-archive__main-row"
        :class="{ 'directory-archive__main-row--filters-visible': filtersVisible }">
        <div
          id="{{ esc_attr($filtersControlsId) }}"
          class="directory-archive__sidebar-shell min-w-0 shrink-0 lg:overflow-visible"
          :class="filtersVisible ? 'max-lg:max-h-[1600px] max-lg:overflow-visible' : 'max-lg:hidden'"
          role="region"
          aria-label="{{ esc_attr($filtersRegionLabel) }}">
          <aside class="directory-archive__aside w-full rounded-none bg-transparent px-[23px] pb-6 pt-0 shadow-none lg:w-[325px] lg:shrink-0">
            <h2 class="sr-only">{{ esc_html($filtersRegionLabel) }}</h2>

            @foreach($filterGroups as $group)
              @if(is_array($group))
                @include('partials.directory-filter-group', $group)
              @endif
            @endforeach
          </aside>
        </div>

        <div class="directory-archive__grid-column min-w-0">
          @if($foundCount <= 0)
            <p class="rounded-[11px] border border-light-brown/25 bg-white px-6 py-12 text-center font-sans text-xl text-faded-olive">
              {{ esc_html($emptyMessage) }}
            </p>
          @else
            <div x-ref="grid" class="directory-archive__grid">
              @while (have_posts())
                @php the_post(); @endphp
                @include($cardPartial)
              @endwhile
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</section>
