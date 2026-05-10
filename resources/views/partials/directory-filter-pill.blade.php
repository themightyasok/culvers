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

  /* JSON_HEX_* keeps the strings safe inside Alpine's `x-text="…"` evaluator
     without forcing us to template-quote per insertion site. */
  $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES;
@endphp
<button
  id="{{ esc_attr($pill_toggle_id) }}"
  type="button"
  class="directory-archive__filter-pill inline-flex w-max max-w-full items-center gap-3 rounded-full bg-brand-500 py-2 pl-6 pr-5 font-sans text-xs font-semibold uppercase leading-8 tracking-wider text-deep-moss transition hover:brightness-95 culvers-focus-ring-deep-moss"
  @click="toggleFilters()"
  :aria-expanded="filtersVisible ? 'true' : 'false'"
  aria-controls="{{ esc_attr($pill_controls_id) }}">
  <span x-text="filtersVisible ? {{ json_encode($pill_hide_label, $json_flags) }} : {{ json_encode($pill_show_label, $json_flags) }}"></span>
  {{-- Icons: detailed close glyph when filters are visible, compact slider
       glyph when they're hidden — matches Figma toolbar states. --}}
  <svg
    class="size-5 shrink-0"
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
  <svg class="h-3 w-[18px] shrink-0" x-show="!filtersVisible" x-cloak viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M4 6h16M8 12h8M10 18h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
  </svg>
</button>
