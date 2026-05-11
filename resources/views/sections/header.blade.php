@php
  use App\Nav\PrimaryNav;

  /** @var list<array{id:int,title:string,url:string,children:list<array{title:string,url:string,preview:string}>}> $navTree */
  $navTree = PrimaryNav::tree('primary_navigation');

  // Theme mods (Appearance → Customize).
  $mapUrl = get_theme_mod('culvers_centre_map_url', '#');
  $hereUrl = get_theme_mod('culvers_getting_here_url', '#');
  $instagramUrl = get_theme_mod('culvers_instagram_url', '#');
  $facebookUrl = get_theme_mod('culvers_facebook_url', '#');
@endphp

{{--
  Site header — Culver Square

  Naming (BEM-style hooks for CSS / QA / design handoff):
    site-header       Landmark + search UI (`site-header__*`).
    mega-nav          Primary nav + mega panels + mobile drawer (`mega-nav__*`).

  Content width (matches `site-footer__columns`): horizontal padding on the gutter,
  then `max-w-8xl` (1440px) on the inner row — never both on one element.
--}}

<header
  class="site-header fixed inset-x-0 top-0 z-50"
  x-data="siteHeader"
  x-on:keydown.escape.window="closeAll()">
  <script
    type="application/json"
    id="culvers-mobile-nav-tree">{!! wp_json_encode(
        $navTree,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
    ) !!}</script>
  {{--
    Scroll-hide transform lives on `.site-header__chrome` only. The mobile drawer stays a direct
    child of `<header>` so `position:fixed` resolves to the viewport (transform ancestors would clip it).
  --}}
  <div
    class="site-header__chrome will-change-transform transition-transform duration-700 ease-[cubic-bezier(0.33,1,0.68,1)] motion-reduce:transition-none"
    x-ref="headerChrome"
    x-bind:class="headerDockHidden ? '-translate-y-full pointer-events-none' : 'translate-y-0'">
  {{-- Shell: full width; content width matches footer (`lg:px-12` + inner `max-w-8xl`). --}}
  <div class="site-header__shell w-full overflow-visible">
    {{-- Entrance animation once the header intersects the viewport. --}}
    <div
      class="site-header__reveal transition-all duration-700 ease-out"
      x-bind:class="headerRevealed ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'">
      <div
        class="site-header__padding relative transition-[padding] duration-300 ease-in-out max-lg:px-0 max-lg:py-0 lg:px-12 lg:pb-2.5 lg:pt-8">

        <div class="mx-auto w-full max-w-8xl">

        {{-- Mega navigation mode --}}
        <div
          class="mega-nav relative flex flex-col gap-0"
          x-show="!searchOpen"
          x-cloak
          x-on:click.outside="closeMega()"
          x-on:mouseenter="cancelCloseMegaHover()"
          x-on:mouseleave="scheduleCloseMegaHover()">

          {{-- Olive bar — fixed chrome width (max-w-8xl parent); scroll only hides the dock, no morph. --}}
          {{-- Mobile / tablet (<lg, 1024px): Figma "Mobile — Nav" 75px flat bar; burger | centred wordmark | search. Desktop: pill bar. --}}
          <div
            class="mega-nav__bar relative z-50 w-full bg-faded-olive max-lg:rounded-none max-lg:border-b max-lg:border-glowleaf lg:rounded-full">
            <div class="mega-nav__bar-gutter w-full max-lg:py-0 py-2">
              <div
                class="mega-nav__bar-row relative flex h-[75px] min-h-[75px] w-full items-center gap-3 px-4 max-lg:gap-2 lg:h-auto lg:min-h-[80px] lg:gap-6 lg:px-5 xl:px-6">
                <button
                  type="button"
                  class="mega-nav__burger relative z-20 inline-flex size-11 shrink-0 items-center justify-center text-glowleaf lg:hidden culvers-focus-ring"
                  aria-controls="mega-mobile-drawer"
                  x-bind:aria-expanded="mobileOpen ? 'true' : 'false'"
                  x-on:click="mobileOpen = !mobileOpen">
                  <span class="sr-only" x-show="!mobileOpen" x-cloak>{{ __('Open menu', 'culvers') }}</span>
                  <span class="sr-only" x-show="mobileOpen" x-cloak>{{ __('Close menu', 'culvers') }}</span>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                  </svg>
                </button>
                <div
                  class="mega-nav__bar-main pointer-events-none flex min-w-0 flex-1 items-center lg:pointer-events-auto lg:gap-[42px]">
                  <a
                    class="mega-nav__logo shrink-0 text-glowleaf max-lg:pointer-events-auto max-lg:absolute max-lg:left-1/2 max-lg:top-1/2 max-lg:z-10 max-lg:-translate-x-1/2 max-lg:-translate-y-1/2 lg:static lg:translate-x-0 lg:translate-y-0"
                    href="{{ esc_url(home_url('/')) }}"
                    rel="home"
                    aria-label="{{ esc_attr(get_bloginfo('name')) }}">
                    @if(has_custom_logo())
                      <span class="block max-h-[28px] w-[178px] max-lg:max-h-[24px] [&_img]:h-full [&_img]:w-auto [&_img]:max-h-[28px] max-lg:[&_img]:max-h-[24px] [&_img]:object-contain [&_img]:object-left max-lg:[&_img]:object-center">
                        {!! get_custom_logo() !!}
                      </span>
                    @else
                      @include('partials.culver-square-logo', ['class' => 'block h-[22px] w-[178px] max-w-[min(100%,178px)] shrink-0 text-glowleaf max-lg:h-[20px] lg:max-w-full [&_svg]:max-h-full'])
                    @endif
                  </a>

                  @if($navTree !== [])
                    <nav class="mega-nav__primary hidden flex-1 justify-start lg:flex" aria-label="{{ esc_attr__('Primary', 'culvers') }}">
                      <ul class="flex flex-wrap items-center gap-x-[30px] gap-y-2">
                        @foreach($navTree as $branch)
                          @php $hasMega = $branch['children'] !== []; @endphp
                          <li
                            class="mega-nav__top-item list-none"
                            @if($hasMega)
                              x-on:mouseenter="openMegaFromHover({{ $branch['id'] }})"
                            @endif>
                            @if($hasMega)
                              <button
                                type="button"
                                class="mega-nav__trigger inline-flex items-center gap-2 capitalize transition-colors hover:text-glowleaf culvers-focus-ring"
                                x-bind:class="megaOpenId === {{ $branch['id'] }} ? 'text-glowleaf' : 'text-white'"
                                x-on:click.prevent="toggleMega({{ $branch['id'] }})"
                                x-bind:aria-expanded="megaOpenId === {{ $branch['id'] }} ? 'true' : 'false'"
                                aria-haspopup="true"
                                aria-controls="mega-panel-{{ $branch['id'] }}">
                                <span class="font-heading font-light text-base leading-6">{{ $branch['title'] }}</span>
                                <span class="mega-nav__chevron relative ms-0.5 inline-flex size-3 shrink-0 items-center justify-center" aria-hidden="true">
                                  <svg
                                    class="size-3"
                                    viewBox="0 0 12 12"
                                    fill="none"
                                    x-show="megaOpenId !== {{ $branch['id'] }}"
                                    x-cloak>
                                    <path
                                      d="M2.5 4.25 6 8 9.5 4.25"
                                      stroke="currentColor"
                                      stroke-width="1.2"
                                      stroke-linecap="round"
                                      stroke-linejoin="round" />
                                  </svg>
                                  <svg
                                    class="size-3"
                                    viewBox="0 0 12 12"
                                    fill="none"
                                    x-show="megaOpenId === {{ $branch['id'] }}"
                                    x-cloak>
                                    <path
                                      d="M2.5 7.75 6 4 9.5 7.75"
                                      stroke="currentColor"
                                      stroke-width="1.2"
                                      stroke-linecap="round"
                                      stroke-linejoin="round" />
                                  </svg>
                                </span>
                              </button>
                            @else
                              <a
                                class="inline-flex items-center gap-2 font-heading font-light text-base capitalize leading-6 text-white transition-colors hover:text-glowleaf culvers-focus-ring"
                                href="{{ esc_url($branch['url']) }}">
                                {{ $branch['title'] }}
                              </a>
                            @endif
                          </li>
                        @endforeach
                      </ul>
                    </nav>
                  @endif
                </div>

                <div class="mega-nav__bar-end relative z-20 flex shrink-0 items-center gap-2 lg:gap-[18px]">
                  <button
                    type="button"
                    class="relative flex size-[43px] shrink-0 items-center justify-center rounded-full bg-brand-500 text-faded-olive transition-transform hover:scale-[1.03] culvers-focus-ring-compact lg:hidden"
                    x-bind:aria-expanded="searchOpen ? 'true' : 'false'"
                    aria-controls="site-header-search"
                    x-on:click="openSearch()">
                    <span class="sr-only">{{ __('Open search', 'culvers') }}</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.6" />
                      <path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                  </button>

                  <div class="mega-nav__utilities hidden shrink-0 items-center lg:flex lg:gap-[18px]">
                  <a
                    class="inline-flex items-center gap-2 text-white transition-opacity hover:opacity-90 focus-visible:rounded-sm culvers-focus-ring"
                    href="{{ esc_url($mapUrl) }}">
                    <svg class="shrink-0" width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path
                        d="M9 3 3 8v12h6V3Zm6 0 6 5v12h-6V3Z"
                        stroke="currentColor"
                        stroke-width="1.4"
                        stroke-linejoin="round" />
                      <circle cx="12" cy="10" r="2.25" stroke="currentColor" stroke-width="1.4" />
                    </svg>
                    <span class="font-sans text-base font-normal leading-snug text-white">{{ __('Centre Map', 'culvers') }}</span>
                  </a>
                  <a
                    class="inline-flex items-center gap-2 text-white transition-opacity hover:opacity-90 focus-visible:rounded-sm culvers-focus-ring"
                    href="{{ esc_url($hereUrl) }}">
                    <svg class="shrink-0" width="13" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path
                        d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z"
                        stroke="currentColor"
                        stroke-width="1.4"
                        stroke-linejoin="round" />
                      <circle cx="12" cy="10" r="2.25" stroke="currentColor" stroke-width="1.4" />
                    </svg>
                    <span class="font-sans text-base font-normal leading-snug text-white">{{ __('Getting Here', 'culvers') }}</span>
                  </a>
                  <button
                    type="button"
                    class="relative flex size-[43px] shrink-0 items-center justify-center rounded-full bg-brand-500 text-deep-moss transition-transform hover:scale-[1.03] culvers-focus-ring-compact-white"
                    x-bind:aria-expanded="searchOpen ? 'true' : 'false'"
                    aria-controls="site-header-search"
                    x-on:click="openSearch()">
                    <span class="sr-only">{{ __('Open search', 'culvers') }}</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.6" />
                      <path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                  </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          @if($navTree !== [])
            @foreach($navTree as $branch)
              @if($branch['children'] !== [])
                @php
                  $firstPreview = '';
                  foreach ($branch['children'] as $child) {
                      if ($child['preview'] !== '') {
                          $firstPreview = $child['preview'];
                          break;
                      }
                  }
                  $fp = $firstPreview !== '' ? esc_url($firstPreview) : '';
                  $fpJs = json_encode($fp, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES);
                @endphp
                {{-- Panel anchor is full-width of `.mega-nav`; inner card stays `max-w-8xl`. --}}
                <div
                  id="mega-panel-{{ $branch['id'] }}"
                  class="mega-nav__panel absolute inset-x-0 top-[calc(100%+6px)] z-60"
                  x-show="megaOpenId === {{ $branch['id'] }}"
                  x-cloak
                  x-transition.opacity.duration.200ms
                  x-on:mouseenter="cancelCloseMegaHover()"
                  role="region"
                  aria-label="{{ esc_attr($branch['title']) }}">
                  <div
                    class="mega-nav__panel-inner mx-auto max-h-[min(85vh,560px)] w-full max-w-8xl overflow-y-auto rounded-2xl border border-light-brown/25 bg-lighter-cream px-5 py-8 shadow-lg md:px-8 lg:px-10 lg:pb-10 lg:pt-10">
                    {{-- Figma mega panel: ~40% text / ~60% preview; heading row is title + arrow flush right in the text column. --}}
                    <div
                      class="flex flex-col gap-10 lg:grid lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] lg:items-start lg:gap-x-12 lg:gap-y-0 xl:gap-x-16">
                      <div class="flex min-w-0 flex-col">
                        <div class="flex w-full items-center justify-between gap-4 lg:gap-6">
                          <h2 class="min-w-0 flex-1 font-heading text-4xl text-faded-olive">
                            {{ $branch['title'] }}
                          </h2>
                          @if($branch['url'] !== '' && $branch['url'] !== '#')
                            <a
                              class="flex size-[43px] shrink-0 items-center justify-center rounded-full bg-brand-500 text-deep-moss transition-transform hover:scale-[1.03] culvers-focus-ring-compact-faded-olive"
                              href="{{ esc_url($branch['url']) }}"
                              aria-label="{{ esc_attr(sprintf(/* translators: %s nav section title */ __('Explore %s', 'culvers'), $branch['title'])) }}">
                              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path
                                  d="M5 12h14m-6-6 6 6-6 6"
                                  stroke="currentColor"
                                  stroke-width="1.8"
                                  stroke-linecap="round"
                                  stroke-linejoin="round" />
                              </svg>
                            </a>
                          @endif
                        </div>
                        <hr class="mt-5 w-full border-0 border-t border-faded-olive/55" />
                        <ul
                          class="mt-[26px] flex max-w-[525px] flex-col gap-[18px]"
                          data-mega-parent-id="{{ $branch['id'] }}"
                          x-on:focusin="megaListFocusIn($event)">
                          @foreach($branch['children'] as $child)
                            @php
                              $childPv = trim((string) ($child['preview'] ?? ''));
                            @endphp
                            <li class="list-none">
                              <a
                                class="mega-nav__sublink inline-block font-sans text-2xl leading-6 text-faded-olive transition-colors hover:text-glowleaf focus-visible:rounded-sm culvers-focus-ring"
                                href="{{ esc_url($child['url']) }}"
                                data-preview-url="{{ $childPv !== '' ? esc_url($childPv) : '' }}"
                                x-on:mouseenter="megaSublinkEnter($event)"
                                x-on:focus="megaSublinkEnter($event)">
                                {{ $child['title'] }}
                              </a>
                            </li>
                          @endforeach
                        </ul>
                        <div class="mt-10 flex flex-wrap gap-[34px]">
                          <a
                            class="inline-flex items-center gap-2 font-sans text-xs font-semibold uppercase tracking-widest text-faded-olive transition-colors hover:text-glowleaf focus-visible:rounded-sm culvers-focus-ring"
                            href="{{ esc_url($instagramUrl) }}"
                            rel="noopener noreferrer">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                              <rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="1.3" />
                              <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.3" />
                              <circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" />
                            </svg>
                            {{ __('Instagram', 'culvers') }}
                          </a>
                          <a
                            class="inline-flex items-center gap-2 font-sans text-xs font-semibold uppercase tracking-widest text-faded-olive transition-colors hover:text-glowleaf focus-visible:rounded-sm culvers-focus-ring"
                            href="{{ esc_url($facebookUrl) }}"
                            rel="noopener noreferrer">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                              <path
                                d="M14 8h3V5h-3c-2.2 0-4 1.8-4 4v2H7v3h3v8h3v-8h3.2l.8-3H13v-2c0-.6.4-1 1-1Z" />
                            </svg>
                            {{ __('Facebook', 'culvers') }}
                          </a>
                        </div>
                      </div>
                      <div class="mega-nav__preview-col min-w-0 w-full">
                        <div class="relative aspect-[8/5] w-full overflow-hidden rounded-md bg-dustleaf/25">
                          <img
                            alt=""
                            class="absolute inset-0 size-full object-cover"
                            x-bind:key='"mega-preview-{{ $branch['id'] }}:" + (megaOpenId === {{ $branch['id'] }} ? (previewSrc || {!! $fpJs !!}) : {!! $fpJs !!})'
                            x-bind:src='megaOpenId === {{ $branch['id'] }} ? (previewSrc || {!! $fpJs !!}) : {!! $fpJs !!}'
                            x-bind:alt='megaOpenId === {{ $branch['id'] }} ? previewAlt : ""'
                            x-show='(megaOpenId === {{ $branch['id'] }} ? (previewSrc || {!! $fpJs !!}) : {!! $fpJs !!}).length > 0' />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              @endif
            @endforeach
          @endif

        </div>

        {{-- Search mode (same gutter / max-width pattern as mega bar). --}}
        <div
          id="site-header-search"
          class="site-header__search flex flex-col gap-2.5"
          x-show="searchOpen"
          x-cloak
          x-transition.opacity.duration.150ms>
          <div
            class="site-header__search-bar border-brand-500 bg-light-cream max-lg:rounded-none max-lg:border-0 max-lg:border-b-4 max-lg:border-glowleaf lg:rounded-full lg:border-4">
            <div class="site-header__search-gutter w-full max-lg:py-0 lg:py-0">
              <div
                class="site-header__search-row flex min-h-[75px] items-center gap-4 px-4 py-2 lg:min-h-[80px] lg:gap-8 lg:px-5 xl:px-6">
                <a class="shrink-0 text-deep-moss" href="{{ esc_url(home_url('/')) }}" rel="home" aria-label="{{ esc_attr(get_bloginfo('name')) }}">
                  @if(has_custom_logo())
                    <span class="block max-h-[28px] w-[178px] [&_img]:h-full [&_img]:w-auto [&_img]:object-contain [&_img]:object-left">
                      {!! get_custom_logo() !!}
                    </span>
                  @else
                    @include('partials.culver-square-logo', ['class' => 'block h-[22px] w-[178px] max-w-full text-deep-moss'])
                  @endif
                </a>
                <svg class="shrink-0 text-faded-olive" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.6" />
                  <path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                </svg>
                <label class="sr-only" for="site-search-input">{{ __('Search', 'culvers') }}</label>
                <input
                  id="site-search-input"
                  class="site-header__search-input min-w-0 flex-1 border-0 bg-transparent font-sans text-xl leading-snug text-faded-olive placeholder:text-faded-olive/55 focus:outline-none focus:ring-0 lg:text-2xl"
                  type="search"
                  name="s"
                  autocomplete="off"
                  placeholder="{{ esc_attr__('Search', 'culvers') }}"
                  x-model="searchQuery" />
                <button
                  type="button"
                  class="flex size-10 shrink-0 items-center justify-center rounded-full text-faded-olive hover:bg-faded-olive/10 culvers-focus-ring-compact-faded-olive"
                  x-on:click="closeSearch()"
                  aria-label="{{ esc_attr__('Close search', 'culvers') }}">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6 18 18M18 6 6 18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
          <div
            class="site-header__search-results max-h-[min(40vh,320px)] overflow-y-auto rounded-[14px] bg-light-cream px-6 py-5 shadow-md lg:px-10"
            x-show="searchResultsVisible"
            x-html="searchHtml"
            role="region"
            aria-live="polite"
            aria-relevant="additions text"
            aria-label="{{ esc_attr__('Search results', 'culvers') }}"></div>
        </div>

        </div>
      </div>
    </div>
  </div>
  {{-- /site-header__shell --}}
  </div>
  {{-- /site-header__chrome --}}

  {{-- Mobile menu — Figma: white full-screen, wordmark + lime close, drill-down panels, useful-link + social pill rows. --}}
  <div
    id="mega-mobile-drawer"
    class="mega-nav__drawer fixed inset-0 z-[100] flex h-[100svh] flex-col bg-white text-deep-moss lg:hidden"
    x-show="mobileOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-300 motion-reduce:transition-none"
    x-transition:enter-start="-translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transition ease-in duration-200 motion-reduce:transition-none"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="-translate-x-full opacity-0"
    role="dialog"
    aria-modal="true"
    aria-labelledby="mega-mobile-drawer-title">
    <h2 id="mega-mobile-drawer-title" class="sr-only">{{ __('Site menu', 'culvers') }}</h2>

    <div
      class="flex shrink-0 items-center justify-between gap-4 border-b border-deep-moss/10 px-5 pb-4 pt-[max(0.5rem,env(safe-area-inset-top))] sm:px-6">
      <a
        class="shrink-0 focus-visible:rounded-sm culvers-focus-ring"
        href="{{ esc_url(home_url('/')) }}"
        rel="home"
        aria-label="{{ esc_attr(get_bloginfo('name')) }}">
        @if(has_custom_logo())
          <span class="block max-h-[28px] w-[178px] max-w-[70vw] [&_img]:h-full [&_img]:w-auto [&_img]:object-contain [&_img]:object-left">
            {!! get_custom_logo() !!}
          </span>
        @else
          @include('partials.culver-square-logo', ['class' => 'block h-[22px] w-[178px] max-w-[70vw] text-deep-moss'])
        @endif
      </a>
      <button
        type="button"
        class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-glowleaf text-deep-moss transition-opacity hover:opacity-90 culvers-focus-ring"
        x-on:click="mobileOpen = false"
        aria-label="{{ esc_attr__('Close menu', 'culvers') }}">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path
            d="m6 6 12 12M18 6 6 18"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round" />
        </svg>
      </button>
    </div>

    <div class="relative min-h-0 flex-1 overflow-hidden">
      <div
        class="flex h-full min-h-0 w-[200%] transition-transform duration-300 ease-[cubic-bezier(0.33,1,0.68,1)] motion-reduce:transition-none"
        :class="mobileNavDepth === 0 ? 'translate-x-0' : '-translate-x-1/2'">
        {{-- Root panel --}}
        <div
          class="flex h-full w-1/2 min-w-[50%] flex-col overflow-y-auto overscroll-contain px-5 pb-10 pt-2 sm:px-6"
          id="mega-mobile-panel-root"
          :aria-hidden="mobileNavDepth === 1">
          <nav aria-label="{{ esc_attr__('Primary navigation', 'culvers') }}">
            @if($navTree !== [])
              <ul class="divide-y divide-deep-moss/10">
                @foreach($navTree as $idx => $branch)
                  <li class="list-none">
                    @if($branch['children'] !== [])
                      <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 py-5 text-left font-heading text-3xl leading-none text-deep-moss focus-visible:rounded-sm culvers-focus-ring-compact sm:text-4xl"
                        x-on:click="openMobileSubmenuByIndex({{ (int) $idx }})">
                        <span>{{ $branch['title'] }}</span>
                        <span class="inline-flex size-9 shrink-0 items-center justify-center text-deep-moss/50" aria-hidden="true">
                          <svg class="size-4" viewBox="0 0 24 24" fill="none">
                            <path
                              d="m9 6 6 6-6 6"
                              stroke="currentColor"
                              stroke-width="1.8"
                              stroke-linecap="round"
                              stroke-linejoin="round" />
                          </svg>
                        </span>
                      </button>
                    @else
                      <a
                        class="flex w-full items-center justify-between gap-4 py-5 font-heading text-3xl leading-none text-deep-moss focus-visible:rounded-sm culvers-focus-ring-compact sm:text-4xl"
                        href="{{ esc_url($branch['url']) }}">
                        <span>{{ $branch['title'] }}</span>
                        <span class="inline-flex size-9 shrink-0 items-center justify-center text-deep-moss/35" aria-hidden="true">
                          <svg class="size-4" viewBox="0 0 24 24" fill="none">
                            <path
                              d="m9 6 6 6-6 6"
                              stroke="currentColor"
                              stroke-width="1.8"
                              stroke-linecap="round"
                              stroke-linejoin="round" />
                          </svg>
                        </span>
                      </a>
                    @endif
                  </li>
                @endforeach
              </ul>
            @endif
          </nav>

          <div class="mt-10 border-t border-deep-moss/10 pt-8">
            <p class="font-sans text-xs font-semibold uppercase tracking-[0.2em] text-deep-moss/55">
              {{ __('Useful links', 'culvers') }}
            </p>
            {{-- Figma mobile nav: one pale-sage pill with two inline links, then one glowleaf pill for social (not four separate tiles). --}}
            <div class="mt-5 flex flex-col gap-3">
              <div
                class="flex min-h-12 divide-x divide-deep-moss/15 overflow-hidden rounded-full bg-light-green text-deep-moss">
                <a
                  class="flex flex-1 items-center gap-2.5 px-4 py-3.5 text-left font-sans text-sm font-medium leading-snug transition-colors hover:bg-deep-moss/[0.06] culvers-focus-ring-compact min-[360px]:gap-3 min-[360px]:px-5"
                  href="{{ esc_url($mapUrl) }}">
                  <svg class="size-5 shrink-0 text-deep-moss" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                      d="M4 6.75 12 3l8 3.75v8.5L12 21l-8-5.75v-8.5Z"
                      stroke="currentColor"
                      stroke-width="1.35"
                      stroke-linejoin="round" />
                    <path d="m9 9 2.25 2.25L15 7.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" />
                  </svg>
                  <span class="min-w-0">{{ __('Centre Map', 'culvers') }}</span>
                </a>
                <a
                  class="flex flex-1 items-center gap-2.5 px-4 py-3.5 text-left font-sans text-sm font-medium leading-snug transition-colors hover:bg-deep-moss/[0.06] culvers-focus-ring-compact min-[360px]:gap-3 min-[360px]:px-5"
                  href="{{ esc_url($hereUrl) }}">
                  <svg class="size-5 shrink-0 text-deep-moss" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                      d="M12 20s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z"
                      stroke="currentColor"
                      stroke-width="1.35"
                      stroke-linejoin="round" />
                    <circle cx="12" cy="9" r="2.25" stroke="currentColor" stroke-width="1.35" />
                  </svg>
                  <span class="min-w-0">{{ __('Getting Here', 'culvers') }}</span>
                </a>
              </div>

              <div
                class="flex min-h-12 divide-x divide-deep-moss/20 overflow-hidden rounded-full bg-glowleaf text-deep-moss">
                @if($instagramUrl !== '' && $instagramUrl !== '#')
                  <a
                    class="flex flex-1 items-center gap-2.5 px-4 py-3.5 text-left font-sans text-sm font-semibold uppercase leading-snug tracking-wide transition-colors hover:bg-deep-moss/[0.07] culvers-focus-ring-compact-deep-moss min-[360px]:gap-3 min-[360px]:px-5"
                    href="{{ esc_url($instagramUrl) }}"
                    target="_blank"
                    rel="noopener noreferrer">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="1.25" />
                      <circle cx="12" cy="12" r="3.2" stroke="currentColor" stroke-width="1.25" />
                      <circle cx="17.5" cy="6.5" r="1.1" fill="currentColor" />
                    </svg>
                    <span class="min-w-0">{{ __('Instagram', 'culvers') }}</span>
                  </a>
                @else
                  <span
                    class="flex flex-1 cursor-not-allowed items-center gap-2.5 px-4 py-3.5 text-left font-sans text-sm font-semibold uppercase leading-snug tracking-wide text-deep-moss/45 min-[360px]:gap-3 min-[360px]:px-5">
                    <svg class="size-5 shrink-0 opacity-50" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="1.25" />
                      <circle cx="12" cy="12" r="3.2" stroke="currentColor" stroke-width="1.25" />
                      <circle cx="17.5" cy="6.5" r="1.1" fill="currentColor" />
                    </svg>
                    <span class="min-w-0">{{ __('Instagram', 'culvers') }}</span>
                  </span>
                @endif
                @if($facebookUrl !== '' && $facebookUrl !== '#')
                  <a
                    class="flex flex-1 items-center gap-2.5 px-4 py-3.5 text-left font-sans text-sm font-semibold uppercase leading-snug tracking-wide transition-colors hover:bg-deep-moss/[0.07] culvers-focus-ring-compact-deep-moss min-[360px]:gap-3 min-[360px]:px-5"
                    href="{{ esc_url($facebookUrl) }}"
                    target="_blank"
                    rel="noopener noreferrer">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                      <path
                        d="M14 8h3V5h-3c-2.2 0-4 1.8-4 4v2H7v3h3v8h3v-8h3.2l.8-3H13v-2c0-.6.4-1 1-1Z" />
                    </svg>
                    <span class="min-w-0">{{ __('Facebook', 'culvers') }}</span>
                  </a>
                @else
                  <span
                    class="flex flex-1 cursor-not-allowed items-center gap-2.5 px-4 py-3.5 text-left font-sans text-sm font-semibold uppercase leading-snug tracking-wide text-deep-moss/45 min-[360px]:gap-3 min-[360px]:px-5">
                    <svg class="size-5 shrink-0 opacity-50" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                      <path
                        d="M14 8h3V5h-3c-2.2 0-4 1.8-4 4v2H7v3h3v8h3v-8h3.2l.8-3H13v-2c0-.6.4-1 1-1Z" />
                    </svg>
                    <span class="min-w-0">{{ __('Facebook', 'culvers') }}</span>
                  </span>
                @endif
              </div>
            </div>
            <button
              type="button"
              class="mt-6 font-sans text-sm font-semibold uppercase tracking-widest text-deep-moss/70 underline decoration-glowleaf decoration-2 underline-offset-4 hover:text-deep-moss focus-visible:rounded-sm culvers-focus-ring"
              x-on:click="openSearchFromMobile()">
              {{ __('Search', 'culvers') }}
            </button>
          </div>
        </div>

        {{-- Submenu panel --}}
        <div
          class="flex h-full w-1/2 min-w-[50%] flex-col overflow-y-auto overscroll-contain border-l border-deep-moss/10 px-5 pb-10 pt-2 sm:px-6"
          id="mega-mobile-panel-sub"
          :aria-hidden="mobileNavDepth === 0">
          <button
            type="button"
            class="mb-4 flex items-center gap-3 py-2 text-left focus-visible:rounded-sm culvers-focus-ring-compact"
            x-on:click="resetMobileSubmenu()">
            <span
              class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-glowleaf text-deep-moss"
              aria-hidden="true">
              <svg class="size-5" viewBox="0 0 24 24" fill="none">
                <path
                  d="m15 6-6 6 6 6"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round" />
              </svg>
            </span>
            <span class="font-sans text-xs font-bold uppercase tracking-[0.2em] text-deep-moss">{{ __('Back', 'culvers') }}</span>
          </button>

          <template x-if="mobileActiveBranch">
            <div class="min-h-0 flex-1">
              <div class="flex items-start justify-between gap-3 border-b border-deep-moss/10 pb-5">
                <h3 class="font-heading text-3xl leading-none text-deep-moss sm:text-4xl" x-text="mobileActiveBranch.title"></h3>
                <a
                  class="mt-1 inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-glowleaf text-deep-moss culvers-focus-ring-compact-deep-moss"
                  x-bind:href="mobileActiveBranch.url || '#'"
                  x-on:click="mobileOpen = false">
                  <span class="sr-only">{{ __('Open section', 'culvers') }}</span>
                  <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                      d="m9 6 6 6-6 6"
                      stroke="currentColor"
                      stroke-width="1.8"
                      stroke-linecap="round"
                      stroke-linejoin="round" />
                  </svg>
                </a>
              </div>
              <ul class="divide-y divide-deep-moss/10">
                <template x-for="(child, cIdx) in mobileActiveBranch.children" :key="(child.url || '') + '-' + cIdx">
                  <li class="list-none">
                    <a
                      class="block py-4 font-sans text-base font-normal leading-snug text-deep-moss focus-visible:rounded-sm culvers-focus-ring-compact"
                      x-bind:href="child.url"
                      x-on:click="mobileOpen = false"
                      x-text="child.title"></a>
                  </li>
                </template>
              </ul>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</header>
