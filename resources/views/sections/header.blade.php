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
  then `max-w-[1440px]` on the inner row — never both on one element.
--}}

<header
  class="site-header fixed inset-x-0 top-0 z-50"
  x-data="siteHeader"
  x-on:keydown.escape.window="closeAll()">
  {{-- Shell: constrained pill → full viewport width after scroll (md+). --}}
  <div
    class="site-header__shell w-full overflow-visible transition-[max-width,margin] duration-300 ease-in-out"
    x-bind:class="headerScrolled ? 'mx-0 max-w-none' : 'mx-auto max-w-[1440px]'">
    {{-- Entrance animation once the header intersects the viewport. --}}
    <div
      class="site-header__reveal transition-all duration-700 ease-out"
      x-bind:class="headerRevealed ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'">
      <div
        class="site-header__padding relative transition-[padding] duration-300 ease-in-out"
        x-bind:class="headerScrolled
          ? 'px-4 pb-3 pt-6 md:px-0 md:pb-0 md:pt-0'
          : 'px-4 pb-3 pt-6 md:px-[46px] md:pb-2.5 md:pt-[46px]'">

        {{-- Mega navigation mode --}}
        <div
          class="mega-nav relative flex flex-col gap-0"
          x-show="!searchOpen"
          x-cloak
          x-on:click.outside="closeMega()"
          x-on:mouseenter="cancelCloseMegaHover()"
          x-on:mouseleave="scheduleCloseMegaHover()">

          {{-- Dim layer: `pointer-events-none` so hover can exit to bridge/panel without trapping. --}}
          <div
            class="mega-nav__backdrop pointer-events-none fixed inset-0 z-[52] hidden bg-black/45 md:block"
            x-show="megaOpenId !== null"
            x-cloak
            x-transition.opacity.duration.200ms
            aria-hidden="true"></div>

          {{-- Olive bar (full bleed when scrolled; rounded pill when not). --}}
          <div
            class="mega-nav__bar relative z-[58] w-full rounded-full bg-faded-olive"
            x-bind:class="headerScrolled ? 'md:rounded-none' : ''">
            <div
              class="mega-nav__bar-gutter w-full py-2"
              x-bind:class="headerScrolled ? 'px-4 md:px-[46px]' : ''">
              <div
                class="mega-nav__bar-row flex min-h-[80px] w-full items-center gap-3 md:gap-6"
                x-bind:class="headerScrolled ? 'mx-auto max-w-[1440px]' : 'px-4 md:px-8 lg:pl-[33px] lg:pr-8'">
                <div class="mega-nav__bar-main flex min-w-0 flex-1 items-center md:gap-[42px]">
                  <a
                    class="mega-nav__logo shrink-0 text-glowleaf"
                    href="{{ esc_url(home_url('/')) }}"
                    rel="home"
                    aria-label="{{ esc_attr(get_bloginfo('name')) }}">
                    @if(has_custom_logo())
                      <span class="block max-h-[28px] w-[178px] [&_img]:h-full [&_img]:w-auto [&_img]:max-h-[28px] [&_img]:object-contain [&_img]:object-left">
                        {!! get_custom_logo() !!}
                      </span>
                    @else
                      @include('partials.culver-square-logo')
                    @endif
                  </a>

                  @if($navTree !== [])
                    <nav class="mega-nav__primary hidden flex-1 justify-start md:flex" aria-label="{{ esc_attr__('Primary', 'culvers') }}">
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
                                class="mega-nav__trigger inline-flex items-center gap-2 capitalize transition-colors hover:text-glowleaf focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-glowleaf"
                                x-bind:class="megaOpenId === {{ $branch['id'] }} ? 'text-glowleaf' : 'text-white'"
                                x-on:click.prevent="toggleMega({{ $branch['id'] }})"
                                x-bind:aria-expanded="megaOpenId === {{ $branch['id'] }} ? 'true' : 'false'"
                                aria-haspopup="true"
                                aria-controls="mega-panel-{{ $branch['id'] }}">
                                <span class="font-sans text-[15px] leading-6">{{ $branch['title'] }}</span>
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
                                class="inline-flex items-center gap-2 font-sans text-[15px] capitalize leading-6 text-white transition-colors hover:text-glowleaf focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-glowleaf"
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

                <button
                  type="button"
                  class="mega-nav__burger ml-auto inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-white/25 text-white md:ml-0 md:hidden"
                  aria-controls="mega-mobile-drawer"
                  x-bind:aria-expanded="mobileOpen ? 'true' : 'false'"
                  x-on:click="mobileOpen = !mobileOpen">
                  <span class="sr-only">{{ __('Open menu', 'culvers') }}</span>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                  </svg>
                </button>

                <div class="mega-nav__utilities hidden shrink-0 items-center md:flex md:gap-[18px]">
                  <a
                    class="inline-flex items-center gap-2 text-white transition-opacity hover:opacity-90"
                    href="{{ esc_url($mapUrl) }}">
                    <svg class="shrink-0" width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path
                        d="M9 3 3 8v12h6V3Zm6 0 6 5v12h-6V3Z"
                        stroke="currentColor"
                        stroke-width="1.4"
                        stroke-linejoin="round" />
                      <circle cx="12" cy="10" r="2.25" stroke="currentColor" stroke-width="1.4" />
                    </svg>
                    <span class="font-sans text-sm font-normal leading-snug text-white">{{ __('Centre Map', 'culvers') }}</span>
                  </a>
                  <a
                    class="inline-flex items-center gap-2 text-white transition-opacity hover:opacity-90"
                    href="{{ esc_url($hereUrl) }}">
                    <svg class="shrink-0" width="13" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path
                        d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z"
                        stroke="currentColor"
                        stroke-width="1.4"
                        stroke-linejoin="round" />
                      <circle cx="12" cy="10" r="2.25" stroke="currentColor" stroke-width="1.4" />
                    </svg>
                    <span class="font-sans text-sm font-normal leading-snug text-white">{{ __('Getting Here', 'culvers') }}</span>
                  </a>
                  <button
                    type="button"
                    class="relative flex size-[43px] shrink-0 items-center justify-center rounded-full bg-brand-500 text-deep-moss transition-transform hover:scale-[1.03] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
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

          {{-- Pointer bridge: fills gap under bar so mega stays open while moving to panel. --}}
          <div
            class="mega-nav__hover-bridge pointer-events-auto absolute inset-x-0 top-full z-[54] hidden min-h-[calc(100vh-5rem)] md:block"
            x-show="megaOpenId !== null"
            x-cloak
            x-transition.opacity.duration.200ms
            x-on:click="closeMega()"
            x-on:mouseenter="cancelCloseMegaHover()"
            aria-hidden="true"></div>

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
                  $fpJson = json_encode($fp, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES);
                @endphp
                <div
                  id="mega-panel-{{ $branch['id'] }}"
                  class="mega-nav__panel absolute left-1/2 top-[calc(100%+18px)] z-[60] w-[min(1348px,calc(100vw-2rem))] -translate-x-1/2"
                  x-show="megaOpenId === {{ $branch['id'] }}"
                  x-cloak
                  x-transition.opacity.duration.200ms
                  role="region"
                  aria-label="{{ esc_attr($branch['title']) }}">
                  <div
                    class="mega-nav__panel-inner mx-auto max-h-[min(85vh,560px)] max-w-[1348px] overflow-y-auto rounded-t-2xl rounded-b-2xl border border-light-brown/25 bg-lighter-cream px-5 py-8 shadow-lg md:px-8 lg:px-10 lg:pb-10 lg:pt-10">
                    <div class="flex flex-col gap-10 lg:flex-row lg:items-start lg:gap-12 xl:gap-16">
                      <div class="flex min-w-0 flex-1 flex-col">
                        <div class="flex flex-wrap items-start gap-4 lg:gap-6">
                          <h2 class="font-heading text-[40px] leading-[1.1] text-faded-olive">
                            {{ $branch['title'] }}
                          </h2>
                          @if($branch['url'] !== '' && $branch['url'] !== '#')
                            <a
                              class="flex size-[43px] shrink-0 items-center justify-center rounded-full bg-brand-500 text-deep-moss transition-transform hover:scale-[1.03] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-faded-olive"
                              href="{{ esc_url($branch['url']) }}"
                              aria-label="{{ esc_attr(__('Explore', 'culvers')) }}">
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
                        <hr class="mt-5 max-w-[525px] border-light-brown/35" />
                        <ul class="mt-[26px] flex max-w-[525px] flex-col gap-[18px]">
                          @foreach($branch['children'] as $child)
                            <li class="list-none">
                              <a
                                class="mega-nav__sublink inline-block font-sans text-[22px] leading-6 text-faded-olive transition-colors hover:text-deep-moss"
                                href="{{ esc_url($child['url']) }}"
                                @if($child['preview'] !== '') data-preview-url="{{ esc_url($child['preview']) }}" @endif
                                x-on:mouseenter="setPreviewFromEvent($event)">
                                {{ $child['title'] }}
                              </a>
                            </li>
                          @endforeach
                        </ul>
                        <div class="mt-10 flex flex-wrap gap-[34px]">
                          <a
                            class="inline-flex items-center gap-2 font-sans text-xs font-semibold uppercase tracking-[1px] text-faded-olive hover:text-deep-moss"
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
                            class="inline-flex items-center gap-2 font-sans text-xs font-semibold uppercase tracking-[1px] text-faded-olive hover:text-deep-moss"
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
                      <div class="mega-nav__preview-col w-full shrink-0 lg:w-[48%] lg:max-w-[734px]">
                        <div class="relative aspect-[734/458] w-full overflow-hidden rounded-md bg-dustleaf/25">
                          <img
                            alt=""
                            class="absolute inset-0 size-full object-cover"
                            x-bind:src="megaOpenId === {{ $branch['id'] }} ? (previewSrc || {!! $fpJson !!}) : {!! $fpJson !!}"
                            x-bind:alt="megaOpenId === {{ $branch['id'] }} ? previewAlt : ''"
                            x-show="(megaOpenId === {{ $branch['id'] }} ? (previewSrc || {!! $fpJson !!}) : {!! $fpJson !!}).length > 0" />
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
            class="site-header__search-bar rounded-full border-4 border-brand-500 bg-light-cream"
            x-bind:class="headerScrolled ? 'md:rounded-none' : ''">
            <div class="site-header__search-gutter w-full" x-bind:class="headerScrolled ? 'px-4 md:px-[46px]' : ''">
              <div
                class="site-header__search-row flex min-h-[80px] items-center gap-4 py-2 md:gap-8"
                x-bind:class="headerScrolled ? 'mx-auto max-w-[1440px]' : 'px-4 md:px-8'">
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
                  class="site-header__search-input min-w-0 flex-1 border-0 bg-transparent font-sans text-lg leading-snug text-faded-olive placeholder:text-faded-olive/55 focus:outline-none focus:ring-0 md:text-xl"
                  type="search"
                  name="s"
                  autocomplete="off"
                  placeholder="{{ esc_attr__('Search', 'culvers') }}"
                  x-model="searchQuery" />
                <button
                  type="button"
                  class="flex size-10 shrink-0 items-center justify-center rounded-full text-faded-olive hover:bg-faded-olive/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-faded-olive"
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
            class="site-header__search-results max-h-[min(40vh,320px)] overflow-y-auto rounded-[14px] bg-light-cream px-6 py-5 shadow-md md:px-10"
            x-show="searchResultsVisible"
            x-html="searchHtml"
            role="listbox"
            aria-label="{{ esc_attr__('Search results', 'culvers') }}"></div>
        </div>
      </div>
    </div>

    {{-- Mobile primary nav --}}
    <div
      id="mega-mobile-drawer"
      class="mega-nav__drawer fixed inset-x-0 bottom-0 top-[72px] z-[70] overflow-y-auto bg-deep-moss p-6 text-white md:hidden"
      x-show="mobileOpen"
      x-cloak
      x-transition.opacity.duration.150ms>
      <div class="flex justify-end">
        <button type="button" class="rounded-full border border-white/30 px-4 py-2 text-sm" x-on:click="mobileOpen = false">
          {{ __('Close', 'culvers') }}
        </button>
      </div>
      @if($navTree !== [])
        <ul class="mt-6 space-y-4">
          @foreach($navTree as $branch)
            <li class="list-none border-b border-white/15 pb-4">
              @if($branch['children'] !== [])
                <p class="font-heading text-lg text-glowleaf">{{ $branch['title'] }}</p>
                <ul class="mt-3 space-y-2 pl-1">
                  @foreach($branch['children'] as $child)
                    <li class="list-none">
                      <a class="block py-1 font-sans text-base text-white/90" href="{{ esc_url($child['url']) }}">
                        {{ $child['title'] }}
                      </a>
                    </li>
                  @endforeach
                </ul>
              @else
                <a class="block font-heading text-lg text-glowleaf" href="{{ esc_url($branch['url']) }}">
                  {{ $branch['title'] }}
                </a>
              @endif
            </li>
          @endforeach
        </ul>
      @endif
      <div class="mt-8 flex flex-col gap-3 border-t border-white/15 pt-6">
        <a class="font-sans text-sm" href="{{ esc_url($mapUrl) }}">{{ __('Centre Map', 'culvers') }}</a>
        <a class="font-sans text-sm" href="{{ esc_url($hereUrl) }}">{{ __('Getting Here', 'culvers') }}</a>
        <button type="button" class="text-left font-sans text-sm text-glowleaf" x-on:click="openSearchFromMobile()">
          {{ __('Search', 'culvers') }}
        </button>
      </div>
    </div>
  </div>
</header>
