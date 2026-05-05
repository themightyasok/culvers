@php
  use App\Helpers\Padding;
  use App\Helpers\TailwindColors;

  $c = is_array($component ?? null) ? $component : [];
  $padding = Padding::getClasses($c);
  $tone = TailwindColors::sanitizeBodyTextTone($c['body_text_tone'] ?? null);
  $grid = $c['_grid_classes'] ?? '';

  $level = isset($c['heading_semantic_level']) ? (int) $c['heading_semantic_level'] : 2;
  if ($level < 1 || $level > 6) {
      $level = 2;
  }
  $headingTag = 'h' . $level;
@endphp

<section class="{{ esc_attr(trim($grid . ' ' . $padding)) }} relative text-deep-moss" data-component-root>
  @if(! empty($c['heading']))
    <{{ $headingTag }} class="mb-4 font-heading text-3xl font-semibold tracking-tight md:text-4xl">
      {{ esc_html($c['heading']) }}
    </{{ $headingTag }}>
  @endif

  @if(! empty($c['body']))
    <div
      class="prose prose-neutral prose-lg max-w-none [&_a]:text-deep-moss [&_a]:underline [&_a]:decoration-glowleaf [&_a]:underline-offset-4 {{ esc_attr($tone) }}">
      {!! $c['body'] !!}
    </div>
  @endif
</section>
