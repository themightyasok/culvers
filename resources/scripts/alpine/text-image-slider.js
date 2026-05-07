/**
 * Text-image slider: a vertical stack of large Canela headlines that expand
 * in place to reveal a body paragraph and two polaroid-style images that
 * pop in from the left and right with a staggered scale/rotate animation.
 * Inactive headlines fade to a muted tone while one row is open.
 *
 * Animation choreography per open row:
 *   1. Panel height transitions via CSS grid-rows trick.
 *   2. Body fades up (opacity 0 → 1, y 16 → 0)             [delay 60ms].
 *   3. Left image pops in (opacity 0 → 1, scale 0.6 → 1,
 *      rotate from 0 → tilt-deg, x -60 → 0)                [delay 200ms].
 *   4. Right image pops in (mirrored)                       [delay 320ms].
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
    isMuted(index) {
      return this.openIndices.length > 0 && !this.isOpen(index);
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
        if (open) {
          panel.removeAttribute('hidden');
        } else {
          panel.setAttribute('hidden', '');
        }
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
      const leftMedia = item.querySelector('[data-tis-media="left"]');
      const rightMedia = item.querySelector('[data-tis-media="right"]');

      if (this.reducedMotion) {
        [body, leftMedia, rightMedia].forEach((el) => {
          if (el instanceof HTMLElement) {
            gsap.set(el, { clearProps: 'all', opacity: 1 });
          }
        });
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

      const leftTilt = leftMedia instanceof HTMLElement ? Number(leftMedia.dataset.tilt || 0) : 0;
      const rightTilt =
        rightMedia instanceof HTMLElement ? Number(rightMedia.dataset.tilt || 0) : 0;

      if (leftMedia instanceof HTMLElement) {
        tl.fromTo(
          leftMedia,
          { opacity: 0, scale: 0.6, rotate: 0, x: -60, y: 20 },
          {
            opacity: 1,
            scale: 1,
            rotate: leftTilt,
            x: 0,
            y: 0,
            duration: 0.7,
            ease: 'back.out(1.6)',
          },
          0.2
        );
      }
      if (rightMedia instanceof HTMLElement) {
        tl.fromTo(
          rightMedia,
          { opacity: 0, scale: 0.6, rotate: 0, x: 60, y: 20 },
          {
            opacity: 1,
            scale: 1,
            rotate: rightTilt,
            x: 0,
            y: 0,
            duration: 0.7,
            ease: 'back.out(1.6)',
          },
          0.32
        );
      }
    },

    /** @param {HTMLElement} item */
    applyTilts(item) {
      const left = item.querySelector('[data-tis-media="left"]');
      const right = item.querySelector('[data-tis-media="right"]');
      if (left instanceof HTMLElement) {
        gsap.set(left, {
          rotate: Number(left.dataset.tilt || 0),
          opacity: 1,
          scale: 1,
          x: 0,
          y: 0,
        });
      }
      if (right instanceof HTMLElement) {
        gsap.set(right, {
          rotate: Number(right.dataset.tilt || 0),
          opacity: 1,
          scale: 1,
          x: 0,
          y: 0,
        });
      }
    },
  }));
}
