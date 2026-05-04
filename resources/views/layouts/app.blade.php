<!doctype html>
<html {!! get_language_attributes() !!}>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
      do_action('get_header');
      wp_head();
    @endphp
  </head>

  <body @php body_class('bg-canvas text-text antialiased'); @endphp>
    @php wp_body_open(); @endphp

    @include('sections.header')

    <div class="h-16 lg:h-20" aria-hidden="true"></div>

    <div id="smooth-wrapper">
      <div id="smooth-content">
        <div id="app" class="min-h-screen">
          <a class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-white"
             href="#main">
            {{ __('Skip to content', 'culvers') }}
          </a>

          <main id="main">
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
