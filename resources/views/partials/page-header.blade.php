@php
  use App\Helpers\Component;
  use App\Helpers\LayoutShell;

  if (! isset($title) || trim((string) $title) === '') {
      $title = \App\Support\DocumentTitle::current();
  }
  $pageTitle = trim((string) $title);
@endphp

@if(! is_front_page() && $pageTitle !== '')
  {{-- Static shell: same cap/inset as flexible blocks — not tied to header scroll. --}}
  <div class="page-header {{ LayoutShell::INNER_MAX_GUTTERED }} pb-6 pt-10 md:pb-8 md:pt-12">
    <h1 class="page-header__title m-0 {{ Component::sectionHeadingClasses('text-deep-moss') }}">
      {!! esc_html($pageTitle) !!}
    </h1>
  </div>
@endif
