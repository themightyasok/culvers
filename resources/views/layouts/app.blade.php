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
            `.footer-newsletter-band` lives inside the olive footer with `-mt-32 md:-mt-44 lg:-mt-52`
            (pulls the newsletter image up into the white area above). Without a buffer between
            `<main>` and `<footer>`, that pull-up overlays the last flexible component on the page.
            The spacer matches the negative-margin amount per breakpoint so the newsletter pulls
            into safe whitespace instead of real content. Every page benefits — no per-component
            padding needed.
          --}}
          <div aria-hidden="true" class="site-footer-spacer h-32 w-full bg-white md:h-44 lg:h-52"></div>

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
