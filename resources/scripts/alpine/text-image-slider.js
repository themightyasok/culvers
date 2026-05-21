/**
 * Text-image slider — accordion rows open via CSS grid-template-rows; no
 * entrance animations on body or images (Figma mobile: body, CTA, then image).
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export default function registerTextImageSliderAlpine(Alpine) {
  Alpine.data('textImageSlider', (config = {}) => ({
    /** @type {'single' | 'multi'} */
    mode: config.mode === 'multi' ? 'multi' : 'single',
    /** @type {number[]} */
    openIndices: Array.isArray(config.defaultOpen) ? [...config.defaultOpen] : [],

    init() {
      if (this.mode === 'single' && this.openIndices.length > 1) {
        this.openIndices = [this.openIndices[0]];
      }
      this.syncDom();
    },

    /** @param {number} index */
    isOpen(index) {
      return this.openIndices.includes(index);
    },

    /** @param {number} index */
    toggle(index) {
      const wasOpen = this.isOpen(index);
      if (wasOpen) {
        this.openIndices = this.openIndices.filter((i) => i !== index);
      } else if (this.mode === 'single') {
        this.openIndices = [index];
      } else {
        this.openIndices = [...this.openIndices, index];
      }
      this.syncDom();
    },

    /**
     * @param {number} fromIndex
     * @param {1 | -1} delta
     */
    focusSibling(fromIndex, delta) {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }
      const buttons = root.querySelectorAll('[data-tis-label]');
      if (buttons.length === 0) {
        return;
      }
      const next = (fromIndex + delta + buttons.length) % buttons.length;
      const target = buttons[next];
      if (target instanceof HTMLElement) {
        target.focus();
      }
    },

    syncDom() {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }
      const labels = root.querySelectorAll('[data-tis-label]');
      labels.forEach((label, i) => {
        if (!(label instanceof HTMLElement)) {
          return;
        }
        const open = this.isOpen(i);
        label.setAttribute('aria-expanded', open ? 'true' : 'false');
        const panelId = label.getAttribute('aria-controls');
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
