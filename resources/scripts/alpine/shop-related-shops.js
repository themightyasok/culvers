/**
 * Shop related shops — mobile Splide (one directory card per slide).
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
import Splide from '@splidejs/splide';

/** @type {import('@splidejs/splide').Options} */
const SHOP_RELATED_SPLIDE_OPTIONS = {
  type: 'loop',
  speed: 600,
  arrows: false,
  pagination: true,
  drag: true,
  gap: '22px',
  perPage: 1,
  perMove: 1,
  trimSpace: false,
};

export default function registerShopRelatedShopsAlpine(Alpine) {
  Alpine.data('shopRelatedShops', () => ({
    /** @type {InstanceType<typeof Splide> | null} */
    splide: null,

    /** @type {HTMLElement | null} */
    splideRoot: null,

    /** @type {(() => void) | undefined} */
    boundOnResize: undefined,

    shouldUseSplide() {
      return window.matchMedia('(max-width: 639px)').matches;
    },

    init() {
      this.boundOnResize = () => {
        if (this.shouldUseSplide()) {
          this.mountSplide();
        } else {
          this.destroySplide();
        }
      };
      window.addEventListener('resize', this.boundOnResize);
      this.mountSplide();
    },

    destroy() {
      if (this.boundOnResize) {
        window.removeEventListener('resize', this.boundOnResize);
        this.boundOnResize = undefined;
      }
      this.destroySplide();
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

    mountSplide() {
      if (!this.shouldUseSplide()) {
        this.destroySplide();
        return;
      }

      const root = this.$refs.splideRoot;
      if (!(root instanceof HTMLElement)) {
        return;
      }

      const slideCount = root.querySelectorAll('.splide__slide').length;
      if (slideCount === 0) {
        this.destroySplide();
        return;
      }

      if (this.splideRoot === root && this.splide) {
        this.splide.refresh();
        return;
      }

      this.destroySplide();
      this.splideRoot = root;

      this.splide = new Splide(root, {
        ...SHOP_RELATED_SPLIDE_OPTIONS,
        type: slideCount > 1 ? 'loop' : 'slide',
        pagination: slideCount > 1,
      });
      this.splide.on('mounted', () => {
        requestAnimationFrame(() => {
          this.splide?.refresh();
        });
      });
      this.splide.mount();
      root.dataset.splideMounted = '1';

      requestAnimationFrame(() => {
        this.splide?.refresh();
      });
    },
  }));
}
