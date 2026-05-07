/**
 * Three card block: optional category tabs; video plays on hover/focus-in.
 * Idle video cards always show decoded frame 0 (never a separate poster bitmap on `<video>`).
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export default function registerThreeCardBlockAlpine(Alpine) {
  Alpine.data('threeCardBlock', () => ({
    activeTab: 0,

    /**
     * @param {number} index
     * @param {boolean} [focus] When true (keyboard nav), move focus to the newly active tab.
     */
    selectTab(index, focus = false) {
      this.activeTab = index;
      this.syncTabAccessibility();
      if (focus) {
        const root = this.$root;
        if (root instanceof HTMLElement) {
          const next = root.querySelectorAll('[role="tab"]')[index];
          if (next instanceof HTMLElement) {
            next.focus();
          }
        }
      }
      this.$nextTick(() => {
        this.primeVideoFirstFrames();
        this.bindVideoHoverPlayback();
      });
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

    /**
     * Pause at frame 0 so the first decoded raster shows until hover/play (HAVE_CURRENT_DATA+).
     */
    primeVideoFirstFrames() {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }

      root.querySelectorAll('[data-three-card-video]').forEach((el) => {
        if (!(el instanceof HTMLVideoElement)) {
          return;
        }

        const snapToFirstFrame = () => {
          try {
            el.pause();
          } catch {
            /* ignore */
          }
          try {
            el.currentTime = 0;
          } catch {
            /* ignore */
          }
          requestAnimationFrame(() => {
            try {
              el.pause();
            } catch {
              /* ignore */
            }
          });
        };

        const tryNow = () => {
          if (el.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
            snapToFirstFrame();
            return true;
          }
          return false;
        };

        if (tryNow()) {
          return;
        }

        let painted = false;
        const oncePaint = () => {
          if (painted) {
            return;
          }
          painted = true;
          snapToFirstFrame();
        };

        el.addEventListener('loadeddata', oncePaint, { once: true });
        el.addEventListener('canplay', oncePaint, { once: true });
      });
    },

    bindVideoHoverPlayback() {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }

      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
      }

      root.querySelectorAll('a.three-card-block__card').forEach((card) => {
        if (!(card instanceof HTMLElement)) {
          return;
        }
        if (card.dataset.culversThreeCardHover === '1') {
          return;
        }

        const video = card.querySelector('[data-three-card-video]');
        if (!(video instanceof HTMLVideoElement)) {
          return;
        }

        card.dataset.culversThreeCardHover = '1';

        const playClip = () => {
          video.play().catch(() => {});
        };

        const pauseAtStart = () => {
          video.pause();
          try {
            video.currentTime = 0;
          } catch {
            /* ignore */
          }
          requestAnimationFrame(() => {
            try {
              video.pause();
            } catch {
              /* ignore */
            }
          });
        };

        card.addEventListener('mouseenter', playClip);
        card.addEventListener('mouseleave', pauseAtStart);
        card.addEventListener('focusin', playClip);
        card.addEventListener('focusout', pauseAtStart);
      });
    },

    init() {
      this.syncTabAccessibility();
      this.$nextTick(() => {
        this.primeVideoFirstFrames();
        this.bindVideoHoverPlayback();
      });
    },
  }));
}
