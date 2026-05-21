@extends('layouts.app')

@section('content')
  @php
    global $wp_query;
    $found_shops = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;
    $shop_categories = get_terms([
        'taxonomy' => 'culvers_shop_category',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);
    $shop_types = get_terms([
        'taxonomy' => 'culvers_shop_type',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);
    if ($shop_categories instanceof \WP_Error) {
        $shop_categories = [];
    }
    if ($shop_types instanceof \WP_Error) {
        $shop_types = [];
    }

    /* Filter group expects `list<['slug', 'name']>` so it can stay agnostic to
       whether options come from a taxonomy (Shop / Eat & Drink) or from
       aggregated post meta (Careers). One inline `array_map` per axis. */
    $shop_category_options = \App\Directory\DirectoryFilterOptions::fromFigmaOrder(
        \App\Directory\DirectoryFilterDefinitions::shopCategories(),
        $shop_categories
    );
    $shop_type_options = \App\Directory\DirectoryFilterOptions::fromFigmaOrder(
        \App\Directory\DirectoryFilterDefinitions::shopRetailerTypes(),
        $shop_types
    );

    $shopsArchiveHero = \App\Directory\ArchiveHeroComponent::fromOptions(\App\Directory\ShopArchiveFields::FIELD_PREFIX);
    /** @var array<string, mixed> $shopsArchiveHero */
    $shopsArchiveHero = apply_filters('culvers_shops_archive_hero_component', $shopsArchiveHero);
    if (! is_array($shopsArchiveHero)) {
        $shopsArchiveHero = [];
    }

    $introRaw = function_exists('get_field') ? get_field('shops_archive_intro_copy', 'option') : '';
    $introHtml = is_string($introRaw) ? trim($introRaw) : '';
    if ($introHtml === '') {
        $introHtml = __(
            'At Culver Square, enjoy a variety of shops, dining, and entertainment all in one place. From fashion and unique gifts to tasty meals, there\'s something for everyone. Join us and explore!',
            'culvers'
        );
    }

    $filter_toggle_id = 'directory-filter-toggle';
  @endphp

  @include('components.image-hero', ['component' => $shopsArchiveHero])

  {{-- Match header/footer: gutter padding outside, `max-w-8xl` inner only (see `site-footer__columns`, header shell). --}}
  <section class="directory-archive pb-16 md:pb-28" x-data="directoryArchive">
    <div class="px-4 md:px-12">
      <div class="mx-auto w-full max-w-8xl">
        {{-- <div> wrapper (not <p>) so cascading text-center / typography
             utilities survive `wpautop()` injecting its own inner <p> — a
             <p>…<p></p>…</p> nesting is invalid HTML and browsers auto-
             close the outer paragraph on the inner one. --}}
        <div
          class="archive-intro mx-auto max-w-[802px] text-center font-sans text-xl font-light text-deep-moss">
          {!! wp_kses_post(wpautop($introHtml)) !!}
        </div>

        <div class="flex flex-col gap-[22px]">
          <div class="directory-archive__toolbar flex justify-center lg:justify-start">
            @include('partials.directory-filter-pill', [
                'toggle_id' => $filter_toggle_id,
                'controls_id' => 'directory-archive-filters',
            ])
          </div>

          <div
            class="directory-archive__main-row"
            :class="{ 'directory-archive__main-row--filters-visible': filtersVisible }">
            <div
              id="directory-archive-filters"
              class="directory-archive__sidebar-shell min-w-0 shrink-0 lg:overflow-visible"
              :class="filtersVisible ? 'max-lg:max-h-[1600px] max-lg:overflow-visible' : 'max-lg:hidden'"
              role="region"
              aria-label="{{ esc_attr__('Directory filters', 'culvers') }}">
              <aside class="directory-archive__aside w-full rounded-none bg-white pb-6 pt-0 shadow-none lg:w-[325px] lg:shrink-0">
                <h2 class="sr-only">{{ __('Directory filters', 'culvers') }}</h2>

                @include('partials.directory-filter-group', [
                    'label' => __('Category', 'culvers'),
                    'aria_label' => __('Shop category', 'culvers'),
                    'panel_id' => 'directory-category-panel',
                    'state_var' => 'categorySlug',
                    'toggle_var' => 'categoryOpen',
                    'setter' => 'setCategory',
                    'options' => $shop_category_options,
                ])

                @include('partials.directory-filter-group', [
                    'label' => __('Retailer type', 'culvers'),
                    'aria_label' => __('Retailer type', 'culvers'),
                    'panel_id' => 'directory-retailer-panel',
                    'state_var' => 'typeSlug',
                    'toggle_var' => 'retailerOpen',
                    'setter' => 'setType',
                    'options' => $shop_type_options,
                ])
              </aside>
            </div>

            <div class="directory-archive__grid-column min-w-0">
              @if ($found_shops <= 0)
                <p class="rounded-[11px] border border-light-brown/25 bg-white px-6 py-12 text-center font-sans text-xl text-faded-olive">
                  {{ __('No shops published yet. Add shops under Shops → Add New in the admin.', 'culvers') }}
                </p>
              @else
                <div x-ref="grid" class="directory-archive__grid">
                  @while (have_posts())
                    @php the_post(); @endphp
                    @include('partials.directory-shop-card')
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
    $shopsArchiveStories = \App\Directory\ShopArchiveThreeCard::componentOrNull();
  @endphp
  @if ($shopsArchiveStories !== null)
    @include('components.three-card-block', ['component' => $shopsArchiveStories])
  @endif
@endsection
