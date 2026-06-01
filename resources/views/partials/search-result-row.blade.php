@php
  /**
   * Single predictive / full-page search row — Figma `51:8146`.
   *
   * @var array{id:int,title:string,excerpt:string,url:string,type:string,subtype:string,subtypeLabel:string} $result
   * @var string $query
   */
  use App\Search\SearchHighlight;

  $result = is_array($result ?? null) ? $result : [];
  $title = trim((string) ($result['title'] ?? ''));
  $excerpt = trim((string) ($result['excerpt'] ?? ''));
  $url = trim((string) ($result['url'] ?? ''));
  $query = trim((string) ($query ?? ''));

  if ($url === '') {
      $url = '#';
  }
@endphp

@if($title !== '')
  <li>
    <a
      class="search-result-row block rounded-[10px] px-2.5 py-3.5 font-sans text-xl font-light leading-[1.3] text-faded-olive transition-colors hover:bg-faded-olive/[0.06] culvers-focus-ring-compact-faded-olive"
      href="{{ esc_url($url) }}">
      <span class="block">{!! SearchHighlight::mark($title, $query) !!}</span>
      @if($excerpt !== '')
        <span class="search-result-row__excerpt mt-1 block text-faded-olive/80">{!! SearchHighlight::mark($excerpt, $query) !!}</span>
      @endif
    </a>
  </li>
@endif
