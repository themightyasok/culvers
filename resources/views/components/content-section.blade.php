@php
  use App\Helpers\Component;
  use App\Helpers\LayoutShell;

  /**
   * Content section — heading + rich body inside the fixed site shell (static
   * `max-w-8xl` + bar-row inset), not the flexible grid’s old px gutters.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $tone = Component::bodyTextTone($c);
  $headingTag = Component::headingTagFromComponent($c, 'content_heading_level', 2);
  $heading = trim((string) ($c['content_heading'] ?? ''));
  $body = (string) ($c['content_body'] ?? '');
  $hasContent = $heading !== '' || trim(strip_tags($body)) !== '';
@endphp

@if($hasContent)
<section
  class="content-section {{ esc_attr($root) }} relative text-deep-moss"
  data-component-root
  data-content-section>
  <div class="{{ LayoutShell::INNER_MAX_GUTTERED }} text-left">
  @if($heading !== '')
    {{-- Section H2: 64px desktop / 48px mobile (Component::sectionHeadingClasses). --}}
    <{{ $headingTag }} class="content-section__heading {{ Component::sectionHeadingClasses('text-deep-moss', 'mb-4') }}">
      {{ esc_html($heading) }}
    </{{ $headingTag }}>
  @endif

  @if(trim(strip_tags($body)) !== '')
    <div
      class="content-section__body prose prose-lg max-w-none text-deep-moss prose-headings:text-deep-moss prose-p:text-deep-moss prose-li:text-deep-moss prose-strong:text-deep-moss rt-link-prose {{ esc_attr($tone) }}">
      {!! $body !!}
    </div>
  @endif
  </div>
</section>
@elseif(current_user_can('edit_posts'))
@include('partials.component-editor-placeholder', [
    'wrapperClasses' => $root,
    'message' => __('Add a heading or body copy to this content section.', 'culvers'),
])
@endif
