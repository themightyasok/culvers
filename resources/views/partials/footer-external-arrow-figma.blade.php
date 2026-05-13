{{-- Culver Square Website Design footer (51:5147) — “View on map” / external NB arrow — Vector `ab99cd24-795f-472c-bb05-6417de243aba` via Figma MCP. Rotated −45° in layout for NE direction; fill follows `currentColor`. --}}
@php $icon_class = (string) ($class ?? ''); @endphp
<span class="{{ esc_attr(trim('footer-external-arrow-figma inline-flex size-[15.556px] shrink-0 items-center justify-center ' . $icon_class)) }}" aria-hidden="true">
  <span class="-rotate-45 flex-none [&_svg]:block [&_svg]:size-[11px]">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 11 11" fill="none" focusable="false">
      <path fill="currentColor" d="M8.37031 6.1875H0V4.8125H8.37031L4.52031 0.9625L5.5 0L11 5.5L5.5 11L4.52031 10.0375L8.37031 6.1875Z" />
    </svg>
  </span>
</span>
