@php
  use App\Helpers\Component;

  if (! isset($title) || trim((string) $title) === '') {
      $title = \App\Support\DocumentTitle::current();
  }
  $pageTitle = trim((string) $title);
@endphp

@if(! is_front_page() && $pageTitle !== '')
  {{-- Static shell: same cap/inset as flexible blocks — not tied to header scroll. --}}
  <div class="page-header mx-auto w-full max-w-8xl px-4 pb-6 pt-10 md:px-5 md:pb-8 md:pt-12 lg:px-6">
    <h1 class="page-header__title m-0 {{ Component::sectionHeadingClasses('text-text') }}">
      {!! esc_html($pageTitle) !!}
    </h1>
  </div>
@endif
