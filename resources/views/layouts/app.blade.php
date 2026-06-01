<!doctype html>
<html {!! get_language_attributes() !!} class="scroll-smooth motion-reduce:scroll-auto">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
      do_action('get_header');
      wp_head();
    @endphp
  </head>

  {{-- Figma site surface: Off white (#EEE9E5) — solid fill behind page diamond pattern. --}}
  <body @php body_class('bg-lighter-cream text-deep-moss font-sans antialiased'); @endphp>
    @php wp_body_open(); @endphp

    <a class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-80 focus:rounded-md focus:bg-brand-500 focus:px-4 focus:py-2 focus:text-deep-moss focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-deep-moss"
       href="#main">
      {{ __('Skip to content', 'culvers') }}
    </a>

    @include('sections.header')

    {{-- ScrollSmoother viewport is `#smooth-wrapper` (no padding — padding here clips the footer).
         Header clearance is the spacer inside `#smooth-content`; height from `--site-header-offset`. --}}
    <div id="smooth-wrapper">
      <div id="smooth-content">
        <div class="site-header-scroll-spacer" aria-hidden="true"></div>
        @php
          use App\View\SitePageSurface;
          $pageSurface = SitePageSurface::config();
          $is404 = is_404();
          $pageSurfaceModifier = $is404
              ? 'site-page-surface--404'
              : $pageSurface['modifier'];
          $footerSpacerClass = $is404
              ? 'h-0 w-full lg:h-0'
              : 'h-24 w-full lg:h-[306px]';
        @endphp
        {{--
          Do not use min-h-screen here: when main + footer are shorter than the viewport (e.g. /shops/),
          min-height would extend #app below the footer and show empty cream — misread as a “footer gap”.
          Homepage flexible rows are tall enough that this rarely appears.
        --}}
        <div
          id="app"
          class="relative site-page-surface {{ esc_attr($pageSurfaceModifier) }}"
          style="--culvers-page-tile: url('{{ esc_url(SitePageSurface::tileUri()) }}'); --culvers-page-tile-ratio: {{ esc_attr(SitePageSurface::tileHeightRatio()) }};">
          <main id="main" tabindex="-1">
            @yield('content')
          </main>

          {{--
            Footer-overlap spacer.
            `.footer-newsletter-band` is vertically centred on the footer top with `-translate-y-1/2`
            from lg upward (half the newsletter sits in the cream band, half on olive). Below lg the
            newsletter is full-bleed with no overlap — one standard `gap-y-24` (96px) band only.
            lg+ spacer = newsletter half (210px @ min-h 420) + 96px — same rhythm as flexible rows.
          --}}
          <div aria-hidden="true" class="site-footer-spacer {{ esc_attr($footerSpacerClass) }}"></div>

          @include('sections.footer')
        </div>
      </div>
    </div>

    @php
      do_action('get_footer');
      wp_footer();
    @endphp
  </body>
</html>
