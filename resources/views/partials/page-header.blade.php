@php
  if (! isset($title) || trim((string) $title) === '') {
      $title = \App\Support\DocumentTitle::current();
  }
  $pageTitle = trim((string) $title);
@endphp

@if(! is_front_page() && $pageTitle !== '')
  <div class="page-header mx-auto w-full max-w-[1800px] px-4 pb-6 pt-10 md:px-8 md:pb-8 md:pt-12">
    <h1 class="m-0 font-heading font-semibold tracking-tight text-text text-7xl md:text-8xl">
      {!! esc_html($pageTitle) !!}
    </h1>
  </div>
@endif
