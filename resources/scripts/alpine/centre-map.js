/**
 * Centre map — Alpine state for the dark map band.
 *
 *   • `panelOpen`           — visibility of the filter panel (filter-toggle pill)
 *   • `openGroup`           — currently open accordion in the panel (one at a time)
 *   • `activeCategorySlug`  — currently selected category slug; also surfaced on the
 *                             map wrap as `[data-active-category]` so a future SVG
 *                             export with per-shop `[data-category~="…"]` layers
 *                             can highlight matching shops via CSS only.
 *   • `activeCategoryLabel` — human-readable label shown in the on-map status pill.
 *   • `zoom`                — map zoom level driven by the +/- buttons (1..2.5)
 *
 * The Blade emits the initial JSON via `x-data="centreMap({...})"`, so we
 * accept any partial shape and merge defensible defaults around it.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export default function registerCentreMapAlpine(Alpine) {
  Alpine.data('centreMap', (init = {}) => ({
    panelOpen: typeof init.panelOpen === 'boolean' ? init.panelOpen : true,
    openGroup: typeof init.openGroup === 'string' ? init.openGroup : '',
    activeCategorySlug: typeof init.activeCategorySlug === 'string' ? init.activeCategorySlug : '',
    activeCategoryLabel:
      typeof init.activeCategoryLabel === 'string' ? init.activeCategoryLabel : '',
    zoom: typeof init.zoom === 'number' && init.zoom >= 1 ? init.zoom : 1,
  }));
}
