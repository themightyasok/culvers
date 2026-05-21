@extends('layouts.app')

@section('content')
  @php
    /**
     * Latest News archive — `/latest-news/` (sibling to /latest-events/
     * and /latest-offers/; surfaced on the /whats-on/ landing). Mirrors
     * archive-culvers-offer.blade.php; the directory archives all share the
     * same shape (image-hero, centred intro, responsive 4 / 3-up grid of
     * shared moss-tile cards).
     */
    global $wp_query;
    $found_news = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;

    $newsArchiveHero = \App\Directory\ArchiveHeroComponent::fromOptions(\App\Directory\NewsArchiveFields::FIELD_PREFIX);
    /** @var array<string, mixed> $newsArchiveHero */
    $newsArchiveHero = apply_filters('culvers_news_archive_hero_component', $newsArchiveHero);
    if (! is_array($newsArchiveHero)) {
        $newsArchiveHero = [];
    }

    $introRaw = function_exists('get_field') ? get_field('news_archive_intro_copy', 'option') : '';
    $introHtml = is_string($introRaw) ? trim($introRaw) : '';
    if ($introHtml === '') {
        $introHtml = __(
            'Centre updates, retailer announcements and editorial from the Culver Square team.',
            'culvers'
        );
    }
  @endphp

  @include('components.image-hero', ['component' => $newsArchiveHero])

  <section class="pb-16 pt-10 md:pb-28 md:pt-12">
    <div class="px-4 md:px-12">
      <div class="mx-auto w-full max-w-8xl">
        {{-- <div> wrapper (not <p>) so cascading text-center / typography
             utilities survive `wpautop()` injecting its own inner <p>. --}}
        <div class="archive-intro mx-auto max-w-[802px] text-center font-sans text-xl font-light text-deep-moss">
          {!! wp_kses_post(wpautop($introHtml)) !!}
        </div>

        <div class="mt-[72px] md:mt-[88px]">
          @if ($found_news <= 0)
            <p class="rounded-[11px] border border-light-brown/25 bg-white px-6 py-12 text-center font-sans text-xl text-faded-olive">
              {{ __('No news articles published yet — check back soon, or sign up to the newsletter to be the first to hear.', 'culvers') }}
            </p>
          @else
            <ul class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              @while (have_posts())
                @php the_post(); @endphp
                <li class="min-w-0">
                  @include('partials.directory-news-card')
                </li>
              @endwhile
            </ul>
          @endif
        </div>
      </div>
    </div>
  </section>
@endsection
