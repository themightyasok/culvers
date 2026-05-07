@extends('layouts.app')

@section('content')
  @php
    /**
     * Eat & Drink archive — shares the Shop archive layout:
     *   • Static `image_hero` band (~half-viewport image banner with
     *     glowleaf title + spaced uppercase subtitle, both stacked
     *     vertically — Figma 51:9360 spec, 1440×646), driven by ACF
     *     option fields registered in {@see App\Directory\EatDrinkArchiveFields}.
     *     Component payload assembled by {@see App\Directory\ArchiveHeroComponent}.
     *   • Centered intro paragraph below the hero (Customizer text).
     *   • Filter pill + collapsible sidebar (Cuisine + Venue type).
     *   • Responsive 4 / 3-up card grid via the shared `directoryArchive`
     *     Alpine module.
     */
    global $wp_query;
    $found_venues = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;
    $eat_drink_categories = get_terms([
        'taxonomy' => 'culvers_eat_drink_category',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);
    $eat_drink_types = get_terms([
        'taxonomy' => 'culvers_eat_drink_type',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);
    if ($eat_drink_categories instanceof \WP_Error) {
        $eat_drink_categories = [];
    }
    if ($eat_drink_types instanceof \WP_Error) {
        $eat_drink_types = [];
    }

    $eatDrinkArchiveHero = \App\Directory\ArchiveHeroComponent::fromOptions(\App\Directory\EatDrinkArchiveFields::FIELD_PREFIX);
    /** @var array<string, mixed> $eatDrinkArchiveHero */
    $eatDrinkArchiveHero = apply_filters('culvers_eat_drink_archive_hero_component', $eatDrinkArchiveHero);
    if (! is_array($eatDrinkArchiveHero)) {
        $eatDrinkArchiveHero = [];
    }

    $introRaw = function_exists('get_field') ? get_field('eat_drink_archive_intro_copy', 'option') : '';
    $introHtml = is_string($introRaw) ? trim($introRaw) : '';
    if ($introHtml === '') {
        $introHtml = __(
            'From quick coffee stops to long, lazy lunches — find your next favourite spot in the centre.',
            'culvers'
        );
    }

    $filter_toggle_id = 'directory-filter-toggle-eat-drink';
  @endphp

  @include('components.image-hero', ['component' => $eatDrinkArchiveHero])

  {{-- Match header/footer: gutter padding outside, `max-w-8xl` inner only. --}}
  <section class="directory-archive bg-lighter-cream pb-16 pt-10 md:pb-28 md:pt-12" x-data="directoryArchive">
    <div class="px-4 md:px-12">
      <div class="mx-auto w-full max-w-8xl">
        {{-- `wpautop()` wraps the intro in its own <p>, so we use a <div> here.
             A <p>…<p>…</p></p> nesting is invalid HTML — browsers auto-close the
             outer <p> on the inner one, dropping the alignment / typography
             utilities. Cascading from a <div> keeps the centred line. --}}
        <div class="archive-intro mx-auto max-w-[802px] text-center font-sans text-xl font-light text-deep-moss">
          {!! wp_kses_post(wpautop($introHtml)) !!}
        </div>

        <div class="mt-[72px] flex flex-col gap-[22px] md:mt-[88px]">
          <div class="directory-archive__toolbar flex justify-start">
            <button
              id="{{ esc_attr($filter_toggle_id) }}"
              type="button"
              class="directory-archive__filter-pill inline-flex w-max max-w-full items-center gap-[12.887px] rounded-full bg-brand-500 py-[7.732px] pl-[25.773px] pr-5 font-sans text-xs font-semibold uppercase leading-[30px] tracking-wider text-deep-moss transition hover:brightness-95 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-deep-moss"
              @click="toggleFilters()"
              :aria-expanded="filtersVisible ? 'true' : 'false'"
              aria-controls="directory-archive-filters-eat-drink">
              <span x-text="filtersVisible ? {{ json_encode(__('Hide filters', 'culvers'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }} : {{ json_encode(__('Show filters', 'culvers'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }}"></span>
              <svg class="size-[19.825px] shrink-0" x-show="filtersVisible" x-cloak viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 6h12M4 12h8m-8 6h4M18 5l3 3m0 0-9 9m9-9-9 9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              <svg class="h-3 w-[18px] shrink-0" x-show="!filtersVisible" x-cloak viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 6h16M8 12h8M10 18h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
              </svg>
            </button>
          </div>

          <div class="directory-archive__main-row" :class="{ 'directory-archive__main-row--filters-visible': filtersVisible }">
            <div
              id="directory-archive-filters-eat-drink"
              class="directory-archive__sidebar-shell min-w-0 shrink-0 overflow-hidden max-lg:max-h-0"
              :class="filtersVisible ? 'max-lg:max-h-[1600px]' : 'max-lg:max-h-0'"
              role="region"
              aria-label="{{ esc_attr__('Eat & Drink filters', 'culvers') }}">
              <aside class="directory-archive__aside w-[325px] max-w-full rounded-none bg-white px-0 pb-6 pt-0 shadow-none md:px-0 lg:shrink-0">
                <h2 class="sr-only">{{ __('Eat & Drink filters', 'culvers') }}</h2>

                <div class="directory-archive__filter-section">
                  <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 py-4 text-left font-sans text-xs font-semibold uppercase tracking-widest text-faded-olive transition hover:text-deep-moss focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-glowleaf"
                    @click="toggleCategoryPanel()"
                    :aria-expanded="categoryOpen.toString()"
                    aria-controls="directory-category-panel-eat-drink">
                    <span>{{ __('Cuisine', 'culvers') }}</span>
                    <span class="text-lg leading-none text-deep-moss tabular-nums" aria-hidden="true" x-text="categoryOpen ? '−' : '+'"></span>
                  </button>

                  <ul
                    id="directory-category-panel-eat-drink"
                    class="directory-archive__filter-list flex flex-col gap-3 pb-5 pt-1"
                    role="radiogroup"
                    aria-label="{{ esc_attr__('Cuisine', 'culvers') }}"
                    x-show="categoryOpen"
                    x-transition.opacity.duration.150ms>
                    <li>
                      <button
                        type="button"
                        role="radio"
                        class="directory-archive__filter-option flex w-full items-center gap-[14px] py-0.5 text-left focus-visible:rounded-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-glowleaf"
                        :class="categorySlug === '' ? 'directory-archive__filter-option--on' : 'directory-archive__filter-option--off'"
                        :aria-checked="categorySlug === ''"
                        @click="setCategory('')">
                        <span class="directory-archive__radio" :class="categorySlug === '' ? 'directory-archive__radio--checked' : ''" aria-hidden="true"></span>
                        <span class="font-sans text-xs font-semibold uppercase tracking-widest">{{ __('All', 'culvers') }}</span>
                      </button>
                    </li>
                    @foreach ($eat_drink_categories as $term)
                      @if ($term instanceof \WP_Term)
                        <li>
                          <button
                            type="button"
                            role="radio"
                            class="directory-archive__filter-option flex w-full items-center gap-[14px] py-0.5 text-left focus-visible:rounded-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-glowleaf"
                            :class="categorySlug === {{ json_encode($term->slug, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }} ? 'directory-archive__filter-option--on' : 'directory-archive__filter-option--off'"
                            :aria-checked="categorySlug === {{ json_encode($term->slug, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }}"
                            @click="setCategory({{ json_encode($term->slug, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }})">
                            <span class="directory-archive__radio" :class="categorySlug === {{ json_encode($term->slug, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }} ? 'directory-archive__radio--checked' : ''" aria-hidden="true"></span>
                            <span class="leading-snug">{{ esc_html($term->name) }}</span>
                          </button>
                        </li>
                      @endif
                    @endforeach
                  </ul>
                </div>

                <div class="directory-archive__filter-section pt-2">
                  <button
                    type="button"
                    class="flex w-full items-center justify-between gap-2 py-4 text-left font-sans text-xs font-semibold uppercase tracking-widest text-faded-olive transition hover:text-deep-moss focus-visible:rounded-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-glowleaf"
                    @click="retailerOpen = !retailerOpen"
                    :aria-expanded="retailerOpen.toString()"
                    aria-controls="directory-type-panel-eat-drink">
                    <span>{{ __('Venue type', 'culvers') }}</span>
                    <span class="text-lg leading-none text-deep-moss tabular-nums" aria-hidden="true" x-text="retailerOpen ? '−' : '+'"></span>
                  </button>
                  <ul
                    id="directory-type-panel-eat-drink"
                    class="directory-archive__filter-list flex flex-col gap-3 pb-5 pt-1"
                    role="radiogroup"
                    aria-label="{{ esc_attr__('Venue type', 'culvers') }}"
                    x-show="retailerOpen"
                    x-transition.opacity.duration.150ms>
                    <li>
                      <button
                        type="button"
                        role="radio"
                        class="directory-archive__filter-option flex w-full items-center gap-[14px] py-0.5 text-left focus-visible:rounded-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-glowleaf"
                        :class="typeSlug === '' ? 'directory-archive__filter-option--on' : 'directory-archive__filter-option--off'"
                        :aria-checked="typeSlug === ''"
                        @click="setType('')">
                        <span class="directory-archive__radio" :class="typeSlug === '' ? 'directory-archive__radio--checked' : ''" aria-hidden="true"></span>
                        <span class="font-sans text-xs font-semibold uppercase tracking-widest">{{ __('All', 'culvers') }}</span>
                      </button>
                    </li>
                    @foreach ($eat_drink_types as $term)
                      @if ($term instanceof \WP_Term)
                        <li>
                          <button
                            type="button"
                            role="radio"
                            class="directory-archive__filter-option flex w-full items-center gap-[14px] py-0.5 text-left focus-visible:rounded-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-glowleaf"
                            :class="typeSlug === {{ json_encode($term->slug, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }} ? 'directory-archive__filter-option--on' : 'directory-archive__filter-option--off'"
                            :aria-checked="typeSlug === {{ json_encode($term->slug, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }}"
                            @click="setType({{ json_encode($term->slug, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }})">
                            <span class="directory-archive__radio" :class="typeSlug === {{ json_encode($term->slug, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }} ? 'directory-archive__radio--checked' : ''" aria-hidden="true"></span>
                            <span>{{ esc_html($term->name) }}</span>
                          </button>
                        </li>
                      @endif
                    @endforeach
                  </ul>
                </div>
              </aside>
            </div>

            <div class="directory-archive__grid-column min-w-0">
              @if ($found_venues <= 0)
                <p class="rounded-[11px] border border-light-brown/25 bg-white px-6 py-12 text-center font-sans text-xl text-faded-olive">
                  {{ __('No Eat & Drink venues published yet. Add venues under Eat & Drink → Add New in the admin.', 'culvers') }}
                </p>
              @else
                <div x-ref="grid" class="directory-archive__grid">
                  @while (have_posts())
                    @php the_post(); @endphp
                    @include('partials.directory-eat-drink-card')
                  @endwhile
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection
