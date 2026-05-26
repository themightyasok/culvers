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

    /* Filter group expects `list<['slug', 'name']>`. Departments come from
       a taxonomy; contract types come from aggregated post-meta — both
       collapse to the same shape for the shared partial. */
    $career_department_options = array_map(
        static fn (\WP_Term $term): array => ['slug' => (string) $term->slug, 'name' => (string) $term->name],
        $departments
    );
    $career_contract_options = [];
    foreach ($contract_types as $slug => $label) {
        $career_contract_options[] = ['slug' => (string) $slug, 'name' => (string) $label];
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
  <section class="directory-archive pb-16 md:pb-28" x-data="directoryArchive">
    <div class="px-4 md:px-12">
      <div class="mx-auto w-full max-w-8xl">
        {{-- <div> wrapper (not <p>) so cascading text-center / typography
             utilities survive `wpautop()` injecting its own inner <p> — a
             <p>…<p></p>…</p> nesting is invalid HTML and browsers auto-
             close the outer paragraph on the inner one. --}}
        <div class="{{ \App\Helpers\LayoutShell::ARCHIVE_INTRO }}">
          {!! wp_kses_post(wpautop($introHtml)) !!}
        </div>

        <div class="flex flex-col gap-[22px]">
          <div class="directory-archive__toolbar flex justify-center lg:justify-start">
            @include('partials.directory-filter-pill', [
                'toggle_id' => $filter_toggle_id,
                'controls_id' => 'directory-archive-filters-careers',
            ])
          </div>

          <div class="directory-archive__main-row" :class="{ 'directory-archive__main-row--filters-visible': filtersVisible }">
            <div
              id="directory-archive-filters-careers"
              class="directory-archive__sidebar-shell min-w-0 shrink-0 lg:overflow-visible"
              :class="filtersVisible ? 'max-lg:max-h-[1600px] max-lg:overflow-visible' : 'max-lg:hidden'"
              role="region"
              aria-label="{{ esc_attr__('Careers filters', 'culvers') }}">
              <aside class="directory-archive__aside w-full rounded-none bg-transparent px-[23px] pb-6 pt-0 shadow-none lg:w-[325px] lg:shrink-0">
                <h2 class="sr-only">{{ __('Careers filters', 'culvers') }}</h2>

                @include('partials.directory-filter-group', [
                    'label' => __('Department', 'culvers'),
                    'aria_label' => __('Department', 'culvers'),
                    'panel_id' => 'directory-category-panel-careers',
                    'state_var' => 'categorySlug',
                    'toggle_var' => 'categoryOpen',
                    'setter' => 'setCategory',
                    'options' => $career_department_options,
                ])

                @if ($career_contract_options !== [])
                  @include('partials.directory-filter-group', [
                      'label' => __('Contract type', 'culvers'),
                      'aria_label' => __('Contract type', 'culvers'),
                      'panel_id' => 'directory-type-panel-careers',
                      'state_var' => 'typeSlug',
                      'toggle_var' => 'retailerOpen',
                      'setter' => 'setType',
                      'options' => $career_contract_options,
                  ])
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

  @php
    $careersContactCta = \App\Directory\CareerArchiveContactCta::componentOrNull();
  @endphp
  @if ($careersContactCta !== null)
    @include('components.info-block', ['component' => $careersContactCta])
  @endif
@endsection
