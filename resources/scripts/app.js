import '../styles/app.css';

import Alpine from 'alpinejs';

import registerSiteHeaderAlpine from './alpine/site-header.js';
import gsapManager from './utils/gsap-manager.js';
import fullScreenScrollManager from './utils/full-screen-scroll-manager.js';
import backgroundParallaxManager from './utils/background-parallax-manager.js';
import initSplideCarousels from './utils/splide-init.js';

registerSiteHeaderAlpine(Alpine);
Alpine.start();

gsapManager.init();
fullScreenScrollManager.init();
backgroundParallaxManager.init();

document.addEventListener('DOMContentLoaded', () => {
  initSplideCarousels(document.body);
});

const runScrollTriggerRefresh = () => {
  if (!window.ScrollTrigger) return;
  requestAnimationFrame(() => {
    requestAnimationFrame(() => window.ScrollTrigger.refresh());
  });
};

window.addEventListener('load', runScrollTriggerRefresh, { once: true });
window.addEventListener('gsap:smoother:ready', runScrollTriggerRefresh, { once: true });
