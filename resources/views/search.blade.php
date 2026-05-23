@extends('layouts.app')

@php
  use App\Search\SearchService;

  global $wp_query;

  $searchQuery = trim((string) get_search_query());
  $found = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;
  $tooShort = $searchQuery !== '' && mb_strlen($searchQuery) < SearchService::MIN_QUERY_LENGTH;
@endphp

@section('content')
  <section class="search-page pb-16 md:pb-28" aria-labelledby="search-page-heading">
    <div class="px-4 md:px-12">
      <div class="mx-auto w-full max-w-8xl">
        <header class="search-page__intro mx-auto max-w-[802px] text-center pt-10 md:pt-12 lg:pt-14">
          <h1
            id="search-page-heading"
            class="m-0 font-heading text-5xl font-normal leading-[1.1] text-deep-moss md:text-7xl">
            {{ __('Search', 'culvers') }}
          </h1>

          @if($searchQuery !== '')
            <p class="mt-4 font-sans text-xl font-light leading-[1.3] text-faded-olive">
              @if($tooShort)
                {{ sprintf(
                    /* translators: %d minimum characters */
                    __('Enter at least %d characters to search.', 'culvers'),
                    SearchService::MIN_QUERY_LENGTH
                ) }}
              @elseif($found === 1)
                {{ sprintf(
                    /* translators: 1: search query, 2: result count */
                    __('Results for “%1$s” — %2$d result', 'culvers'),
                    $searchQuery,
                    $found
                ) }}
              @else
                {{ sprintf(
                    /* translators: 1: search query, 2: result count */
                    __('Results for “%1$s” — %2$d results', 'culvers'),
                    $searchQuery,
                    $found
                ) }}
              @endif
            </p>
          @else
            <p class="mt-4 font-sans text-xl font-light leading-[1.3] text-faded-olive">
              {{ __('Search shops, dining, news, events and site pages.', 'culvers') }}
            </p>
          @endif

          <form
            class="search-page__form mx-auto mt-8 max-w-3xl"
            method="get"
            action="{{ esc_url(home_url('/')) }}"
            role="search">
            <div class="flex min-h-[75px] items-center gap-3 rounded-full border-4 border-brand-500 bg-light-cream px-4 py-2 md:min-h-[80px] md:gap-6 md:px-5">
              <svg class="shrink-0 text-faded-olive" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.6" />
                <path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
              </svg>
              <label class="sr-only" for="search-page-input">{{ __('Search', 'culvers') }}</label>
              <input
                id="search-page-input"
                class="min-w-0 flex-1 border-0 bg-transparent font-sans text-xl font-light leading-[1.3] text-faded-olive placeholder:text-faded-olive/55 focus:outline-none focus:ring-0"
                type="search"
                name="s"
                value="{{ esc_attr($searchQuery) }}"
                autocomplete="off"
                placeholder="{{ esc_attr__('Search', 'culvers') }}" />
              <button
                type="submit"
                class="btn btn-dark shrink-0 rounded-full px-6 py-2.5 font-label text-sm font-semibold uppercase tracking-[0.12em]">
                {{ __('Go', 'culvers') }}
              </button>
            </div>
          </form>
        </header>

        <div class="search-page__results mx-auto mt-10 max-w-3xl md:mt-12">
          @if($searchQuery === '' || $tooShort)
            <p class="rounded-[14px] bg-light-cream px-6 py-8 text-center font-sans text-xl font-light leading-[1.3] text-faded-olive/80 shadow-md">
              {{ __('Type a search term above to see matching pages.', 'culvers') }}
            </p>
          @elseif(! have_posts())
            <p class="rounded-[14px] bg-light-cream px-6 py-8 text-center font-sans text-xl font-light leading-[1.3] text-faded-olive/80 shadow-md">
              {{ __('No results. Try a different spelling or a shorter phrase.', 'culvers') }}
            </p>
          @else
            <ul class="divide-y divide-faded-olive/15 rounded-[14px] bg-light-cream px-4 py-2 shadow-md md:px-6">
              @while (have_posts())
                @php the_post(); @endphp
                @include('partials.search-result-row', [
                    'result' => SearchService::format(get_post()),
                    'query' => $searchQuery,
                ])
              @endwhile
            </ul>

            @php
              $pagination = get_the_posts_pagination([
                  'mid_size' => 1,
                  'prev_text' => __('Previous', 'culvers'),
                  'next_text' => __('Next', 'culvers'),
              ]);
            @endphp
            @if(is_string($pagination) && trim($pagination) !== '')
              <nav class="search-page__pagination mt-8 font-sans text-lg text-faded-olive" aria-label="{{ esc_attr__('Search results pages', 'culvers') }}">
                {!! $pagination !!}
              </nav>
            @endif
          @endif
        </div>
      </div>
    </div>
  </section>
@endsection
