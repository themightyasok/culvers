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

  <body @php body_class('bg-white text-deep-moss font-sans antialiased'); @endphp>
    @php wp_body_open(); @endphp

    <a class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-80 focus:rounded-md focus:bg-brand-500 focus:px-4 focus:py-2 focus:text-deep-moss focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-deep-moss"
       href="#main">
      {{ __('Skip to content', 'culvers') }}
    </a>

    @include('sections.header')

    {{-- Fixed header clearance: `--site-header-offset` is set by `site-header.js` (ResizeObserver). No per-component padding. --}}
    <div id="smooth-wrapper">
      <div id="smooth-content">
        {{--
          Do not use min-h-screen here: when main + footer are shorter than the viewport (e.g. /shops/),
          min-height would extend #app below the footer and show empty bg-white — misread as a “footer gap”.
          Homepage flexible rows are tall enough that this rarely appears.
        --}}
        <div id="app" class="bg-white">
          <main id="main" tabindex="-1">
            @yield('content')
          </main>

          {{--
            Footer-overlap spacer.
            `.footer-newsletter-band` is vertically centred on the footer top with `-translate-y-1/2`
            (half of the newsletter sits in the white band, half on olive). The spacer must reserve
            **newsletter_half + clear breathing room** so the card never visually slams into the last
            component above it. Newsletter min-heights (Figma calibrated) are
            300 / 380 / 420 → halves 150 / 190 / 210. We add ~90 / 130 / 150 px of clean whitespace
            on top so the rhythm matches the rest of the page-to-section spacing.
          --}}
          <div aria-hidden="true" class="site-footer-spacer h-[240px] w-full bg-white md:h-[320px] lg:h-[360px]"></div>

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
