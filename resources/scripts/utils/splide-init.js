/**
 * Mount Splide carousels on `[data-splide]` roots (expects Splide markup inside).
 */
import Splide from '@splidejs/splide';

export function initSplideCarousels(root = document) {
  if (!root?.querySelectorAll) return;

  root
    .querySelectorAll('[data-splide]:not([data-splide-mounted]):not([data-splide-manual])')
    .forEach((el) => {
      if (!(el instanceof HTMLElement)) return;

      const slideCount = el.querySelectorAll('.splide__slide').length;
      let options = { type: slideCount > 1 ? 'loop' : 'slide' };
      const raw = el.getAttribute('data-splide-options');
      if (raw) {
        try {
          options = { type: slideCount > 1 ? 'loop' : 'slide', ...JSON.parse(raw) };
        } catch {
          options = { type: slideCount > 1 ? 'loop' : 'slide' };
        }
      }

      const instance = new Splide(el, options);
      instance.mount();
      el.dataset.splideMounted = '1';
    });
}

export default initSplideCarousels;
