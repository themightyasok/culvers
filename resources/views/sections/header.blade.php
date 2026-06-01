@php
  use App\Nav\PrimaryNav;
  use App\Nav\PrimaryNavLinkSync;

  /** @var list<array{id:int,title:string,url:string,is_current:bool,children:list<array{title:string,url:string,preview:string}>}> $navTree */
  $navTree = PrimaryNav::tree('primary_navigation');

  // Header utility pills — always deep-link to in-page anchors (same as mega submenu).
  $mapUrl = PrimaryNavLinkSync::headerCentreMapUrl();
  $hereUrl = PrimaryNavLinkSync::headerGettingHereUrl();
  $instagramUrl = get_theme_mod('culvers_instagram_url', '#');
  $facebookUrl = get_theme_mod('culvers_facebook_url', '#');

  /*
   * Header wordmark — Figma Mobile Nav filled mark (`2:988`) at every breakpoint.
   * Mobile nav: 46×205; desktop bar + search: 22×178 (wrapper max-h 28px).
   */
  $headerLogoSvgClass = 'block h-[46px] w-auto max-w-[min(100%,205px)] shrink-0 text-current lg:h-[22px] lg:max-w-[min(100%,178px)] [&_svg]:max-h-full';
  $headerLogoWrapClass = 'flex h-[46px] w-[205px] max-w-[min(100%,205px)] items-center lg:h-[22px] lg:max-h-[28px] lg:w-[178px] lg:max-w-[min(100%,178px)] [&_svg]:h-full [&_svg]:max-w-full [&_svg]:object-contain [&_svg]:object-center lg:[&_svg]:object-left';
  $headerLogoImgWrapClass = 'block h-[46px] w-[205px] max-w-[min(100%,205px)] lg:h-[22px] lg:max-h-[28px] lg:w-[178px] lg:max-w-[min(100%,178px)] [&_img]:h-full [&_img]:w-auto [&_img]:max-h-full [&_img]:object-contain [&_img]:object-center lg:[&_img]:object-left';
  $headerLogoPartialClass = 'block h-[46px] w-[205px] max-w-[min(100%,205px)] shrink-0 text-glowleaf lg:h-[22px] lg:w-[178px] lg:max-w-[min(100%,178px)] lg:max-w-full [&_svg]:max-h-full';
  $headerLogoDesktopWrapClass = 'flex max-h-[28px] w-[178px] max-w-full items-center [&_svg]:max-h-full [&_svg]:max-w-full [&_svg]:object-contain [&_svg]:object-left';
  $headerLogoDesktopImgWrapClass = 'block max-h-[28px] w-[178px] [&_img]:h-full [&_img]:w-auto [&_img]:max-h-[28px] [&_img]:object-contain [&_img]:object-left';
  $headerLogoDesktopPartialClass = 'block h-[22px] w-[178px] max-w-full text-deep-moss';

  $headerWordmarkFromFile = static function (string $absolutePath, string $svgClass): string {
      if (! is_readable($absolutePath)) {
          return '';
      }

      $raw = (string) file_get_contents($absolutePath);
      if ($raw === '') {
          return '';
      }

      return (string) preg_replace(
          '/<svg\b/',
          '<svg class="' . esc_attr($svgClass) . '" aria-hidden="true" focusable="false"',
          $raw,
          1,
      );
  };

  $headerWordmarkSvg = $headerWordmarkFromFile(
      get_template_directory() . '/resources/images/brand/culver-square-wordmark.svg',
      $headerLogoSvgClass,
  );
  $headerHasWordmark = $headerWordmarkSvg !== '';
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
  {{-- Figma menu-open frame `72:5031`: scrim layer `72:5036` (30% black) over hero that already has 30% — see `.site-header__menu-scrim` in app.css. --}}
  <div
    class="site-header__menu-scrim fixed inset-0 z-[1] hidden motion-reduce:transition-none lg:block"
    x-show="megaOpenId !== null"
    x-cloak
    x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none motion-reduce:duration-0"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none motion-reduce:duration-0"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-on:click="closeMega()"
    aria-hidden="true"></div>
  {{--
    Scroll-hide transform lives on `.site-header__chrome` only. The mobile drawer stays a direct
    child of `<header>` so `position:fixed` resolves to the viewport (transform ancestors would clip it).
  --}}
  <div
    class="site-header__chrome relative z-10 will-change-transform motion-reduce:transition-none"
    x-ref="headerChrome"
    x-bind:class="[
      headerDockHidden ? '-translate-y-full pointer-events-none' : 'translate-y-0',
      headerDockTransitionEnabled ? 'transition-transform duration-700 ease-[cubic-bezier(0.33,1,0.68,1)]' : '',
    ]">
  {{-- Shell: full width; content width matches footer (`lg:px-12` + inner `max-w-8xl`). --}}
  <div class="site-header__shell w-full overflow-visible">
    {{-- Entrance animation once the header intersects the viewport. --}}
    <div
      class="site-header__reveal max-lg:translate-y-0 max-lg:opacity-100 lg:transition-all lg:duration-700 lg:ease-out"
      x-bind:class="headerRevealed ? 'translate-y-0 opacity-100' : 'lg:translate-y-8 lg:opacity-0'">
      {{-- Figma: pill sits inside `pt-[46px] px-[46px] pb-[10px]` from the frame edge.
           `lg:px-12` (48px) is +2px, kept stock; the top is the visible spec. --}}
      <div
        class="site-header__padding relative transition-[padding] duration-300 ease-in-out max-lg:px-0 max-lg:py-0 lg:px-12 lg:pb-2.5 lg:pt-[46px]">

        <div class="mx-auto w-full max-w-8xl">
        {{--
          Search overlays the mega bar instead of swapping `x-show` siblings (which reflowed the
          chrome, fought `--site-header-offset` measurement, and read as two bars replacing).
        --}}
        <div class="relative isolate w-full">

        {{-- Mega navigation mode — stays in-flow for stable height while search is `absolute`. --}}
        <div
          class="mega-nav relative flex flex-col gap-0"
          x-bind:inert="searchOpen"
          x-on:click.outside="closeMega()"
          x-on:mouseenter="cancelCloseMegaHover()"
          x-on:mouseleave="scheduleCloseMegaHover()">

          {{-- Olive bar — fixed chrome width (max-w-8xl parent); scroll only hides the dock, no morph. --}}
          {{-- Mobile / tablet (<lg): Figma Mobile — Nav (51:8865 → component 2:988): 430×75 bar #4F5438;
               burger 2:1016 = 36×20px glyph @ left 5.58%; search pill 2:990 = 39×39px #D4FF50 @ right 5.58%
               with 14.512×14.33px magnifier #4F5438 (assets: resources/images/header/mobile-*.svg). --}}
          <div
            class="mega-nav__bar relative z-50 w-full max-lg:rounded-none max-lg:border-b lg:rounded-full"
            x-bind:class="searchOpen ? 'bg-transparent max-lg:border-b-transparent' : 'bg-faded-olive max-lg:border-glowleaf'">
            {{-- No vertical padding on the gutter — Figma pill is exactly 80px tall (matches the
                 inner row's `lg:min-h-[80px]`). Previous `py-2` inflated the pill to 96px. --}}
            <div class="mega-nav__bar-gutter w-full max-lg:py-0">
              <div
                class="mega-nav__bar-row relative flex h-[75px] min-h-[75px] w-full items-center gap-3 px-4 max-lg:gap-2 lg:h-auto lg:min-h-[80px] lg:gap-6 lg:px-5 xl:px-6">
                <button
                  type="button"
                  class="mega-nav__burger relative z-20 inline-flex h-5 w-9 shrink-0 items-center justify-center self-center text-glowleaf before:absolute before:-inset-3 before:content-[''] max-lg:-ms-0.5 lg:hidden culvers-focus-ring"
                  aria-controls="mega-mobile-drawer"
                  x-bind:aria-expanded="mobileOpen ? 'true' : 'false'"
                  x-show="!searchOpen"
                  x-cloak
                  x-on:click="mobileOpen = !mobileOpen">
                  <span class="sr-only" x-show="!mobileOpen" x-cloak>{{ __('Open menu', 'culvers') }}</span>
                  <span class="sr-only" x-show="mobileOpen" x-cloak>{{ __('Close menu', 'culvers') }}</span>
                  @include('partials.icons.header-mobile-menu')
                </button>
                <div
                  class="mega-nav__bar-main pointer-events-none flex min-w-0 flex-1 items-center lg:pointer-events-auto lg:gap-[42px]">
                  <a
                    class="mega-nav__logo shrink-0 text-glowleaf max-lg:pointer-events-auto max-lg:absolute max-lg:left-1/2 max-lg:top-1/2 max-lg:z-10 max-lg:-translate-x-1/2 max-lg:-translate-y-1/2 lg:static lg:translate-x-0 lg:translate-y-0"
                    href="{{ esc_url(home_url('/')) }}"
                    rel="home"
                    aria-label="{{ esc_attr(get_bloginfo('name')) }}"
                    x-show="!searchOpen"
                    x-cloak>
                    @if($headerHasWordmark)
                      <span class="{{ $headerLogoWrapClass }}">{!! $headerWordmarkSvg !!}</span>
                    @elseif(has_custom_logo())
                      <span class="{{ $headerLogoImgWrapClass }}">
                        {!! get_custom_logo() !!}
                      </span>
                    @else
                      @include('partials.culver-square-logo', ['class' => $headerLogoPartialClass])
                    @endif
                  </a>

                  @if($navTree !== [])
                    <nav class="mega-nav__primary hidden flex-1 justify-start lg:flex" aria-label="{{ esc_attr__('Primary', 'culvers') }}">
                      <ul class="flex flex-wrap items-center gap-x-[30px] gap-y-2">
                        @foreach($navTree as $branch)
                          @php
                            $hasMega = $branch['children'] !== [];
                            $isCurrent = ! empty($branch['is_current']);
                          @endphp
                          <li
                            class="group/top-item mega-nav__top-item list-none{{ $isCurrent ? ' mega-nav__top-item--current' : '' }}"
                            @if($hasMega)
                              x-on:mouseenter="openMegaFromHover({{ $branch['id'] }})"
                            @endif>
                            @if($hasMega)
                              <button
                                type="button"
                                class="mega-nav__trigger inline-flex items-center gap-2 capitalize text-white transition-colors group-hover/top-item:text-glowleaf group-focus-within/top-item:text-glowleaf culvers-focus-ring{{ $isCurrent ? ' !text-glowleaf' : '' }}"
                                x-bind:class="megaOpenId === {{ $branch['id'] }} ? '!text-glowleaf' : ''"
                                x-on:click.prevent="toggleMega({{ $branch['id'] }})"
                                x-bind:aria-expanded="megaOpenId === {{ $branch['id'] }} ? 'true' : 'false'"
                                @if($isCurrent) aria-current="page" @endif
                                aria-haspopup="true"
                                aria-controls="mega-panel-{{ $branch['id'] }}">
                                {{-- Figma Menu: default = white + ▼ (`2:94` + rotate-90). Hover + open/current = glowleaf + diamond (`2:262`). --}}
                                <span class="font-sans font-medium text-base leading-6">{{ $branch['title'] }}</span>
                                <span
                                  class="mega-nav__marker relative ms-0.5 inline-flex size-2 shrink-0 items-center justify-center"
                                  aria-hidden="true">
                                  <span
                                    class="mega-nav__marker-chevron absolute inset-0 flex items-center justify-center opacity-100 transition-opacity duration-150 group-hover/top-item:opacity-0 group-focus-within/top-item:opacity-0{{ $isCurrent ? ' opacity-0' : '' }}"
                                    x-bind:class="megaOpenId === {{ $branch['id'] }} ? 'opacity-0' : ''">
                                    <span class="inline-flex h-1 w-[7px] items-center justify-center">
                                      <span class="inline-flex rotate-90">
                                        @include('partials.icons.figma-header-icon', [
                                            'header_icon_variant' => 'nav-chevron',
                                            'header_icon_class' => 'block h-[7px] w-1 shrink-0',
                                        ])
                                      </span>
                                    </span>
                                  </span>
                                  <span
                                    class="mega-nav__marker-diamond absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-150 group-hover/top-item:opacity-100 group-focus-within/top-item:opacity-100{{ $isCurrent ? ' opacity-100' : '' }}"
                                    x-bind:class="megaOpenId === {{ $branch['id'] }} ? 'opacity-100' : ''">
                                    <span class="size-[5.657px] shrink-0 rotate-45 bg-glowleaf" aria-hidden="true"></span>
                                  </span>
                                </span>
                              </button>
                            @else
                              <a
                                class="inline-flex items-center gap-2 font-sans font-medium text-base capitalize leading-6 transition-colors culvers-focus-ring{{ $isCurrent ? ' text-glowleaf' : ' text-white hover:text-glowleaf' }}"
                                href="{{ esc_url($branch['url']) }}"
                                @if($isCurrent) aria-current="page" @endif>
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
                    class="mega-nav__search-mobile relative flex size-[32px] shrink-0 items-center justify-center rounded-full bg-glowleaf p-0 culvers-focus-ring-compact lg:hidden"
                    x-bind:aria-expanded="searchOpen ? 'true' : 'false'"
                    aria-controls="site-header-search"
                    x-show="!searchOpen"
                    x-cloak
                    x-on:click="openSearch()">
                    <span class="sr-only">{{ __('Open search', 'culvers') }}</span>
                    @include('partials.icons.header-mobile-search')
                  </button>

                  <div class="mega-nav__utilities hidden shrink-0 items-center lg:flex lg:gap-[18px]">
                  <a
                    class="inline-flex items-center gap-2 text-white transition-opacity hover:opacity-90 focus-visible:rounded-sm culvers-focus-ring"
                    href="{{ esc_url($mapUrl) }}"
                    x-on:click="headerUtilityClick($event)">
                    @include('partials.icons.figma-header-icon', [
                        'header_icon_variant' => 'centre-map-desktop',
                        'header_icon_class' => 'size-[15px] shrink-0',
                    ])
                    <span class="font-sans text-sm font-medium leading-[22px] text-white">{{ __('Centre Map', 'culvers') }}</span>
                  </a>
                  <a
                    class="inline-flex items-center gap-2 text-white transition-opacity hover:opacity-90 focus-visible:rounded-sm culvers-focus-ring"
                    href="{{ esc_url($hereUrl) }}"
                    x-on:click="headerUtilityClick($event)">
                    @include('partials.icons.figma-header-icon', [
                        'header_icon_variant' => 'getting-here-desktop',
                        'header_icon_class' => 'h-4 w-[13px] shrink-0',
                    ])
                    <span class="font-sans text-sm font-medium leading-[22px] text-white">{{ __('Getting Here', 'culvers') }}</span>
                  </a>
                  <button
                    type="button"
                    class="relative flex size-[43px] shrink-0 items-center justify-center rounded-full bg-brand-500 text-deep-moss transition-transform hover:scale-[1.03] culvers-focus-ring-compact-white"
                    x-bind:aria-expanded="searchOpen ? 'true' : 'false'"
                    aria-controls="site-header-search"
                    x-show="!searchOpen"
                    x-cloak
                    x-on:click="openSearch()">
                    <span class="sr-only">{{ __('Open search', 'culvers') }}</span>
                    @include('partials.icons.figma-header-icon', [
                        'header_icon_variant' => 'search-magnifier-desktop',
                        'header_icon_class' => 'size-[20px] shrink-0',
                    ])
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
                  /* Fallback alt when the hovered sublink has no `previewAlt`. Uses the branch
                     title (e.g. "Shop") so the preview image is never alt-empty while visible —
                     WCAG SC 1.1.1: non-decorative imagery must carry a text alternative. */
                  $branchAltJs = json_encode(
                      (string) ($branch['title'] ?? ''),
                      JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES
                  );
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
                    class="mega-nav__panel-inner mx-auto w-full max-w-8xl overflow-visible rounded-2xl border border-light-brown/25 bg-lighter-cream px-5 py-8 shadow-lg md:px-8 lg:px-10 lg:pb-10 lg:pt-10">
                    {{-- Figma mega panel: ~40% text / ~60% preview; no inner scroll — panel grows with links (Sheet: dropdowns stay scrollbar-free). --}}
                    <div
                      class="flex flex-col gap-10 lg:grid lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] lg:items-stretch lg:gap-x-12 lg:gap-y-0 xl:gap-x-16">
                      {{-- Left column: list fills from top; socials pin to panel bottom (~Figma 72:4994 inset). --}}
                      <div class="flex min-h-0 w-full flex-col lg:justify-between">
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
                              @include('partials.icons.figma-header-icon', [
                                  'header_icon_variant' => 'explore-arrow',
                                  'header_icon_class' => 'size-4 shrink-0',
                              ])
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
                                class="mega-nav__sublink inline-block font-sans text-2xl leading-6 focus-visible:rounded-sm culvers-focus-ring"
                                href="{{ esc_url($child['url']) }}"
                                data-preview-url="{{ $childPv !== '' ? esc_url($childPv) : '' }}"
                                x-on:click="megaSublinkClick($event)"
                                x-on:mouseenter="megaSublinkEnter($event)"
                                x-on:focus="megaSublinkEnter($event)">
                                {{ $child['title'] }}
                              </a>
                            </li>
                          @endforeach
                        </ul>
                        </div>
                        {{-- Figma Culver Square — Dropdown Menu Shop (72:4994–72:5001): Commuter SemiBold 12 / 1px tracking; 6px icon–label; 34px between marks. --}}
                        <div class="mt-10 flex shrink-0 flex-wrap gap-[34px] pt-6 lg:mt-auto lg:pt-10 lg:pb-2">
                          <a
                            class="mega-nav__social-link inline-flex cursor-pointer items-center gap-1.5 font-label text-xs font-semibold uppercase tracking-[0.0625rem] focus-visible:rounded-sm culvers-focus-ring"
                            href="{{ esc_url($instagramUrl) }}"
                            rel="noopener noreferrer">
                            @include('partials.figma-social-icon', [
                                'social_icon_variant' => 'instagram',
                                'social_icon_class' => 'size-[14px] shrink-0 overflow-visible text-current transition-colors',
                            ])
                            {{ __('Instagram', 'culvers') }}
                          </a>
                          <a
                            class="mega-nav__social-link inline-flex cursor-pointer items-center gap-1.5 font-label text-xs font-semibold uppercase tracking-[0.0625rem] focus-visible:rounded-sm culvers-focus-ring"
                            href="{{ esc_url($facebookUrl) }}"
                            rel="noopener noreferrer">
                            @include('partials.figma-social-icon', [
                                'social_icon_variant' => 'facebook',
                                'social_icon_class' => 'size-[15px] shrink-0 text-current transition-colors',
                            ])
                            {{ __('Facebook', 'culvers') }}
                          </a>
                        </div>
                      </div>
                      <div class="mega-nav__preview-col flex min-h-0 min-w-0 w-full lg:items-stretch">
                        <div class="relative aspect-[8/5] w-full overflow-hidden rounded-md bg-dustleaf/25">
                          <img
                            alt=""
                            class="absolute inset-0 size-full object-cover"
                            x-bind:key='"mega-preview-{{ $branch['id'] }}:" + (megaOpenId === {{ $branch['id'] }} ? (previewSrc || {!! $fpJs !!}) : {!! $fpJs !!})'
                            x-bind:src='megaOpenId === {{ $branch['id'] }} ? (previewSrc || {!! $fpJs !!}) : {!! $fpJs !!}'
                            x-bind:alt='megaOpenId === {{ $branch['id'] }} ? (previewAlt || {!! $branchAltJs !!}) : ""'
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

        {{-- Search overlays mega bar (+ optional results below); opaque bar masks inactive nav underneath. --}}
        <div
          id="site-header-search"
          class="site-header__search absolute left-0 right-0 top-0 z-[70] flex flex-col gap-2.5"
          x-show="searchOpen"
          x-cloak
          x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none motion-reduce:duration-0"
          x-transition:enter-start="opacity-0"
          x-transition:enter-end="opacity-100"
          x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none motion-reduce:duration-0"
          x-transition:leave-start="opacity-100"
          x-transition:leave-end="opacity-0"
          role="dialog"
          aria-modal="true"
          x-on:click.outside="closeSearch()"
          aria-label="{{ esc_attr__('Site search', 'culvers') }}">
          <div
            class="site-header__search-bar w-full border-brand-500 bg-light-cream max-lg:min-h-[75px] max-lg:rounded-none max-lg:border-0 max-lg:border-b-4 max-lg:border-glowleaf lg:rounded-full lg:border-4">
            <div class="site-header__search-gutter w-full max-lg:py-0 lg:py-0">
              <form
                method="get"
                action="{{ esc_url(home_url('/')) }}"
                role="search"
                class="site-header__search-row flex min-h-[75px] w-full items-center gap-3 px-4 py-2 max-lg:gap-3 lg:min-h-[80px] lg:gap-8 lg:px-5 xl:px-6"
                x-on:submit="closeSearch()">
                {{-- Mobile search is full-width input + close; logo stays on desktop search bar only. --}}
                <a class="max-lg:hidden shrink-0 text-deep-moss" href="{{ esc_url(home_url('/')) }}" rel="home" aria-label="{{ esc_attr(get_bloginfo('name')) }}">
                  @if($headerHasWordmark)
                    <span class="{{ $headerLogoDesktopWrapClass }}">{!! $headerWordmarkSvg !!}</span>
                  @elseif(has_custom_logo())
                    <span class="{{ $headerLogoDesktopImgWrapClass }}">
                      {!! get_custom_logo() !!}
                    </span>
                  @else
                    @include('partials.culver-square-logo', ['class' => $headerLogoDesktopPartialClass])
                  @endif
                </a>
                <svg class="shrink-0 text-faded-olive" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.6" />
                  <path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                </svg>
                <label class="sr-only" for="site-search-input">{{ __('Search', 'culvers') }}</label>
                {{-- Figma `51:7110`: Halyard Display Book 20 / lh 1.3 / Faded Olive (#4F5438). --}}
                <input
                  id="site-search-input"
                  class="site-header__search-input min-w-0 flex-1 border-0 bg-transparent font-sans text-xl font-light leading-[1.3] text-faded-olive placeholder:text-faded-olive/55 focus:outline-none focus:ring-0"
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
              </form>
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

        </div>{{-- /.relative.site-header-shell-slot --}}

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
    {{-- Figma `51:9052` (mobile drawer): Lighter Cream surface (#FFFEFA), Faded Olive text. --}}
    class="mega-nav__drawer fixed inset-0 z-[100] flex h-[100svh] flex-col bg-lighter-cream text-faded-olive lg:hidden"
    x-show="mobileOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-300 motion-reduce:transition-none"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    role="dialog"
    aria-modal="true"
    aria-labelledby="mega-mobile-drawer-title">
    <h2 id="mega-mobile-drawer-title" class="sr-only">{{ __('Site menu', 'culvers') }}</h2>

    <div
      class="flex shrink-0 items-center justify-between gap-4 border-b border-faded-olive/15 px-5 py-4 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6">
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
        class="flex h-full min-h-0 w-[200%] ease-[cubic-bezier(0.33,1,0.68,1)] motion-reduce:transition-none"
        x-bind:class="[
          mobileNavDepth === 0 ? 'translate-x-0' : '-translate-x-1/2',
          mobileNavAnimate ? 'transition-transform duration-300' : 'transition-none',
        ]">
        {{-- Root panel — sheet feedback row 5: tighter horizontal padding (text closer to edges)
             + bottom block pinned to bottom of the panel (Useful Links + socials). --}}
        <div
          class="flex h-full w-1/2 min-w-[50%] flex-col overflow-y-auto overscroll-contain px-4 pb-6 pt-2 sm:px-5"
          id="mega-mobile-panel-root"
          :aria-hidden="mobileNavDepth === 1">
          <nav aria-label="{{ esc_attr__('Primary navigation', 'culvers') }}">
            @if($navTree !== [])
              <ul class="divide-y divide-faded-olive/15">
                @foreach($navTree as $idx => $branch)
                  @php $mobileCurrent = ! empty($branch['is_current']); @endphp
                  <li class="list-none">
                    {{-- Figma `51:9052`: Canela Regular 36 / lh 1.1 / Faded Olive (H2 Mobile token);
                         rows separated by 1 px Faded Olive 15% dividers (matches `divide-y` above). --}}
                    @if($branch['children'] !== [])
                      <button
                        type="button"
                        class="mega-nav__mobile-root-link flex w-full items-center justify-between gap-4 py-5 text-left font-heading text-4xl leading-[1.1] focus-visible:rounded-sm culvers-focus-ring-compact{{ $mobileCurrent ? ' mega-nav__mobile-root-link--current' : '' }}"
                        x-on:click="openMobileSubmenuByIndex({{ (int) $idx }})"
                        @if($mobileCurrent) aria-current="page" @endif>
                        <span>{{ $branch['title'] }}</span>
                        <span class="inline-flex shrink-0 items-center justify-center text-faded-olive" aria-hidden="true">
                          @include('partials.icons.figma-header-icon', [
                              'header_icon_variant' => 'mobile-drawer-chevron',
                              'header_icon_class' => 'h-[14px] w-2 shrink-0',
                          ])
                        </span>
                      </button>
                    @else
                      <a
                        class="mega-nav__mobile-root-link flex w-full items-center justify-between gap-4 py-5 font-heading text-4xl leading-[1.1] focus-visible:rounded-sm culvers-focus-ring-compact{{ $mobileCurrent ? ' mega-nav__mobile-root-link--current' : '' }}"
                        href="{{ esc_url($branch['url']) }}"
                        x-on:click.prevent="followMobileNavLink('{{ esc_url($branch['url']) }}')"
                        @if($mobileCurrent) aria-current="page" @endif>
                        <span>{{ $branch['title'] }}</span>
                        <span class="inline-flex shrink-0 items-center justify-center text-faded-olive" aria-hidden="true">
                          @include('partials.icons.figma-header-icon', [
                              'header_icon_variant' => 'mobile-drawer-chevron',
                              'header_icon_class' => 'h-[14px] w-2 shrink-0',
                          ])
                        </span>
                      </a>
                    @endif
                  </li>
                @endforeach
              </ul>
            @endif
          </nav>

          {{-- Useful Links + socials block pinned to bottom of the drawer panel (sheet feedback row 5:
               "They also need to always remain at the bottom of the menu"). `mt-auto` shoves the
               whole footer block down when the nav list is shorter than the viewport. --}}
          <div class="mt-auto pt-8">
            {{-- Figma `51:9059`: Commuters SemiBold 16 / lh 24 / 1 px tracking / Faded Olive / left-aligned. --}}
            <p class="text-left font-label text-base font-semibold uppercase leading-6 tracking-[0.0625em] text-faded-olive">
              {{ __('Useful links', 'culvers') }}
            </p>
            <div class="mt-5 flex flex-col gap-3">
              {{-- Figma `51:9087` Centre Map / Getting Here pill — Light Green at 60 %, Halyard
                   Display Book 20 / lh 1.3 / Faded Olive, 24 px icon. --}}
              <div
                class="flex min-h-[61px] overflow-hidden rounded-[12px] bg-light-green/60 text-faded-olive">
                <a
                  class="flex flex-1 items-center gap-3 px-5 py-3 text-left font-sans text-xl font-light leading-[1.3] transition-colors hover:bg-faded-olive/[0.06] culvers-focus-ring-compact-faded-olive"
                  href="{{ esc_url($mapUrl) }}"
                  x-on:click.prevent="followMobileNavLink('{{ esc_url($mapUrl) }}')">
                  @include('partials.icons.figma-header-icon', [
                      'header_icon_variant' => 'centre-map-mobile',
                      'header_icon_class' => 'size-[21px] shrink-0',
                  ])
                  <span class="min-w-0">{{ __('Centre Map', 'culvers') }}</span>
                </a>
                <a
                  class="flex flex-1 items-center gap-3 px-5 py-3 text-left font-sans text-xl font-light leading-[1.3] transition-colors hover:bg-faded-olive/[0.06] culvers-focus-ring-compact-faded-olive"
                  href="{{ esc_url($hereUrl) }}"
                  x-on:click.prevent="followMobileNavLink('{{ esc_url($hereUrl) }}')">
                  @include('partials.icons.figma-header-icon', [
                      'header_icon_variant' => 'getting-here-mobile',
                      'header_icon_class' => 'h-[22px] w-[18px] shrink-0',
                  ])
                  <span class="min-w-0">{{ __('Getting Here', 'culvers') }}</span>
                </a>
              </div>

              {{-- Figma `51:9095` socials pill — Glowleaf at 60 %, Commuters SemiBold 14.5 /
                   lh 29 / 1.2 px tracking / uppercase / 24 px icon. --}}
              <div
                class="flex min-h-[57px] overflow-hidden rounded-[12px] bg-glowleaf/60 text-faded-olive">
                @if($instagramUrl !== '' && $instagramUrl !== '#')
                  <a
                    class="flex flex-1 items-center gap-3 px-5 py-3 text-left font-label text-sm font-semibold uppercase leading-7 tracking-widest transition-colors hover:bg-faded-olive/[0.07] culvers-focus-ring-compact-faded-olive"
                    href="{{ esc_url($instagramUrl) }}"
                    target="_blank"
                    rel="noopener noreferrer">
                    @include('partials.figma-social-icon', [
                        'social_icon_variant' => 'instagram',
                        'social_icon_class' => 'size-[16.917px] shrink-0 text-faded-olive',
                    ])
                    <span class="min-w-0">{{ __('Instagram', 'culvers') }}</span>
                  </a>
                @else
                  <span
                    class="flex flex-1 cursor-not-allowed items-center gap-3 px-5 py-3 text-left font-label text-sm font-semibold uppercase leading-7 tracking-widest text-faded-olive/45">
                    @include('partials.figma-social-icon', [
                        'social_icon_variant' => 'instagram',
                        'social_icon_class' => 'size-[16.917px] shrink-0 opacity-50 text-faded-olive',
                    ])
                    <span class="min-w-0">{{ __('Instagram', 'culvers') }}</span>
                  </span>
                @endif
                @if($facebookUrl !== '' && $facebookUrl !== '#')
                  <a
                    class="flex flex-1 items-center gap-3 px-5 py-3 text-left font-label text-sm font-semibold uppercase leading-7 tracking-widest transition-colors hover:bg-faded-olive/[0.07] culvers-focus-ring-compact-faded-olive"
                    href="{{ esc_url($facebookUrl) }}"
                    target="_blank"
                    rel="noopener noreferrer">
                    @include('partials.figma-social-icon', [
                        'social_icon_variant' => 'facebook',
                        'social_icon_class' => 'size-[18.125px] shrink-0 text-faded-olive',
                    ])
                    <span class="min-w-0">{{ __('Facebook', 'culvers') }}</span>
                  </a>
                @else
                  <span
                    class="flex flex-1 cursor-not-allowed items-center gap-3 px-5 py-3 text-left font-label text-sm font-semibold uppercase leading-7 tracking-widest text-faded-olive/45">
                    @include('partials.figma-social-icon', [
                        'social_icon_variant' => 'facebook',
                        'social_icon_class' => 'size-[18.125px] shrink-0 opacity-50 text-faded-olive',
                    ])
                    <span class="min-w-0">{{ __('Facebook', 'culvers') }}</span>
                  </span>
                @endif
              </div>
            </div>
          </div>
        </div>

        {{-- Submenu panel — Figma 51:9130 (second-level drill-down). --}}
        <div
          class="flex h-full w-1/2 min-w-[50%] flex-col overflow-y-auto overscroll-contain px-4 pb-10 pt-6 sm:px-6"
          id="mega-mobile-panel-sub"
          :aria-hidden="mobileNavDepth === 0">
          <div class="flex flex-col gap-[42px]">
            <div class="flex flex-col gap-[19px]">
              <button
                type="button"
                class="inline-flex items-center gap-2.5 text-left text-faded-olive culvers-focus-ring-compact-faded-olive"
                x-on:click="resetMobileSubmenu()">
                <span class="inline-flex shrink-0" aria-hidden="true">
                  @include('partials.icons.figma-header-icon', [
                      'header_icon_variant' => 'mobile-back-arrow',
                      'header_icon_class' => 'size-[30px] shrink-0 rotate-90',
                  ])
                </span>
                <span class="font-label text-sm font-semibold uppercase leading-6 tracking-widest">{{ __('Back', 'culvers') }}</span>
              </button>
              <div class="h-0 w-full border-b border-faded-olive/15" aria-hidden="true"></div>
            </div>

            <template x-if="mobileActiveBranch">
              <div class="flex min-h-0 flex-1 flex-col gap-[42px]">
                <div class="flex flex-col gap-[26px]">
                  <div class="flex items-center justify-between gap-3">
                    <h3
                      class="font-heading text-4xl leading-[1.1] text-faded-olive"
                      x-text="mobileActiveBranch.title"></h3>
                    <a
                      class="inline-flex size-[43px] shrink-0 items-center justify-center rounded-full bg-glowleaf text-deep-moss culvers-focus-ring-compact-deep-moss"
                      x-bind:href="mobileActiveBranch.url || '#'"
                      x-on:click.prevent="followMobileNavLink(mobileActiveBranch.url)">
                      <span class="sr-only">{{ __('Open section', 'culvers') }}</span>
                      @include('partials.icons.figma-header-icon', [
                          'header_icon_variant' => 'explore-arrow',
                          'header_icon_class' => 'size-4 shrink-0',
                      ])
                    </a>
                  </div>
                  <div class="h-0 w-full border-b border-deep-moss" aria-hidden="true"></div>
                </div>
                <ul class="divide-y divide-faded-olive/15">
                  <template x-for="(child, cIdx) in mobileActiveBranch.children" :key="(child.url || '') + '-' + cIdx">
                    <li class="list-none">
                      <a
                        class="mega-nav__sublink block py-6 font-sans text-2xl font-normal capitalize leading-[1.3] focus-visible:rounded-sm culvers-focus-ring-compact"
                        x-bind:href="child.url"
                        x-on:click.prevent="followMobileNavLink(child.url)"
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
  </div>
</header>
