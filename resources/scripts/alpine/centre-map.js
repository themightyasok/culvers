/**
 * Centre map — Alpine state for the dark map band.
 *
 *   • `panelOpen`         — visibility of the left filter panel (filter-toggle pill)
 *   • `openGroup`         — currently open accordion in the panel (one at a time)
 *   • `activeCategorySlug` — currently selected category radio (visual-only)
 *   • `zoom`              — map zoom level driven by the +/- buttons (1..2.5)
 *
 * The Blade emits the initial JSON via `x-data="centreMap({...})"`, so we
 * accept any partial shape and merge defensible defaults around it. This
 * keeps the inline data parsing in one place (here) instead of every
 * component instance hard-coding the shape inline.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export default function registerCentreMapAlpine(Alpine) {
  Alpine.data('centreMap', (init = {}) => ({
    panelOpen: typeof init.panelOpen === 'boolean' ? init.panelOpen : true,
    openGroup: typeof init.openGroup === 'string' ? init.openGroup : '',
    activeCategorySlug: typeof init.activeCategorySlug === 'string' ? init.activeCategorySlug : '',
    zoom: typeof init.zoom === 'number' && init.zoom >= 1 ? init.zoom : 1,
  }));
}
