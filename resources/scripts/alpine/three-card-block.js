/**
 * Three card block: optional category tabs; video plays on hover/focus-in.
 * CPT / blog mode mounts Splide on the active tab panel (mobile carousel).
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
import Splide from '@splidejs/splide';

/** @type {import('@splidejs/splide').Options} */
const THREE_CARD_SPLIDE_OPTIONS = {
  type: 'slide',
  rewind: true,
  speed: 600,
  arrows: false,
  pagination: true,
  drag: true,
  gap: '1rem',
  perPage: 1,
  perMove: 1,
  trimSpace: false,
};

export default function registerThreeCardBlockAlpine(Alpine) {
  Alpine.data('threeCardBlock', () => ({
    activeTab: 0,

    /** @type {InstanceType<typeof Splide> | null} */
    splide: null,

    /** @type {HTMLElement | null} */
    splideRoot: null,

    /** @type {(() => void) | undefined} */
    boundOnResize: undefined,

    /** @type {ReturnType<typeof setTimeout> | undefined} */
    resizeTimer: undefined,

    /** @type {MediaQueryList | null} */
    mobileMql: null,

    /** @type {((event: MediaQueryListEvent) => void) | undefined} */
    boundOnMobileChange: undefined,

    shouldUseSplide() {
      return typeof window.matchMedia === 'function'
        ? window.matchMedia('(max-width: 639px)').matches
        : false;
    },

    bindMobileQuery() {
      if (typeof window.matchMedia !== 'function') {
        return;
      }

      this.mobileMql = window.matchMedia('(max-width: 639px)');
      this.boundOnMobileChange = () => {
        this.$nextTick(() => {
          this.mountActiveSplide();
        });
      };

      if (typeof this.mobileMql.addEventListener === 'function') {
        this.mobileMql.addEventListener('change', this.boundOnMobileChange);
      } else if (typeof this.mobileMql.addListener === 'function') {
        this.mobileMql.addListener(this.boundOnMobileChange);
      }
    },

    unbindMobileQuery() {
      if (this.mobileMql && this.boundOnMobileChange) {
        if (typeof this.mobileMql.removeEventListener === 'function') {
          this.mobileMql.removeEventListener('change', this.boundOnMobileChange);
        } else if (typeof this.mobileMql.removeListener === 'function') {
          this.mobileMql.removeListener(this.boundOnMobileChange);
        }
      }
      this.mobileMql = null;
      this.boundOnMobileChange = undefined;
    },

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
        this.mountActiveSplide();
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

    destroySplide() {
      if (this.splide) {
        this.splide.destroy(true);
        this.splide = null;
      }
      if (this.splideRoot instanceof HTMLElement) {
        delete this.splideRoot.dataset.splideMounted;
      }
      this.splideRoot = null;
    },

    findActiveSplideRoot() {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return null;
      }

      const panels = root.querySelectorAll('.three-card-block__panel');
      const panel = panels[this.activeTab];
      if (!(panel instanceof HTMLElement)) {
        return null;
      }

      const splideEl = panel.querySelector('[data-three-card-splide]');
      return splideEl instanceof HTMLElement ? splideEl : null;
    },

    mountActiveSplide() {
      if (!this.shouldUseSplide()) {
        this.destroySplide();
        return;
      }

      const nextRoot = this.findActiveSplideRoot();
      if (!nextRoot) {
        this.destroySplide();
        return;
      }

      if (this.splideRoot === nextRoot && this.splide) {
        this.splide.refresh();
        return;
      }

      this.destroySplide();
      this.splideRoot = nextRoot;

      const slideCount = nextRoot.querySelectorAll('.splide__slide').length;
      const options = {
        ...THREE_CARD_SPLIDE_OPTIONS,
        pagination: slideCount > 1,
      };

      this.splide = new Splide(nextRoot, options);
      this.splide.on('mounted', () => {
        requestAnimationFrame(() => {
          this.splide?.refresh();
        });
      });
      this.splide.mount();
      nextRoot.dataset.splideMounted = '1';
    },

    onResize() {
      clearTimeout(this.resizeTimer);
      this.resizeTimer = setTimeout(() => {
        if (this.splide) {
          this.splide.refresh();
        }
      }, 150);
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
      this.bindMobileQuery();
      this.boundOnResize = this.onResize.bind(this);
      window.addEventListener('resize', this.boundOnResize, { passive: true });

      this.$nextTick(() => {
        requestAnimationFrame(() => {
          this.mountActiveSplide();
          this.primeVideoFirstFrames();
          this.bindVideoHoverPlayback();
        });
      });
    },

    destroy() {
      clearTimeout(this.resizeTimer);
      this.unbindMobileQuery();
      if (this.boundOnResize) {
        window.removeEventListener('resize', this.boundOnResize);
      }
      this.destroySplide();
    },
  }));
}
