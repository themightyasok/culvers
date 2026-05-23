{{-- Glowleaf circular +/- — Figma centre map / contact map (`51:9437`, `51:9502`). --}}
<div
  class="map-embed-zoom absolute bottom-4 right-4 z-20 flex flex-col gap-2 md:bottom-6 md:right-6"
  role="group"
  aria-label="{{ esc_attr__('Map zoom', 'culvers') }}">
  <button
    type="button"
    class="map-embed-zoom__button inline-flex size-[43px] items-center justify-center rounded-full bg-glowleaf text-deep-moss transition hover:bg-lighter-cream culvers-focus-ring-compact disabled:cursor-not-allowed disabled:opacity-40"
    aria-label="{{ esc_attr__('Zoom in', 'culvers') }}"
    x-bind:disabled="zoom >= maxZoom"
    @click="zoomIn()">
    <svg viewBox="0 0 16 16" class="size-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
      <path d="M8 1v14M1 8h14" />
    </svg>
  </button>
  <button
    type="button"
    class="map-embed-zoom__button inline-flex size-[43px] items-center justify-center rounded-full bg-glowleaf text-deep-moss transition hover:bg-lighter-cream culvers-focus-ring-compact disabled:cursor-not-allowed disabled:opacity-40"
    aria-label="{{ esc_attr__('Zoom out', 'culvers') }}"
    x-bind:disabled="zoom <= minZoom"
    @click="zoomOut()">
    <svg viewBox="0 0 16 16" class="size-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
      <path d="M1 8h14" />
    </svg>
  </button>
</div>
