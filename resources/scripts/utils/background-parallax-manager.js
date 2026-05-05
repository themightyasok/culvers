/**
 * Background Parallax Manager
 *
 * Adds subtle scroll-based parallax to shared component background images.
 * Enabled on desktop only and disabled for reduced-motion users.
 */
class BackgroundParallaxManager {
  constructor() {
    this.initialized = false;
    this.enabled = false;
    this.tweens = [];

    this.desktopQuery = window.matchMedia(
      '(min-width: 900px) and (hover: hover) and (pointer: fine)'
    );
    this.reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

    this.boundHandleModeChange = this.handleModeChange.bind(this);
    this.boundRebuild = this.rebuild.bind(this);
  }

  init() {
    if (this.initialized) return;
    this.initialized = true;

    if (typeof window.gsap === 'undefined' || typeof window.ScrollTrigger === 'undefined') {
      return;
    }

    this.addMediaQueryListener(this.desktopQuery, this.boundHandleModeChange);
    this.addMediaQueryListener(this.reducedMotionQuery, this.boundHandleModeChange);

    window.addEventListener('load', this.boundRebuild);
    window.addEventListener('gsap:smoother:ready', this.boundRebuild);

    requestAnimationFrame(() => {
      requestAnimationFrame(() => this.handleModeChange());
    });
  }

  addMediaQueryListener(mediaQuery, handler) {
    if (!mediaQuery) return;
    if (typeof mediaQuery.addEventListener === 'function') {
      mediaQuery.addEventListener('change', handler);
      return;
    }
    if (typeof mediaQuery.addListener === 'function') {
      mediaQuery.addListener(handler);
    }
  }

  removeMediaQueryListener(mediaQuery, handler) {
    if (!mediaQuery) return;
    if (typeof mediaQuery.removeEventListener === 'function') {
      mediaQuery.removeEventListener('change', handler);
      return;
    }
    if (typeof mediaQuery.removeListener === 'function') {
      mediaQuery.removeListener(handler);
    }
  }

  collectTargets() {
    return Array.from(document.querySelectorAll('[data-background-parallax-image]')).filter(
      (el) => el instanceof HTMLElement
    );
  }

  getComponentRoot(element) {
    let current = element.parentElement;
    while (current) {
      if (current.hasAttribute?.('data-component-root')) return current;
      const classes = Array.from(current.classList || []);
      if (classes.some((className) => className.endsWith('-component'))) {
        return current;
      }
      current = current.parentElement;
    }
    return null;
  }

  getTriggerForTarget(target) {
    const explicit = target.closest('[data-background-parallax-trigger]');
    if (explicit instanceof HTMLElement) {
      return explicit;
    }
    return (
      this.getComponentRoot(target) || target.closest('.col-span-full') || target.parentElement
    );
  }

  handleModeChange() {
    const canEnable =
      this.desktopQuery.matches &&
      !this.reducedMotionQuery.matches &&
      typeof window.gsap !== 'undefined' &&
      typeof window.ScrollTrigger !== 'undefined';

    if (!canEnable) {
      this.disable();
      return;
    }

    this.enable();
  }

  enable() {
    if (this.enabled) {
      this.rebuild();
      return;
    }

    this.enabled = true;
    this.rebuild();
  }

  disable() {
    if (!this.enabled && this.tweens.length === 0) return;
    this.killTweens();
    this.enabled = false;
  }

  rebuild() {
    if (!this.enabled) return;

    const gsap = window.gsap;
    const ScrollTrigger = window.ScrollTrigger;
    if (!gsap || !ScrollTrigger) return;

    this.killTweens();

    const targets = this.collectTargets();
    const parallaxDistance = Math.round(
      Math.min(106, Math.max(40, window.innerHeight * 0.088))
    ); /* +10% — matches reference theme feel */
    targets.forEach((target, index) => {
      const triggerEl = this.getTriggerForTarget(target);
      if (!(triggerEl instanceof Element)) return;

      const horizontal = target.getAttribute('data-background-parallax-axis') === 'x';
      const fromVars = horizontal
        ? { x: parallaxDistance, scale: 1.08, force3D: true }
        : { y: -parallaxDistance, scale: 1.08, force3D: true };
      const toVars = horizontal
        ? { x: -parallaxDistance, scale: 1.08, ease: 'none' }
        : { y: parallaxDistance, scale: 1.08, ease: 'none' };

      const tween = gsap.fromTo(target, fromVars, {
        ...toVars,
        scrollTrigger: {
          id: `background-parallax-${index}`,
          trigger: triggerEl,
          start: 'top bottom',
          end: 'bottom top',
          scrub: true,
          invalidateOnRefresh: true,
        },
      });

      this.tweens.push(tween);
    });
  }

  killTweens() {
    this.tweens.forEach((tween) => {
      tween.scrollTrigger?.kill();
      tween.kill();
    });
    this.tweens = [];

    this.collectTargets().forEach((el) => {
      el.style.removeProperty('transform');
      el.style.removeProperty('will-change');
    });
  }

  destroy() {
    this.disable();
    this.removeMediaQueryListener(this.desktopQuery, this.boundHandleModeChange);
    this.removeMediaQueryListener(this.reducedMotionQuery, this.boundHandleModeChange);
    window.removeEventListener('load', this.boundRebuild);
    window.removeEventListener('gsap:smoother:ready', this.boundRebuild);
    this.initialized = false;
  }
}

const backgroundParallaxManager = new BackgroundParallaxManager();
window.backgroundParallaxManager = backgroundParallaxManager;

export default backgroundParallaxManager;
