@php
  use App\Helpers\LayoutShell;

  $introHtml = trim((string) ($introHtml ?? ''));
@endphp

@if($introHtml !== '')
  {{-- <div> wrapper (not <p>) so text-center utilities survive wpautop() inner <p>. --}}
  <div class="{{ LayoutShell::ARCHIVE_INTRO }}">
    {!! wp_kses_post(wpautop($introHtml)) !!}
  </div>
@endif
