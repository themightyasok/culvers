@php
use App\Helpers\Padding;

$c = $component ?? [];
$padding = Padding::getClasses($c);
$tone = $c['body_text_tone'] ?? 'text-zinc-100';
$grid = $c['_grid_classes'] ?? '';
@endphp

<section class="{{ esc_attr(trim($grid . ' ' . $padding)) }} relative" data-component-root>
  @if(! empty($c['heading']))
    <h2 class="mb-4 text-3xl font-semibold tracking-tight text-white md:text-4xl">
      {{ esc_html($c['heading']) }}
    </h2>
  @endif

  @if(! empty($c['body']))
    <div class="prose prose-invert max-w-none {{ esc_attr($tone) }}">
      {!! $c['body'] !!}
    </div>
  @endif
</section>
