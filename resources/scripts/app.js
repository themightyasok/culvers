import '../styles/app.css';

import Alpine from 'alpinejs';

import registerSiteHeaderAlpine from './alpine/site-header.js';
import registerThreeCardBlockAlpine from './alpine/three-card-block.js';
import registerHeroSliderAlpine from './alpine/hero-slider.js';
import registerHorizontalScrollerAlpine from './alpine/horizontal-scroller.js';
import registerDirectoryArchiveAlpine from './alpine/directory-archive.js';
import registerVideoBlockAlpine from './alpine/video-block.js';
import registerFooterMenuAccordionAlpine from './alpine/footer-menu-accordion.js';
import gsapManager from './utils/gsap-manager.js';
import fullScreenScrollManager from './utils/full-screen-scroll-manager.js';
import backgroundParallaxManager from './utils/background-parallax-manager.js';
import initSplideCarousels from './utils/splide-init.js';

registerSiteHeaderAlpine(Alpine);
registerThreeCardBlockAlpine(Alpine);
registerHeroSliderAlpine(Alpine);
registerHorizontalScrollerAlpine(Alpine);
registerDirectoryArchiveAlpine(Alpine);
registerVideoBlockAlpine(Alpine);
registerFooterMenuAccordionAlpine(Alpine);
Alpine.start();

gsapManager.init();
fullScreenScrollManager.init();
backgroundParallaxManager.init();

document.addEventListener('DOMContentLoaded', () => {
  initSplideCarousels(document.body);
});

/**
 * ScrollSmoother (desktop ≥1024px) measures `#smooth-content` for max scroll; refresh after layout
 * settles so the footer isn’t clipped. ScrollSmoother already attaches its own ResizeObserver — do not
 * observe `#smooth-content` again here (duplicate refreshes reset scroll / kill smoothing on first wheel).
 */
let scrollLayoutDebounceId;

function runScrollLayoutRefresh() {
  if (!window.ScrollTrigger) {
    return;
  }
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      window.ScrollTrigger.refresh();
      const smoother = window.smoother;
      if (smoother && typeof smoother.refresh === 'function') {
        smoother.refresh();
      }
    });
  });
}

/** Debounced coalesces fonts/layout bursts; immediate skips debounce for intentional breakpoints. */
function scheduleScrollLayoutRefresh(immediate = false) {
  if (!window.ScrollTrigger) {
    return;
  }
  if (immediate) {
    clearTimeout(scrollLayoutDebounceId);
    scrollLayoutDebounceId = undefined;
    runScrollLayoutRefresh();
    return;
  }
  clearTimeout(scrollLayoutDebounceId);
  scrollLayoutDebounceId = window.setTimeout(() => {
    scrollLayoutDebounceId = undefined;
    runScrollLayoutRefresh();
  }, 320);
}

window.addEventListener(
  'load',
  () => {
    scheduleScrollLayoutRefresh(true);
    if (window.matchMedia('(min-width: 1024px)').matches) {
      window.setTimeout(() => scheduleScrollLayoutRefresh(true), 1100);
    }
  },
  { once: true }
);

window.addEventListener('gsap:smoother:ready', (event) => {
  if (!event.detail?.smoother) {
    return;
  }
  scheduleScrollLayoutRefresh(true);
  window.setTimeout(() => scheduleScrollLayoutRefresh(true), 480);
});

if (
  typeof document !== 'undefined' &&
  document.fonts &&
  typeof document.fonts.ready?.then === 'function'
) {
  document.fonts.ready.then(() => scheduleScrollLayoutRefresh());
}

let resizeRefreshTimer;
window.addEventListener('resize', () => {
  clearTimeout(resizeRefreshTimer);
  resizeRefreshTimer = window.setTimeout(() => scheduleScrollLayoutRefresh(true), 200);
});
