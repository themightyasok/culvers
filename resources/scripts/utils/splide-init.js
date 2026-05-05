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

      let options = {};
      const raw = el.getAttribute('data-splide-options');
      if (raw) {
        try {
          options = JSON.parse(raw);
        } catch {
          options = {};
        }
      }

      const instance = new Splide(el, options);
      instance.mount();
      el.dataset.splideMounted = '1';
    });
}

export default initSplideCarousels;
