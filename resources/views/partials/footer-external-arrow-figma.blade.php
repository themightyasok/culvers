{{-- Culver Square Website Design footer (51:5147) — “View on map” arrow (`ab99cd24-795f-472c-bb05-6417de243aba`), −45° for NE.
     Sized to match ~14 px label cap-height in Figma; never bind `$class` here (shared Blade scope). --}}
@php $footer_arrow_class = trim((string) ($footer_arrow_class ?? '')); @endphp
<span
  class="{{ esc_attr(trim('footer-external-arrow-figma inline-flex h-[14px] w-[14px] shrink-0 items-center justify-center align-middle text-current ' . $footer_arrow_class)) }}"
  aria-hidden="true">
  <span class="-rotate-45 flex-none leading-none">
    <svg
      xmlns="http://www.w3.org/2000/svg"
      class="block h-[12px] w-[12px]"
      viewBox="0 0 11 11"
      fill="none"
      focusable="false">
      <path fill="currentColor" d="M8.37031 6.1875H0V4.8125H8.37031L4.52031 0.9625L5.5 0L11 5.5L5.5 11L4.52031 10.0375L8.37031 6.1875Z" />
    </svg>
  </span>
</span>
