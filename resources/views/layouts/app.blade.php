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

  <body @php body_class('bg-canvas text-text font-sans antialiased'); @endphp>
    @php wp_body_open(); @endphp

    <a class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[80] focus:rounded-md focus:bg-brand-500 focus:px-4 focus:py-2 focus:text-deep-moss focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-deep-moss"
       href="#main">
      {{ __('Skip to content', 'culvers') }}
    </a>

    @include('sections.header')

    <div class="min-h-[180px] md:min-h-[220px]" aria-hidden="true"></div>

    <div id="smooth-wrapper">
      <div id="smooth-content">
        <div id="app" class="min-h-screen">
          <main id="main" tabindex="-1">
            @yield('content')
          </main>

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
