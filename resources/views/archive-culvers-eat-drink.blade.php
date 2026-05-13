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

    /* Filter group expects `list<['slug', 'name']>`. */
    $eat_drink_category_options = array_map(
        static fn (\WP_Term $term): array => ['slug' => (string) $term->slug, 'name' => (string) $term->name],
        $eat_drink_categories
    );
    $eat_drink_type_options = array_map(
        static fn (\WP_Term $term): array => ['slug' => (string) $term->slug, 'name' => (string) $term->name],
        $eat_drink_types
    );

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
  {{-- Sheet feedback row 21: same intro-padding trim as shop archive. --}}
  <section class="directory-archive bg-lighter-cream pb-16 pt-6 md:pb-28 md:pt-8" x-data="directoryArchive">
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
            @include('partials.directory-filter-pill', [
                'toggle_id' => $filter_toggle_id,
                'controls_id' => 'directory-archive-filters-eat-drink',
            ])
          </div>

          <div class="directory-archive__main-row" :class="{ 'directory-archive__main-row--filters-visible': filtersVisible }">
            <div
              id="directory-archive-filters-eat-drink"
              class="directory-archive__sidebar-shell min-w-0 shrink-0 lg:overflow-visible"
              :class="
                filtersVisible
                  ? 'max-lg:max-h-[1600px] max-lg:overflow-visible'
                  : 'max-lg:max-h-0 max-lg:overflow-hidden'
              "
              role="region"
              aria-label="{{ esc_attr__('Eat & Drink filters', 'culvers') }}">
              <aside class="directory-archive__aside w-[325px] max-w-full rounded-none bg-white px-0 pb-6 pt-0 shadow-none md:px-0 lg:shrink-0">
                <h2 class="sr-only">{{ __('Eat & Drink filters', 'culvers') }}</h2>

                @include('partials.directory-filter-group', [
                    'label' => __('Cuisine', 'culvers'),
                    'aria_label' => __('Cuisine', 'culvers'),
                    'panel_id' => 'directory-category-panel-eat-drink',
                    'state_var' => 'categorySlug',
                    'toggle_var' => 'categoryOpen',
                    'setter' => 'setCategory',
                    'options' => $eat_drink_category_options,
                ])

                @include('partials.directory-filter-group', [
                    'label' => __('Venue type', 'culvers'),
                    'aria_label' => __('Venue type', 'culvers'),
                    'panel_id' => 'directory-type-panel-eat-drink',
                    'state_var' => 'typeSlug',
                    'toggle_var' => 'retailerOpen',
                    'setter' => 'setType',
                    'options' => $eat_drink_type_options,
                ])
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

  {{-- Same News / Events / Offers strip as Shops — Eat & Drink has its own options under Appearance → Eat & Drink directory. --}}
  @php $eatDrinkArchiveStories = \App\Directory\EatDrinkArchiveThreeCard::componentOrNull(); @endphp
  @if ($eatDrinkArchiveStories !== null)
    <div class="bg-lighter-cream px-4 md:px-12">
      @include('components.three-card-block', ['component' => $eatDrinkArchiveStories])
    </div>
  @endif
@endsection
