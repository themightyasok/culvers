@php
  use App\Helpers\Component;

  /**
   * Content section — heading + rich body inside the inherited grid gutters.
   * Lives on long-form/policy pages where it can host the page H1.
   * No inner shell: keeps the grid gutters as the section frame.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c, stripGutters: false);
  $tone = Component::bodyTextTone($c);
  $headingTag = Component::headingTag($c['content_heading_level'] ?? null);
  $heading = trim((string) ($c['content_heading'] ?? ''));
  $body = (string) ($c['content_body'] ?? '');
  $hasContent = $heading !== '' || trim(strip_tags($body)) !== '';
@endphp

@if($hasContent)
<section
  class="content-section {{ esc_attr($root) }} relative text-deep-moss"
  data-component-root
  data-content-section>
  @if($heading !== '')
    {{-- Section H2: 64px desktop / 48px mobile (Component::sectionHeadingClasses). --}}
    <{{ $headingTag }} class="content-section__heading {{ Component::sectionHeadingClasses('text-deep-moss', 'mb-4') }}">
      {{ esc_html($heading) }}
    </{{ $headingTag }}>
  @endif

  @if(trim(strip_tags($body)) !== '')
    <div
      class="content-section__body prose prose-lg max-w-none text-deep-moss prose-headings:text-deep-moss prose-p:text-deep-moss prose-li:text-deep-moss prose-strong:text-deep-moss [&_a]:text-deep-moss [&_a]:underline [&_a]:decoration-glowleaf [&_a]:underline-offset-4 hover:[&_a]:decoration-deep-moss {{ esc_attr($tone) }}">
      {!! $body !!}
    </div>
  @endif
</section>
@elseif(current_user_can('edit_posts'))
@include('partials.component-editor-placeholder', [
    'wrapperClasses' => $root,
    'message' => __('Add a heading or body copy to this content section.', 'culvers'),
])
@endif
