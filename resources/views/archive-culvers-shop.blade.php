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
  <section class="directory-archive bg-lighter-cream pb-16 pt-10 md:pb-28 md:pt-12" x-data="directoryArchive">
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

        <div class="mt-[72px] flex flex-col gap-[22px] md:mt-[88px]">
          <div class="directory-archive__toolbar flex justify-start">
          <button
            id="{{ esc_attr($filter_toggle_id) }}"
            type="button"
            class="directory-archive__filter-pill inline-flex w-max max-w-full items-center gap-[12.887px] rounded-full bg-brand-500 py-[7.732px] pl-[25.773px] pr-5 font-sans text-xs font-semibold uppercase leading-[30px] tracking-wider text-deep-moss transition hover:brightness-95 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-deep-moss"
            @click="toggleFilters()"
            :aria-expanded="filtersVisible ? 'true' : 'false'"
            aria-controls="directory-archive-filters">
            <span x-text="filtersVisible ? {{ json_encode(__('Hide filters', 'culvers'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }} : {{ json_encode(__('Show filters', 'culvers'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) }}"></span>
            <svg
              class="size-[19.825px] shrink-0"
              x-show="filtersVisible"
              x-cloak
              viewBox="0 0 24 24"
              fill="none"
              aria-hidden="true">
              <path
                d="M4 6h12M4 12h8m-8 6h4M18 5l3 3m0 0-9 9m9-9-9 9"
                stroke="currentColor"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round" />
            </svg>
            <svg class="h-3 w-[18px] shrink-0" x-show="!filtersVisible" x-cloak viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M4 6h16M8 12h8M10 18h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
          </button>
          </div>

          <div
            class="directory-archive__main-row"
            :class="{ 'directory-archive__main-row--filters-visible': filtersVisible }">
          <div
            id="directory-archive-filters"
            class="directory-archive__sidebar-shell min-w-0 shrink-0 overflow-hidden max-lg:max-h-0"
            :class="filtersVisible ? 'max-lg:max-h-[1600px]' : 'max-lg:max-h-0'"
            role="region"
            aria-label="{{ esc_attr__('Directory filters', 'culvers') }}">
            <aside class="directory-archive__aside w-[325px] max-w-full rounded-none bg-white px-0 pb-6 pt-0 shadow-none md:px-0 lg:shrink-0">
              <h2 class="sr-only">{{ __('Directory filters', 'culvers') }}</h2>

            <div class="directory-archive__filter-section">
              <button
                type="button"
                class="flex w-full items-center justify-between gap-3 py-4 text-left font-sans text-xs font-semibold uppercase tracking-widest text-faded-olive transition hover:text-deep-moss focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-glowleaf"
                @click="toggleCategoryPanel()"
                :aria-expanded="categoryOpen.toString()"
                aria-controls="directory-category-panel">
                <span>{{ __('Category', 'culvers') }}</span>
                <span class="text-lg leading-none text-deep-moss tabular-nums" aria-hidden="true" x-text="categoryOpen ? '−' : '+'"></span>
              </button>

              <ul
                id="directory-category-panel"
                class="directory-archive__filter-list flex flex-col gap-3 pb-5 pt-1"
                role="radiogroup"
                aria-label="{{ esc_attr__('Shop category', 'culvers') }}"
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
                @foreach ($shop_categories as $term)
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
                aria-controls="directory-retailer-panel">
                <span>{{ __('Retailer type', 'culvers') }}</span>
                <span class="text-lg leading-none text-deep-moss tabular-nums" aria-hidden="true" x-text="retailerOpen ? '−' : '+'"></span>
              </button>
              <ul
                id="directory-retailer-panel"
                class="directory-archive__filter-list flex flex-col gap-3 pb-5 pt-1"
                role="radiogroup"
                aria-label="{{ esc_attr__('Retailer type', 'culvers') }}"
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
                @foreach ($shop_types as $term)
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
    <div class="bg-lighter-cream px-4 md:px-12">
      @include('components.three-card-block', ['component' => $shopsArchiveStories])
    </div>
  @endif
@endsection
