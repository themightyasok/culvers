/**
 * Hero slider: Splide carousel + breakpoint-driven copy motion (Alpine lifecycle).
 *
 * Accessibility: respects `prefers-reduced-motion: reduce` by suppressing autoplay
 * (WCAG 2.2.2 / 2.3.3 — no unsolicited motion). Manual navigation, drag, and
 * pagination remain enabled — user-initiated motion is fine under reduce.
 * The handler is reactive: if the user toggles the OS preference at runtime,
 * autoplay pauses / resumes without a reload.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
import Splide from '@splidejs/splide';

const REDUCE_MOTION_QUERY = '(prefers-reduced-motion: reduce)';

export default function registerHeroSliderAlpine(Alpine) {
  Alpine.data('heroSlider', () => ({
    /** @type {InstanceType<typeof Splide> | null} */
    splide: null,

    /** @type {(() => void) | undefined} */
    boundOnResize: undefined,

    /** @type {ReturnType<typeof setTimeout> | undefined} */
    resizeTimer: undefined,

    /** @type {MediaQueryList | null} */
    reduceMql: null,

    /** @type {((event: MediaQueryListEvent) => void) | undefined} */
    boundOnReduceChange: undefined,

    /** @type {number} */
    slideCount: 0,

    /** @type {boolean} */
    reducedMotion: false,

    init() {
      const root = this.$root;
      const splideRoot = this.$refs.splideRoot;
      if (!(root instanceof HTMLElement) || !(splideRoot instanceof HTMLElement)) {
        return;
      }

      this.boundOnResize = this.onResize.bind(this);
      window.addEventListener('resize', this.boundOnResize, { passive: true });

      this.slideCount = Number.parseInt(root.dataset.heroSlideCount || '0', 10);

      this.reduceMql =
        typeof window.matchMedia === 'function' ? window.matchMedia(REDUCE_MOTION_QUERY) : null;
      this.reducedMotion = !!(this.reduceMql && this.reduceMql.matches);
      if (this.reduceMql) {
        this.boundOnReduceChange = this.onReduceChange.bind(this);
        if (typeof this.reduceMql.addEventListener === 'function') {
          this.reduceMql.addEventListener('change', this.boundOnReduceChange);
        } else if (typeof this.reduceMql.addListener === 'function') {
          this.reduceMql.addListener(this.boundOnReduceChange);
        }
      }

      this.splide = new Splide(splideRoot, {
        type: this.slideCount > 1 ? 'loop' : 'slide',
        speed: 950,
        easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
        arrows: false,
        pagination: this.slideCount > 1,
        drag: true,
        gap: 0,
        height: '100%',
        cover: true,
        trimSpace: false,
        autoplay: this.slideCount > 1 && !this.reducedMotion,
        interval: 6500,
        pauseOnHover: true,
        pauseOnFocus: true,
        resetProgress: false,
        classes: {
          pagination: 'splide__pagination hero-slider__pagination',
          page: 'splide__pagination__page hero-slider__page',
        },
      });

      this.splide.mount();
    },

    destroy() {
      this.teardown();
    },

    onResize() {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }
      window.clearTimeout(this.resizeTimer);
      root.classList.add('hero-slider--bp-shift');
      this.resizeTimer = window.setTimeout(() => {
        root.classList.remove('hero-slider--bp-shift');
        if (this.splide && typeof this.splide.refresh === 'function') {
          this.splide.refresh();
        }
      }, 420);
    },

    /** @param {MediaQueryListEvent} event */
    onReduceChange(event) {
      this.reducedMotion = !!event.matches;
      if (!this.splide) {
        return;
      }
      /* Splide v4 exposes the Autoplay component on the live instance. Toggle it
         rather than re-mounting so drag / pagination state stay intact. */
      const autoplay = this.splide.Components && this.splide.Components.Autoplay;
      if (!autoplay) {
        return;
      }
      if (this.reducedMotion) {
        if (typeof autoplay.pause === 'function') {
          autoplay.pause();
        }
      } else if (this.slideCount > 1 && typeof autoplay.play === 'function') {
        autoplay.play();
      }
    },

    teardown() {
      window.clearTimeout(this.resizeTimer);
      if (this.boundOnResize) {
        window.removeEventListener('resize', this.boundOnResize);
      }
      if (this.reduceMql && this.boundOnReduceChange) {
        if (typeof this.reduceMql.removeEventListener === 'function') {
          this.reduceMql.removeEventListener('change', this.boundOnReduceChange);
        } else if (typeof this.reduceMql.removeListener === 'function') {
          this.reduceMql.removeListener(this.boundOnReduceChange);
        }
      }
      this.reduceMql = null;
      this.boundOnReduceChange = undefined;
      if (this.splide) {
        this.splide.destroy();
        this.splide = null;
      }
    },
  }));
}
