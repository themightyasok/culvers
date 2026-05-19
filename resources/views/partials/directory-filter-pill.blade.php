@php
  /**
   * Directory filter toggle pill — the brand-yellow "Show / Hide filters"
   * button used in the toolbar above every filtered directory archive
   * (Shop / Eat & Drink / Careers).
   *
   * The button is a controlled child of the surrounding `directoryArchive`
   * Alpine module — it reads `filtersVisible` and calls `toggleFilters()`,
   * matching the contract in
   * {@see resources/scripts/alpine/directory-archive.js}.
   *
   * Inputs:
   *   • `$toggle_id`           — DOM id (per-archive, e.g.
   *                               `directory-filter-toggle-eat-drink`).
   *   • `$controls_id`         — id of the panel toggled by this button
   *                               (matches the sidebar shell's `id`).
   *   • `$show_label` (string) — copy when the panel is collapsed.
   *                               Defaults to `'Show filters'`.
   *   • `$hide_label` (string) — copy when the panel is expanded.
   *                               Defaults to `'Hide filters'`.
   */
  $pill_toggle_id = (string) ($toggle_id ?? 'directory-filter-toggle');
  $pill_controls_id = (string) ($controls_id ?? 'directory-archive-filters');
  $pill_show_label = (string) ($show_label ?? __('Show filters', 'culvers'));
  $pill_hide_label = (string) ($hide_label ?? __('Hide filters', 'culvers'));

  /* JSON for Alpine: HTML-escape when embedded in double-quoted x-text so
     json_encode outer quotes cannot break the attribute. */
  $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES;
@endphp
<button
  id="{{ esc_attr($pill_toggle_id) }}"
  type="button"
  class="directory-archive__filter-pill btn btn-primary w-max max-w-full gap-3 py-2 pl-6 pr-5 hover:brightness-95"
  @click="toggleFilters()"
  :aria-expanded="filtersVisible ? 'true' : 'false'"
  aria-controls="{{ esc_attr($pill_controls_id) }}">
  <span x-text="filtersVisible ? {{ e(json_encode($pill_hide_label, $json_flags)) }} : {{ e(json_encode($pill_show_label, $json_flags)) }}"></span>
  {{-- Icons: detailed close glyph when filters are visible, compact slider
       glyph when they're hidden — matches Figma toolbar states. --}}
  <svg
    class="size-[19.825px] shrink-0"
    x-show="filtersVisible"
    x-cloak
    viewBox="0 0 24 24"
    fill="none"
    aria-hidden="true">
    <path
      d="M4 6h12M4 12h8m-8 6h4M18 5l3 3m0 0-9 9m9-9-9 9"
      stroke="currentColor"
      stroke-width="1.6"
      stroke-linecap="round"
      stroke-linejoin="round" />
  </svg>
  {{-- Figma toolbar sliders glyph: horizontal rails with adjustable dot handles. --}}
  <svg class="h-5 w-[22px] shrink-0" x-show="!filtersVisible" x-cloak viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M4 6h6m4 0h6M4 12h2m4 0h10M4 18h12m4 0h0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
    <circle cx="12" cy="6" r="1.8" fill="currentColor" />
    <circle cx="8" cy="12" r="1.8" fill="currentColor" />
    <circle cx="18" cy="18" r="1.8" fill="currentColor" />
  </svg>
</button>
