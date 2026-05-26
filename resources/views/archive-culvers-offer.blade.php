@extends('layouts.app')

@section('content')
  @php
    /**
     * Latest Offers archive — `/latest-offers/` (sibling to /latest-events/
     * and /latest-news/; surfaced on the /whats-on/ landing). Mirrors
     * archive-culvers-event.blade.php; the four directory archives (shop,
     * eat-drink, events, offers) all share the same shape.
     */
    global $wp_query;
    $found_offers = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;

    $offersArchiveHero = \App\Directory\ArchiveHeroComponent::fromOptions(\App\Directory\OfferArchiveFields::FIELD_PREFIX);
    /** @var array<string, mixed> $offersArchiveHero */
    $offersArchiveHero = apply_filters('culvers_offers_archive_hero_component', $offersArchiveHero);
    if (! is_array($offersArchiveHero)) {
        $offersArchiveHero = [];
    }

    $introRaw = function_exists('get_field') ? get_field('offers_archive_intro_copy', 'option') : '';
    $introHtml = is_string($introRaw) ? trim($introRaw) : '';
    if ($introHtml === '') {
        $introHtml = __(
            'Promotions, discounts and brand campaigns from across the centre — pick something for your next visit.',
            'culvers'
        );
    }
  @endphp

  @include('components.image-hero', ['component' => $offersArchiveHero])

  <section class="pb-16 md:pb-28">
    <div class="px-4 md:px-12">
      <div class="mx-auto w-full max-w-8xl">
        <div class="{{ \App\Helpers\LayoutShell::ARCHIVE_INTRO }}">
          {!! wp_kses_post(wpautop($introHtml)) !!}
        </div>

        <div>
          @if ($found_offers <= 0)
            <p class="rounded-[11px] border border-light-brown/25 bg-white px-6 py-12 text-center font-sans text-xl text-faded-olive">
              {{ __('No offers running right now — check back soon.', 'culvers') }}
            </p>
          @else
            <ul class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              @while (have_posts())
                @php the_post(); @endphp
                <li class="min-w-0">
                  @include('partials.directory-offer-card')
                </li>
              @endwhile
            </ul>
          @endif

          @include('partials.whats-on-return-cta')
        </div>
      </div>
    </div>
  </section>
@endsection
