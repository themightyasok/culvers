@php
use App\Helpers\Padding;

$c = $component ?? [];
$padding = Padding::getClasses($c);
$tone = $c['body_text_tone'] ?? 'text-zinc-100';
$grid = $c['_grid_classes'] ?? '';

$level = isset($c['heading_semantic_level']) ? (int) $c['heading_semantic_level'] : 2;
if ($level < 1 || $level > 6) {
    $level = 2;
}
$headingTag = 'h' . $level;
@endphp

<section class="{{ esc_attr(trim($grid . ' ' . $padding)) }} relative" data-component-root>
  @if(! empty($c['heading']))
    <{{ $headingTag }} class="mb-4 font-heading font-semibold tracking-tight text-text text-2xl md:text-3xl">
      {{ esc_html($c['heading']) }}
    </{{ $headingTag }}>
  @endif

  @if(! empty($c['body']))
    <div class="prose prose-invert max-w-none {{ esc_attr($tone) }}">
      {!! $c['body'] !!}
    </div>
  @endif
</section>
