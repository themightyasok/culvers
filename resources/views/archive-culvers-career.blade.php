@extends('layouts.app')

@section('content')
  @php
    /**
     * Careers archive — shares the Shop archive layout:
     *   • Hero slider band (same `components/hero-slider.blade.php` component
     *     as the homepage and `/shops/`), driven by ACF option fields
     *     registered in {@see App\Directory\CareerArchiveFields}.
     *   • Centered intro paragraph below the hero (Customizer text).
     *   • Filter pill + collapsible sidebar (Department + Contract type).
     *   • Responsive 4 / 3-up card grid via the shared `directoryArchive`
     *     Alpine module.
     *
     * Contract type isn't a taxonomy — it's a free-text field
     * (`career_employment_type`). We aggregate distinct values from the
     * current post set so editors don't have to maintain a parallel
     * taxonomy. Slugs are derived with `sanitize_title()` so the card's
     * `data-type-slugs` and the sidebar buttons line up.
     */
    global $wp_query;
    $found_roles = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;

    $departments = get_terms([
        'taxonomy' => 'culvers_career_department',
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);
    if ($departments instanceof \WP_Error) {
        $departments = [];
    }

    /* Aggregate the distinct employment-type values from the current
       archive query so the Contract type filter is always in sync. */
    $contract_types = [];
    if ($wp_query->have_posts()) {
        foreach ($wp_query->posts as $post) {
            if (! ($post instanceof \WP_Post)) {
                continue;
            }
            $value = function_exists('get_field') ? get_field('career_employment_type', $post->ID) : '';
            $value = is_string($value) ? trim($value) : '';
            if ($value === '') {
                continue;
            }
            $slug = sanitize_title($value);
            if ($slug === '' || isset($contract_types[$slug])) {
                continue;
            }
            $contract_types[$slug] = $value;
        }
        ksort($contract_types);
    }

    $careersArchiveHero = \App\Directory\ArchiveHeroComponent::fromOptions(\App\Directory\CareerArchiveFields::FIELD_PREFIX);
    /** @var array<string, mixed> $careersArchiveHero */
    $careersArchiveHero = apply_filters('culvers_careers_archive_hero_component', $careersArchiveHero);
    if (! is_array($careersArchiveHero)) {
        $careersArchiveHero = [];
    }

    $introRaw = function_exists('get_field') ? get_field('careers_archive_intro_copy', 'option') : '';
    $introHtml = is_string($introRaw) ? trim($introRaw) : '';
    if ($introHtml === '') {
        $introHtml = __(
            'Roles in the centre team and across our retail, hospitality and operations partners. Browse open positions below.',
            'culvers'
        );
    }

    $filter_toggle_id = 'directory-filter-toggle-careers';
  @endphp

  @include('components.image-hero', ['component' => $careersArchiveHero])

  {{-- Match header/footer: gutter padding outside, `max-w-8xl` inner only. --}}
  <section class="directory-archive bg-lighter-cream pb-16 pt-10 md:pb-28 md:pt-12" x-data="directoryArchive">
    <div class="px-4 md:px-12">
      <div class="mx-auto w-full max-w-8xl">
        {{-- <div> wrapper (not <p>) so cascading text-center / typography
             utilities survive `wpautop()` injecting its own inner <p> — a
             <p>…<p></p>…</p> nesting is invalid HTML and browsers auto-
             close the outer paragraph on the inner one. --}}
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
              aria-controls="directory-archive-filters-careers">
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
              id="directory-archive-filters-careers"
              class="directory-archive__sidebar-shell min-w-0 shrink-0 overflow-hidden max-lg:max-h-0"
              :class="filtersVisible ? 'max-lg:max-h-[1600px]' : 'max-lg:max-h-0'"
              role="region"
              aria-label="{{ esc_attr__('Careers filters', 'culvers') }}">
              <aside class="directory-archive__aside w-[325px] max-w-full rounded-none bg-white px-0 pb-6 pt-0 shadow-none md:px-0 lg:shrink-0">
                <h2 class="sr-only">{{ __('Careers filters', 'culvers') }}</h2>

                <div class="directory-archive__filter-section">
                  <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 py-4 text-left font-sans text-xs font-semibold uppercase tracking-widest text-faded-olive transition hover:text-deep-moss focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-glowleaf"
                    @click="toggleCategoryPanel()"
                    :aria-expanded="categoryOpen.toString()"
                    aria-controls="directory-category-panel-careers">
                    <span>{{ __('Department', 'culvers') }}</span>
                    <span class="text-lg leading-none text-deep-moss tabular-nums" aria-hidden="true" x-text="categoryOpen ? '−' : '+'"></span>
                  </button>

                  <ul
                    id="directory-category-panel-careers"
                    class="directory-archive__filter-list flex flex-col gap-3 pb-5 pt-1"
                    role="radiogroup"
                    aria-label="{{ esc_attr__('Department', 'culvers') }}"
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
                    @foreach ($departments as $term)
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

                @if ($contract_types !== [])
                  <div class="directory-archive__filter-section pt-2">
                    <button
                      type="button"
                      class="flex w-full items-center justify-between gap-2 py-4 text-left font-sans text-xs font-semibold uppercase tracking-widest text-faded-olive transition hover:text-deep-moss focus-visible:rounded-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-glowleaf"
                      @click="retailerOpen = !retailerOpen"
                      :aria-expanded="retailerOpen.toString()"
                      aria-controls="directory-type-panel-careers">
                      <span>{{ __('Contract type', 'culvers') }}</span>
                      <span class="text-lg leading-none text-deep-moss tabular-nums" aria-hidden="true" x-text="retailerOpen ? '−' : '+'"></span>
                    </button>
                    <ul
                      id="directory-type-panel-careers"
                      class="directory-archive__filter-list flex flex-col gap-3 pb-5 pt-1"
                      role="radiogroup"
                      aria-label="{{ esc_attr__('Contract type', 'culvers') }}"
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
                      @foreach ($contract_types as $slug => $label)
                        <li>
                          <button
                            type="button"
                            role="radio"
                            class="directory-archive__filter-option flex w-full items-center gap-[14px] py-0.5 text-left focus-visible:rounded-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-glowleaf"
                            :class="typeSlug === {{ json_encode($slug, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }} ? 'directory-archive__filter-option--on' : 'directory-archive__filter-option--off'"
                            :aria-checked="typeSlug === {{ json_encode($slug, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }}"
                            @click="setType({{ json_encode($slug, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }})">
                            <span class="directory-archive__radio" :class="typeSlug === {{ json_encode($slug, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }} ? 'directory-archive__radio--checked' : ''" aria-hidden="true"></span>
                            <span>{{ esc_html($label) }}</span>
                          </button>
                        </li>
                      @endforeach
                    </ul>
                  </div>
                @endif
              </aside>
            </div>

            <div class="directory-archive__grid-column min-w-0">
              @if ($found_roles <= 0)
                <p class="rounded-[11px] border border-light-brown/25 bg-white px-6 py-12 text-center font-sans text-xl text-faded-olive">
                  {{ __('No roles open right now — check back soon, or follow us on socials for the latest.', 'culvers') }}
                </p>
              @else
                <div x-ref="grid" class="directory-archive__grid">
                  @while (have_posts())
                    @php the_post(); @endphp
                    @include('partials.directory-career-card')
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
