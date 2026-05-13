/**
 * Text-image slider: a vertical stack of large Canela headlines that expand
 * in place to reveal a body paragraph and two polaroid-style images that
 * pop in from the left and right with a staggered scale/rotate animation.
 * Headlines stay full-contrast regardless of open state (see Blade `text-black`).
 *
 * Animation choreography per open row:
 *   1. Panel height transitions via CSS grid-rows trick.
 *   2. Body fades up (opacity 0 → 1, y 16 → 0)             [delay 60ms].
 *   3. Left polaroid pops in — wrapper: opacity / xPercent / yPercent; inner
 *      [data-tis-polaroid]: scale + rotate (shadow stays aligned with tilt).
 *   4. Right polaroid (mirrored).
 *
 * Honours `prefers-reduced-motion` — falls back to a 1-frame fade-in.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
import { gsap } from 'gsap';

export default function registerTextImageSliderAlpine(Alpine) {
  Alpine.data('textImageSlider', (config = {}) => ({
    /** @type {'single' | 'multi'} */
    mode: config.mode === 'multi' ? 'multi' : 'single',
    /** @type {number[]} */
    openIndices: Array.isArray(config.defaultOpen) ? [...config.defaultOpen] : [],
    /** @type {boolean} */
    reducedMotion: false,

    init() {
      if (this.mode === 'single' && this.openIndices.length > 1) {
        this.openIndices = [this.openIndices[0]];
      }
      this.reducedMotion =
        typeof window !== 'undefined' && window.matchMedia
          ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
          : false;
      this.syncDom();
      // Animate any pre-opened rows on load.
      this.$nextTick(() => {
        this.openIndices.forEach((i) => this.playPopIn(i));
      });
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
      if (!wasOpen) {
        this.$nextTick(() => this.playPopIn(index));
      }
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

    /** @param {number} index */
    playPopIn(index) {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }
      const item = root.querySelector(`[data-tis-item="${index}"]`);
      if (!(item instanceof HTMLElement)) {
        return;
      }
      const body = item.querySelector('[data-tis-body]');
      /* Desktop wrappers live inside the panel; mobile wrappers live outside as
         siblings. The two sets are size-class gated (hidden lg:contents / lg:hidden)
         so we animate BOTH — only the visible set actually paints. */
      const lefts = item.querySelectorAll(
        '[data-tis-media="left"], [data-tis-media="left-mobile"]'
      );
      const rights = item.querySelectorAll(
        '[data-tis-media="right"], [data-tis-media="right-mobile"]'
      );

      if (this.reducedMotion) {
        if (body instanceof HTMLElement) {
          gsap.set(body, { clearProps: 'all', opacity: 1 });
        }
        this.applyTilts(item);
        return;
      }

      const tl = gsap.timeline({ defaults: { overwrite: 'auto' } });

      if (body instanceof HTMLElement) {
        tl.fromTo(
          body,
          { opacity: 0, y: 16 },
          { opacity: 1, y: 0, duration: 0.45, ease: 'power2.out', delay: 0.06 },
          0
        );
      }

      /* Desktop wrappers are absolutely positioned at top:50% and need to be
         offset up by 50% of their own height to be visually centered against
         the body. Mobile wrappers live in normal flow and only need 0. */
      const baselineY = (el) =>
        el.dataset.tisMedia === 'left' || el.dataset.tisMedia === 'right' ? -50 : 0;

      lefts.forEach((el) => {
        if (!(el instanceof HTMLElement)) {
          return;
        }
        const polaroid = el.querySelector('[data-tis-polaroid]');
        if (!(polaroid instanceof HTMLElement)) {
          return;
        }
        const tilt = Number(el.dataset.tilt || 0);
        const base = baselineY(el);
        tl.fromTo(
          el,
          { opacity: 0, xPercent: -20, yPercent: base + 4 },
          {
            opacity: 1,
            xPercent: 0,
            yPercent: base,
            duration: 0.7,
            ease: 'back.out(1.6)',
          },
          0.2
        );
        tl.fromTo(
          polaroid,
          { scale: 0.6, rotate: 0 },
          {
            scale: 1,
            rotate: tilt,
            duration: 0.7,
            ease: 'back.out(1.6)',
          },
          0.2
        );
      });

      rights.forEach((el) => {
        if (!(el instanceof HTMLElement)) {
          return;
        }
        const polaroid = el.querySelector('[data-tis-polaroid]');
        if (!(polaroid instanceof HTMLElement)) {
          return;
        }
        const tilt = Number(el.dataset.tilt || 0);
        const base = baselineY(el);
        tl.fromTo(
          el,
          { opacity: 0, xPercent: 20, yPercent: base + 4 },
          {
            opacity: 1,
            xPercent: 0,
            yPercent: base,
            duration: 0.7,
            ease: 'back.out(1.6)',
          },
          0.32
        );
        tl.fromTo(
          polaroid,
          { scale: 0.6, rotate: 0 },
          {
            scale: 1,
            rotate: tilt,
            duration: 0.7,
            ease: 'back.out(1.6)',
          },
          0.32
        );
      });
    },

    /** @param {HTMLElement} item */
    applyTilts(item) {
      const tiles = item.querySelectorAll('[data-tis-media]');
      tiles.forEach((el) => {
        if (!(el instanceof HTMLElement)) {
          return;
        }
        const polaroid = el.querySelector('[data-tis-polaroid]');
        if (!(polaroid instanceof HTMLElement)) {
          return;
        }
        const isDesktop = el.dataset.tisMedia === 'left' || el.dataset.tisMedia === 'right';
        gsap.set(el, {
          opacity: 1,
          xPercent: 0,
          yPercent: isDesktop ? -50 : 0,
        });
        gsap.set(polaroid, {
          rotate: Number(el.dataset.tilt || 0),
          scale: 1,
        });
      });
    },
  }));
}
