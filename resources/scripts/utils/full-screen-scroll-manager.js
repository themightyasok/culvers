/**
 * Full Screen Scroll Manager
 *
 * Enables page-level section snapping on desktop when
 * `data-full-screen-scrolling="1"` is present on flexible components.
 *
 * Pinned (scroll-hijack) ranges are detected from active ScrollTriggers with `pin`.
 * While inside a pinned range, global snapping is bypassed so local hijack behavior
 * keeps control.
 */
class FullScreenScrollManager {
  constructor() {
    this.initialized = false;
    this.enabled = false;
    this.container = null;
    this.sections = [];
    this.snapPoints = [];
    this.snapTrigger = null;
    this.lastProgress = 0;

    this.desktopQuery = window.matchMedia('(min-width: 1024px)');
    this.reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

    this.boundHandleModeChange = this.handleModeChange.bind(this);
    this.boundHandleRefresh = this.handleRefresh.bind(this);
  }

  init() {
    if (this.initialized) return;
    this.initialized = true;

    if (typeof window.ScrollTrigger === 'undefined') {
      return;
    }

    this.addMediaQueryListener(this.desktopQuery, this.boundHandleModeChange);
    this.addMediaQueryListener(this.reducedMotionQuery, this.boundHandleModeChange);

    window.addEventListener('load', this.boundHandleModeChange);
    window.addEventListener('gsap:smoother:ready', this.boundHandleModeChange);
    window.ScrollTrigger.addEventListener('refresh', this.boundHandleRefresh);

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

  handleModeChange() {
    const nextContainer = document.querySelector(
      '.flexible-components[data-full-screen-scrolling="1"]'
    );
    const desktopOnly = this.desktopQuery.matches;
    const reducedMotion = this.reducedMotionQuery.matches;
    const canEnable = !!nextContainer && desktopOnly && !reducedMotion && !!window.ScrollTrigger;

    if (!canEnable) {
      this.disable();
      return;
    }

    if (this.container !== nextContainer || !this.snapTrigger) {
      this.enable(nextContainer);
      return;
    }

    this.collectSections();
    this.rebuildSnapPoints();
    this.snapTrigger.refresh();
  }

  enable(container) {
    this.disable();
    this.container = container;
    this.container.classList.add('flexible-components--full-screen-active');

    this.collectSections();
    this.rebuildSnapPoints();
    this.createSnapTrigger();
    this.enabled = true;

    requestAnimationFrame(() => {
      window.ScrollTrigger?.refresh?.();
    });
  }

  disable() {
    if (this.snapTrigger) {
      this.snapTrigger.kill();
      this.snapTrigger = null;
    }

    if (this.container) {
      this.container.classList.remove('flexible-components--full-screen-active');
    }

    this.container = null;
    this.sections = [];
    this.snapPoints = [];
    this.enabled = false;
    this.lastProgress = 0;
  }

  collectSections() {
    if (!this.container) {
      this.sections = [];
      return;
    }

    this.sections = Array.from(this.container.children).filter((node) => {
      if (!(node instanceof HTMLElement)) return false;
      const styles = window.getComputedStyle(node);
      if (styles.display === 'none' || styles.visibility === 'hidden') return false;
      return node.getBoundingClientRect().height > 0;
    });
  }

  getScroll() {
    const scrollFn = window.ScrollTrigger?.getScrollFunc?.(window);
    if (typeof scrollFn === 'function') {
      return scrollFn();
    }
    return window.scrollY || window.pageYOffset || 0;
  }

  rebuildSnapPoints() {
    const ScrollTrigger = window.ScrollTrigger;
    if (!ScrollTrigger || this.sections.length === 0) {
      this.snapPoints = [0, 1];
      return;
    }

    const maxScroll = Math.max(1, ScrollTrigger.maxScroll(window));
    const currentScroll = this.getScroll();

    const points = this.sections.map((section) => {
      const top = currentScroll + section.getBoundingClientRect().top;
      const clamped = Math.max(0, Math.min(maxScroll, top));
      return clamped / maxScroll;
    });

    points.push(0, 1);
    points.sort((a, b) => a - b);

    const deduped = [];
    const epsilon = 0.0005;
    points.forEach((point) => {
      if (!deduped.length || Math.abs(deduped[deduped.length - 1] - point) > epsilon) {
        deduped.push(point);
      }
    });

    this.snapPoints = deduped.length ? deduped : [0, 1];
  }

  getPinnedRanges() {
    const ScrollTrigger = window.ScrollTrigger;
    if (!ScrollTrigger) return [];

    return ScrollTrigger.getAll()
      .filter(
        (st) =>
          st &&
          st !== this.snapTrigger &&
          st.pin &&
          Number.isFinite(st.start) &&
          Number.isFinite(st.end)
      )
      .map((st) => ({
        start: Math.min(st.start, st.end),
        end: Math.max(st.start, st.end),
      }));
  }

  isInsidePinnedRange(scrollPosition) {
    const ranges = this.getPinnedRanges();
    if (!ranges.length) return false;

    const edgeBuffer = 1;
    return ranges.some(
      (range) =>
        scrollPosition >= range.start - edgeBuffer && scrollPosition <= range.end + edgeBuffer
    );
  }

  getSnapProgress(progress, self) {
    const ScrollTrigger = window.ScrollTrigger;
    if (!ScrollTrigger || !this.snapPoints.length) return progress;

    const maxScroll = Math.max(1, ScrollTrigger.maxScroll(window));
    const scrollPosition = progress * maxScroll;
    if (this.isInsidePinnedRange(scrollPosition)) {
      this.lastProgress = progress;
      return progress;
    }

    const fallbackDirection = progress >= this.lastProgress ? 1 : -1;
    const direction = self?.direction || fallbackDirection;
    this.lastProgress = progress;

    // When scrolling past the end of a pinned range, exclude snap points at or before
    // the range end. Otherwise snapDirectional snaps back into the range and we get stuck.
    const ranges = this.getPinnedRanges();
    const edgeBuffer = 1;
    const pastRanges = ranges.filter((r) => scrollPosition > r.end + edgeBuffer);
    let points = this.snapPoints;
    if (pastRanges.length && direction === 1) {
      const minProgress = Math.max(...pastRanges.map((r) => (r.end + edgeBuffer) / maxScroll), 0);
      points = this.snapPoints.filter((p) => p > minProgress);
      if (!points.length) points = [1];
    }

    const directionalSnap = ScrollTrigger.snapDirectional(points);
    const snapped = directionalSnap(progress, direction);
    return Math.max(0, Math.min(1, snapped));
  }

  createSnapTrigger() {
    const ScrollTrigger = window.ScrollTrigger;
    if (!ScrollTrigger) return;

    this.snapTrigger = ScrollTrigger.create({
      id: 'full-screen-scroll-snap',
      trigger: document.documentElement,
      start: 0,
      end: 'max',
      invalidateOnRefresh: true,
      refreshPriority: -10,
      onRefresh: () => {
        this.collectSections();
        this.rebuildSnapPoints();
      },
      snap: {
        delay: 0.08,
        duration: { min: 0.15, max: 0.45 },
        ease: 'power2.out',
        inertia: false,
        directional: true,
        snapTo: (value, trigger) => this.getSnapProgress(value, trigger),
      },
    });
  }

  handleRefresh() {
    if (!this.enabled) return;
    this.collectSections();
    this.rebuildSnapPoints();
  }

  destroy() {
    this.disable();
    this.removeMediaQueryListener(this.desktopQuery, this.boundHandleModeChange);
    this.removeMediaQueryListener(this.reducedMotionQuery, this.boundHandleModeChange);
    window.removeEventListener('load', this.boundHandleModeChange);
    window.removeEventListener('gsap:smoother:ready', this.boundHandleModeChange);
    window.ScrollTrigger?.removeEventListener?.('refresh', this.boundHandleRefresh);
    this.initialized = false;
  }
}

const fullScreenScrollManager = new FullScreenScrollManager();
window.fullScreenScrollManager = fullScreenScrollManager;

export default fullScreenScrollManager;
