@extends('layouts.app')

@section('content')
  @php
    /**
     * Latest Events archive — `/latest-events/` (sibling to /latest-offers/
     * and /latest-news/; surfaced on the /whats-on/ landing). Shares the layout
     * with the other directory archives (shop / eat-drink / offers / news),
     * minus the filter sidebar (by design — the calendar is intentionally
     * unfiltered so visitors see the full programme):
     *   • Static `image_hero` band (~half-viewport image banner with
     *     glowleaf title + spaced uppercase subtitle, both stacked
     *     vertically — Figma 51:9360 spec, 1440×646), driven by ACF
     *     option fields registered in {@see App\Directory\EventArchiveFields}.
     *   • Centered intro paragraph below the hero (Customizer text).
     *   • Responsive 4 / 3-up grid using the shared moss-tile event card.
     */
    global $wp_query;
    $found_events = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;

    $eventsArchiveHero = \App\Directory\ArchiveHeroComponent::fromOptions(\App\Directory\EventArchiveFields::FIELD_PREFIX);
    /** @var array<string, mixed> $eventsArchiveHero */
    $eventsArchiveHero = apply_filters('culvers_events_archive_hero_component', $eventsArchiveHero);
    if (! is_array($eventsArchiveHero)) {
        $eventsArchiveHero = [];
    }

    $introRaw = function_exists('get_field') ? get_field('events_archive_intro_copy', 'option') : '';
    $introHtml = is_string($introRaw) ? trim($introRaw) : '';
    if ($introHtml === '') {
        $introHtml = __(
            'Workshops, performances, family days and seasonal moments — see what’s coming up at Culver Square.',
            'culvers'
        );
    }
  @endphp

  @include('components.image-hero', ['component' => $eventsArchiveHero])

  {{-- Match header/footer: gutter padding outside, `max-w-8xl` inner only. --}}
  <section class="pb-16 md:pb-28">
    <div class="px-4 md:px-12">
      <div class="mx-auto w-full max-w-8xl">
        {{-- <div> wrapper (not <p>) so cascading text-center / typography
             utilities survive `wpautop()` injecting its own inner <p> — a
             <p>…<p></p>…</p> nesting is invalid HTML and browsers auto-
             close the outer paragraph on the inner one. --}}
        <div class="{{ \App\Helpers\LayoutShell::ARCHIVE_INTRO }}">
          {!! wp_kses_post(wpautop($introHtml)) !!}
        </div>

        <div>
          @if ($found_events <= 0)
            <p class="rounded-[11px] border border-light-brown/25 bg-white px-6 py-12 text-center font-sans text-xl text-faded-olive">
              {{ __('Nothing on the calendar yet — check back soon, or sign up to the newsletter to be the first to hear.', 'culvers') }}
            </p>
          @else
            <ul class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              @while (have_posts())
                @php the_post(); @endphp
                <li class="min-w-0">
                  @include('partials.directory-event-card')
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
