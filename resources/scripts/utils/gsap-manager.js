/**
 * GSAP bootstrap for the Culvers theme — ScrollSmoother (desktop), ScrollTrigger
 * config, and viewport-aware ticker registration for the horizontal scroller.
 */

import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollSmoother } from 'gsap/ScrollSmoother';
import { Observer } from 'gsap/Observer';

gsap.registerPlugin(ScrollTrigger, ScrollSmoother, Observer);

class GSAPManager {
  constructor() {
    this.smoother = null;
    this.isInitialized = false;
    this.components = new Set();
    this.matchMedia = null;
    this.tickers = new Set();
  }

  init() {
    if (this.isInitialized) return this.smoother;

    ScrollTrigger.config({
      autoRefreshEvents: 'visibilitychange,DOMContentLoaded,load,resize',
      limitCallbacks: true,
      anticipatePin: 1,
    });

    gsap.ticker.lagSmoothing(1000, 20);
    gsap.defaults({ overwrite: 'auto' });

    window.gsap = gsap;
    window.ScrollTrigger = ScrollTrigger;
    window.ScrollSmoother = ScrollSmoother;
    window.Observer = Observer;
    window.smoother = null;

    this.matchMedia = gsap.matchMedia();

    const isMobile = window.matchMedia('(max-width: 1023px)').matches;
    if (isMobile) {
      window.dispatchEvent(
        new CustomEvent('gsap:smoother:ready', {
          detail: { smoother: null },
        })
      );
    }

    this.matchMedia.add('(min-width: 1024px)', () => {
      const wrapper = document.getElementById('smooth-wrapper');
      const content = document.getElementById('smooth-content');

      if (wrapper && content) {
        this.smoother = ScrollSmoother.create({
          smooth: 1,
          effects: false,
          normalizeScroll: false,
          ignoreMobileResize: true,
        });
        window.smoother = this.smoother;
        window.dispatchEvent(
          new CustomEvent('gsap:smoother:ready', {
            detail: { smoother: this.smoother },
          })
        );

        return () => {
          if (this.smoother) {
            this.smoother.kill();
            this.smoother = null;
          }
          window.smoother = null;
        };
      }
      return () => {};
    });

    window.gsapMatchMedia = this.matchMedia;
    this.isInitialized = true;

    return this.smoother;
  }

  registerComponent(component) {
    if (!component || this.components.has(component)) return;
    this.components.add(component);
  }

  unregisterComponent(component) {
    if (!component || !this.components.has(component)) return;
    this.components.delete(component);
  }

  registerTicker(tickerFunction, element) {
    if (!tickerFunction || !element) return;

    return ScrollTrigger.create({
      trigger: element,
      start: 'top bottom',
      end: 'bottom top',
      onEnter: () => {
        gsap.ticker.add(tickerFunction);
        this.tickers.add(tickerFunction);
      },
      onEnterBack: () => {
        gsap.ticker.add(tickerFunction);
        this.tickers.add(tickerFunction);
      },
      onLeave: () => {
        gsap.ticker.remove(tickerFunction);
        this.tickers.delete(tickerFunction);
      },
      onLeaveBack: () => {
        gsap.ticker.remove(tickerFunction);
        this.tickers.delete(tickerFunction);
      },
    });
  }

  destroy() {
    this.tickers.forEach((tickerFunction) => {
      gsap.ticker.remove(tickerFunction);
    });
    this.tickers.clear();
    this.components.clear();

    if (this.matchMedia?.revert) {
      this.matchMedia.revert();
      this.matchMedia = null;
    }

    if (this.smoother) {
      this.smoother.kill();
      this.smoother = null;
    }

    window.smoother = null;
    ScrollTrigger.killAll();
    this.isInitialized = false;
  }
}

const gsapManager = new GSAPManager();

window.gsapManager = gsapManager;

export default gsapManager;
