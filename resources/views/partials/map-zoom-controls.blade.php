@php
  /**
   * Glowleaf circular +/- map zoom controls (Figma 51:9437 / 51:9502).
   *
   * @var 'embed'|'centre-map' $variant
   *   embed — contact iframe map (`zoomIn()` / `zoomOut()` on Alpine component)
   *   centre-map — raster centre map (`zoom` + `clampPan()` on centreMap Alpine)
   * @var float $maxZoom
   * @var float $minZoom
   * @var float $step  centre-map fractional step (embed uses integer steps via methods)
   */
  $variant = is_string($variant ?? null) ? $variant : 'embed';
  $maxZoom = (float) ($maxZoom ?? ($variant === 'centre-map' ? 2.5 : 19));
  $minZoom = (float) ($minZoom ?? 1);
  $step = (float) ($step ?? 0.25);

  $wrapperClass = match ($variant) {
      'centre-map' => 'centre-map__zoom-controls absolute bottom-4 right-4 flex flex-col gap-2 max-lg:bottom-3 max-lg:right-3 max-lg:gap-2 md:bottom-6 md:right-6',
      default => 'map-embed-zoom absolute bottom-4 right-4 z-20 flex flex-col gap-2 md:bottom-6 md:right-6',
  };

  $buttonClass = match ($variant) {
      'centre-map' => 'centre-map__zoom-button inline-flex size-[43px] items-center justify-center rounded-full bg-glowleaf text-deep-moss transition hover:bg-lighter-cream culvers-focus-ring-compact disabled:cursor-not-allowed disabled:opacity-40 lg:size-10 xl:size-11',
      default => 'map-embed-zoom__button inline-flex size-[43px] items-center justify-center rounded-full bg-glowleaf text-deep-moss transition hover:bg-lighter-cream culvers-focus-ring-compact disabled:cursor-not-allowed disabled:opacity-40',
  };
@endphp

<div
  class="{{ $wrapperClass }}"
  role="group"
  aria-label="{{ esc_attr__('Map zoom', 'culvers') }}">
  @if($variant === 'centre-map')
    <button
      type="button"
      class="{{ $buttonClass }}"
      aria-label="{{ esc_attr__('Zoom in', 'culvers') }}"
      :disabled="zoom >= {{ $maxZoom }}"
      @click="zoom = Math.min({{ $maxZoom }}, Math.round((zoom + {{ $step }}) * 100) / 100); $nextTick(() => clampPan())">
      @include('partials.icons.map-zoom-in')
    </button>
    <button
      type="button"
      class="{{ $buttonClass }}"
      aria-label="{{ esc_attr__('Zoom out', 'culvers') }}"
      :disabled="zoom <= {{ $minZoom }}"
      @click="zoom = Math.max({{ $minZoom }}, Math.round((zoom - {{ $step }}) * 100) / 100); $nextTick(() => clampPan())">
      @include('partials.icons.map-zoom-out')
    </button>
  @else
    <button
      type="button"
      class="{{ $buttonClass }}"
      aria-label="{{ esc_attr__('Zoom in', 'culvers') }}"
      x-bind:disabled="zoom >= maxZoom"
      @click="zoomIn()">
      @include('partials.icons.map-zoom-in')
    </button>
    <button
      type="button"
      class="{{ $buttonClass }}"
      aria-label="{{ esc_attr__('Zoom out', 'culvers') }}"
      x-bind:disabled="zoom <= minZoom"
      @click="zoomOut()">
      @include('partials.icons.map-zoom-out')
    </button>
  @endif
</div>
