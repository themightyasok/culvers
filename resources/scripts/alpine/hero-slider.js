/**
 * Hero slider: Splide carousel + breakpoint-driven copy motion (Alpine lifecycle).
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
import Splide from '@splidejs/splide';

export default function registerHeroSliderAlpine(Alpine) {
  Alpine.data('heroSlider', () => ({
    /** @type {InstanceType<typeof Splide> | null} */
    splide: null,

    /** @type {(() => void) | undefined} */
    boundOnResize: undefined,

    /** @type {ReturnType<typeof setTimeout> | undefined} */
    resizeTimer: undefined,

    init() {
      const root = this.$root;
      const splideRoot = this.$refs.splideRoot;
      if (!(root instanceof HTMLElement) || !(splideRoot instanceof HTMLElement)) {
        return;
      }

      this.boundOnResize = this.onResize.bind(this);
      window.addEventListener('resize', this.boundOnResize, { passive: true });

      const slideCount = Number.parseInt(root.dataset.heroSlideCount || '0', 10);

      this.splide = new Splide(splideRoot, {
        type: 'slide',
        rewind: true,
        speed: 950,
        easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
        arrows: false,
        pagination: slideCount > 1,
        drag: true,
        gap: 0,
        height: '100%',
        cover: true,
        trimSpace: false,
        autoplay: slideCount > 1,
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
      const splideInstance = this.splide;
      window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
          if (this.splide === splideInstance && typeof splideInstance.refresh === 'function') {
            splideInstance.refresh();
          }
        });
      });
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

    teardown() {
      window.clearTimeout(this.resizeTimer);
      if (this.boundOnResize) {
        window.removeEventListener('resize', this.boundOnResize);
      }
      if (this.splide) {
        this.splide.destroy();
        this.splide = null;
      }
    },
  }));
}
