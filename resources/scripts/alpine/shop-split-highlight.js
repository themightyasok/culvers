/**
 * Shop split-highlight: tabbed copy column with cross-faded panels.
 * Mirrors the WAI-ARIA tab pattern (roving tabindex, aria-selected, arrow-key nav).
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export default function registerSplitHighlightAlpine(Alpine) {
  Alpine.data('splitHighlight', () => ({
    activeTab: 0,

    init() {
      this.syncTabAccessibility();
    },

    /**
     * @param {number} index
     * @param {boolean} [focus] When true (keyboard nav), move focus to the newly active tab.
     */
    selectTab(index, focus = false) {
      this.activeTab = index;
      this.syncTabAccessibility();
      if (!focus) {
        return;
      }
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }
      const next = root.querySelectorAll('[role="tab"]')[index];
      if (next instanceof HTMLElement) {
        next.focus();
      }
    },

    syncTabAccessibility() {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }
      const tabs = root.querySelectorAll('[role="tab"]');
      tabs.forEach((tab, i) => {
        if (!(tab instanceof HTMLElement)) {
          return;
        }
        const selected = i === this.activeTab;
        tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        tab.tabIndex = selected ? 0 : -1;
      });
    },
  }));
}
