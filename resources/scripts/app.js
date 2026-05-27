import '../styles/app.css';

import Alpine from 'alpinejs';

import registerSiteHeaderAlpine from './alpine/site-header.js';
import registerThreeCardBlockAlpine from './alpine/three-card-block.js';
import registerSplitHighlightAlpine from './alpine/shop-split-highlight.js';
import registerHeroSliderAlpine from './alpine/hero-slider.js';
import registerHorizontalScrollerAlpine from './alpine/horizontal-scroller.js';
import registerDirectoryArchiveAlpine from './alpine/directory-archive.js';
import registerVideoBlockAlpine from './alpine/video-block.js';
import registerFooterMenuAccordionAlpine from './alpine/footer-menu-accordion.js';
import registerFaqAlpine from './alpine/faq.js';
import registerTravelCalculatorAlpine from './alpine/travel-calculator.js';
import registerTextImageSliderAlpine from './alpine/text-image-slider.js';
import registerContactAlpine from './alpine/contact.js';
import registerCentreMapAlpine from './alpine/centre-map.js';
import registerShopRelatedShopsAlpine from './alpine/shop-related-shops.js';
import gsapManager from './utils/gsap-manager.js';
import backgroundParallaxManager from './utils/background-parallax-manager.js';
import initSplideCarousels from './utils/splide-init.js';
import { initPageHashNavigation, isHashNavigationActive } from './utils/page-anchor.js';

registerSiteHeaderAlpine(Alpine);
registerThreeCardBlockAlpine(Alpine);
registerSplitHighlightAlpine(Alpine);
registerHeroSliderAlpine(Alpine);
registerHorizontalScrollerAlpine(Alpine);
registerDirectoryArchiveAlpine(Alpine);
registerVideoBlockAlpine(Alpine);
registerFooterMenuAccordionAlpine(Alpine);
registerFaqAlpine(Alpine);
registerTravelCalculatorAlpine(Alpine);
registerTextImageSliderAlpine(Alpine);
registerContactAlpine(Alpine);
registerCentreMapAlpine(Alpine);
registerShopRelatedShopsAlpine(Alpine);
window.Alpine = Alpine;
Alpine.start();

gsapManager.init();
backgroundParallaxManager.init();
initPageHashNavigation();

document.addEventListener('DOMContentLoaded', () => {
  initSplideCarousels(document.body);
});

/**
 * ScrollSmoother (desktop ≥1024px) measures `#smooth-content` for max scroll; refresh after layout
 * settles so the footer isn’t clipped. ScrollSmoother already attaches its own ResizeObserver — do not
 * observe `#smooth-content` again here (duplicate refreshes reset scroll / kill smoothing on first wheel).
 */
let scrollLayoutDebounceId;
let refreshDeferred = false;
let refreshIdleTimer;

/** True while ScrollSmoother is mid wheel / programmatic glide — never refresh then. */
function isSmootherScrolling() {
  const smoother = window.smoother;
  if (!smoother || typeof smoother.getVelocity !== 'function') {
    return false;
  }

  return Math.abs(smoother.getVelocity()) > 0.05;
}

function shouldDeferScrollLayoutRefresh() {
  return isHashNavigationActive() || isSmootherScrolling();
}

function queueDeferredScrollLayoutRefresh() {
  refreshDeferred = true;
  clearTimeout(refreshIdleTimer);
  refreshIdleTimer = window.setTimeout(() => {
    if (shouldDeferScrollLayoutRefresh()) {
      queueDeferredScrollLayoutRefresh();
      return;
    }
    refreshDeferred = false;
    runScrollLayoutRefresh();
  }, 180);
}

function runScrollLayoutRefresh() {
  if (!window.ScrollTrigger) {
    return;
  }

  if (shouldDeferScrollLayoutRefresh()) {
    queueDeferredScrollLayoutRefresh();
    return;
  }

  const smoother = window.smoother;
  const savedScroll =
    smoother && typeof smoother.scrollTop === 'function'
      ? smoother.scrollTop()
      : (window.scrollY ?? document.documentElement.scrollTop ?? 0);

  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      if (shouldDeferScrollLayoutRefresh()) {
        queueDeferredScrollLayoutRefresh();
        return;
      }

      window.ScrollTrigger.refresh();
      if (smoother && typeof smoother.refresh === 'function') {
        smoother.refresh();
      }

      if (smoother && typeof smoother.scrollTop === 'function') {
        smoother.scrollTop(savedScroll);
      } else {
        window.scrollTo({ top: savedScroll, behavior: 'auto' });
      }
    });
  });
}

/** Debounced coalesces fonts/layout bursts; immediate skips debounce for intentional breakpoints. */
function scheduleScrollLayoutRefresh(immediate = false) {
  if (!window.ScrollTrigger) {
    return;
  }
  if (shouldDeferScrollLayoutRefresh()) {
    queueDeferredScrollLayoutRefresh();
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
  }, 480);
}

window.addEventListener(
  'load',
  () => {
    scheduleScrollLayoutRefresh(true);
  },
  { once: true }
);

window.addEventListener('gsap:smoother:ready', (event) => {
  if (!event.detail?.smoother) {
    return;
  }
  scheduleScrollLayoutRefresh(true);
});

window.addEventListener('culvers:header-offset', () => scheduleScrollLayoutRefresh(false));

window.addEventListener('culvers:page-anchor-idle', () => {
  queueDeferredScrollLayoutRefresh();
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
