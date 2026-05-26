/**
 * Text-image slider — accordion rows open via CSS grid-template-rows. Side
 * polaroids (desktop) and the mobile image animate in/out with a soft bounce
 * when rows switch; respects prefers-reduced-motion.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export default function registerTextImageSliderAlpine(Alpine) {
  Alpine.data('textImageSlider', (config = {}) => ({
    /** @type {'single' | 'multi'} */
    mode: config.mode === 'multi' ? 'multi' : 'single',
    /** @type {number[]} */
    openIndices: Array.isArray(config.defaultOpen) ? [...config.defaultOpen] : [],
    /** @type {boolean} */
    _mediaReady: false,

    init() {
      if (this.mode === 'single' && this.openIndices.length > 1) {
        this.openIndices = [this.openIndices[0]];
      }
      this.syncDom();
      this.$nextTick(() => {
        this.syncMediaAnimations(true);
        this._mediaReady = true;
      });
    },

    destroy() {
      const root = this.$root;
      if (!(root instanceof HTMLElement) || typeof window.gsap === 'undefined') {
        return;
      }
      root.querySelectorAll('[data-tis-media-motion]').forEach((el) => {
        window.gsap.killTweensOf(el);
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

      if (this._mediaReady) {
        this.$nextTick(() => {
          this.syncMediaAnimations(false);
        });
      }
    },

    /**
     * Bounce polaroids in when a row opens; ease them out when it closes.
     *
     * @param {boolean} [initial=false] Skip motion on first paint for default-open rows.
     */
    syncMediaAnimations(initial = false) {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }

      const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      const gsap = typeof window.gsap !== 'undefined' ? window.gsap : null;

      root.querySelectorAll('[data-tis-item]').forEach((item) => {
        if (!(item instanceof HTMLElement)) {
          return;
        }
        const index = Number(item.dataset.tisItem);
        if (Number.isNaN(index)) {
          return;
        }
        const open = this.isOpen(index);
        const motionEls = item.querySelectorAll('[data-tis-media-motion]');

        motionEls.forEach((motionEl, mediaIdx) => {
          if (!(motionEl instanceof HTMLElement)) {
            return;
          }
          const mediaRoot = motionEl.closest('[data-tis-media]');
          if (!(mediaRoot instanceof HTMLElement)) {
            return;
          }

          const side = mediaRoot.dataset.tisMedia ?? '';
          const isLeft = side === 'left';
          const isRight = side === 'right';
          const isMobile = mediaRoot.classList.contains('text-image-slider__media--mobile');
          const restScale = isLeft ? 0.98 : isRight ? 1.02 : 1;
          const enterFromX = isMobile ? 0 : isLeft ? -24 : isRight ? 24 : 0;
          const enterFromY = isMobile ? 14 : 20;

          if (gsap) {
            gsap.killTweensOf(motionEl);
          }

          /** Mobile accordion image: no bounce/scale — show/hide instantly with the row. */
          if (initial || isMobile || reducedMotion || !gsap) {
            mediaRoot.classList.toggle('is-active', open);
            motionEl.style.opacity = open ? '1' : '0';
            motionEl.style.visibility = open ? 'visible' : 'hidden';
            if (gsap) {
              gsap.set(motionEl, {
                scale: open ? restScale : 1,
                x: 0,
                y: 0,
              });
            } else {
              motionEl.style.transform = open && restScale !== 1 ? `scale(${restScale})` : '';
            }
            return;
          }

          if (open) {
            mediaRoot.classList.add('is-active');
            motionEl.style.visibility = 'visible';

            gsap.fromTo(
              motionEl,
              {
                opacity: 0,
                scale: 0.84,
                x: enterFromX,
                y: enterFromY,
              },
              {
                opacity: 1,
                scale: restScale,
                x: 0,
                y: 0,
                duration: 0.68,
                ease: 'back.out(1.35)',
                delay: (isMobile ? 0.08 : 0.14) + mediaIdx * 0.09,
                overwrite: true,
              }
            );
            return;
          }

          gsap.to(motionEl, {
            opacity: 0,
            scale: 0.9,
            y: -12,
            duration: 0.3,
            ease: 'power2.in',
            overwrite: true,
            onComplete: () => {
              mediaRoot.classList.remove('is-active');
              motionEl.style.visibility = 'hidden';
              gsap.set(motionEl, { x: 0, scale: 1, y: 0 });
            },
          });
        });
      });
    },
  }));
}
