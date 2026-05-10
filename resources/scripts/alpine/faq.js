/**
 * FAQ accordion: WAI-ARIA disclosure pattern with single/multi open modes.
 * Smooth height animation uses the modern grid-template-rows trick driven by
 * the `data-open` attribute on the panel (no JS height measuring). Do not
 * toggle the `hidden` attribute on panels — it forces `display:none` and
 * prevents the transition from running.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export default function registerFaqAlpine(Alpine) {
  Alpine.data('faq', (config = {}) => ({
    /** @type {'single' | 'multi'} */
    mode: config.mode === 'multi' ? 'multi' : 'single',
    /** @type {number[]} */
    openIndices: Array.isArray(config.defaultOpen) ? [...config.defaultOpen] : [],

    init() {
      if (this.mode === 'single' && this.openIndices.length > 1) {
        this.openIndices = [this.openIndices[0]];
      }
      this.syncAccessibility();
    },

    /**
     * @param {number} index
     */
    isOpen(index) {
      return this.openIndices.includes(index);
    },

    /**
     * @param {number} index
     */
    toggle(index) {
      if (this.isOpen(index)) {
        this.openIndices = this.openIndices.filter((i) => i !== index);
      } else if (this.mode === 'single') {
        this.openIndices = [index];
      } else {
        this.openIndices = [...this.openIndices, index];
      }
      this.syncAccessibility();
    },

    /**
     * Move focus to the next/previous question button (WAI ARIA Disclosure +
     * common accordion convention — Up/Down cycle through headers).
     *
     * @param {number} fromIndex
     * @param {1 | -1} delta
     */
    focusSibling(fromIndex, delta) {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }
      const buttons = root.querySelectorAll('[data-faq-question]');
      if (buttons.length === 0) {
        return;
      }
      const next = (fromIndex + delta + buttons.length) % buttons.length;
      const target = buttons[next];
      if (target instanceof HTMLElement) {
        target.focus();
      }
    },

    /**
     * @param {number} fromIndex
     * @param {'first' | 'last'} edge
     */
    focusEdge(fromIndex, edge) {
      void fromIndex;
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }
      const buttons = root.querySelectorAll('[data-faq-question]');
      if (buttons.length === 0) {
        return;
      }
      const target = edge === 'first' ? buttons[0] : buttons[buttons.length - 1];
      if (target instanceof HTMLElement) {
        target.focus();
      }
    },

    syncAccessibility() {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }
      const buttons = root.querySelectorAll('[data-faq-question]');
      buttons.forEach((button, i) => {
        if (!(button instanceof HTMLElement)) {
          return;
        }
        const open = this.isOpen(i);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        const panelId = button.getAttribute('aria-controls');
        if (!panelId) {
          return;
        }
        const panel = root.querySelector(`#${CSS.escape(panelId)}`);
        if (!(panel instanceof HTMLElement)) {
          return;
        }
        panel.dataset.open = open ? 'true' : 'false';
      });
    },
  }));
}
