/**
 * Horizontal scroller — GSAP Observer drag + ticker auto-scroll (infinite row).
 * Reads options from `data-hs-*` on the component root.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */

const MAX_SETUP_RETRIES = 40;
const SETUP_RETRY_DELAY = 50;

export default function registerHorizontalScrollerAlpine(Alpine) {
  Alpine.data('horizontalScroller', horizontalScrollerData);
}

function horizontalScrollerData() {
  return {
    disableScroll: false,
    scrollSpeed: 'medium',
    gsapContext: null,
    total: 0,
    xTo: null,
    itemValues: [],
    tl: null,
    gsapObserver: null,
    tick: null,
    _initStarted: false,
    _waitTimer: null,
    _waitStart: null,
    _setupRetryTimer: null,
    _isSetup: false,
    _dragResizeHandler: null,
    _dragResizeRefreshTimer: null,
    _dragMediaListeners: [],
    _tickerTrigger: null,
    debugLog: '',
    debugEnabled: false,

    ensureSeamlessSetCoverage(content) {
      if (!content) return 0;

      const baseItems = Array.from(
        content.querySelectorAll('.horizontal-scroller-item[data-set-index="0"]')
      );
      if (!baseItems.length) return 0;

      const ensureSet = (setIndex) => {
        const existing = content.querySelector(
          `.horizontal-scroller-item[data-set-index="${setIndex}"]`
        );
        if (existing) return;

        baseItems.forEach((item) => {
          const clone = item.cloneNode(true);
          clone.dataset.setIndex = String(setIndex);
          clone.setAttribute('aria-hidden', 'true');
          clone.setAttribute('inert', '');
          content.appendChild(clone);
        });
      };

      // Always keep at least one duplicate for width measurement.
      ensureSet(1);

      const firstSetStart = content.querySelector('.horizontal-scroller-item[data-set-index="0"]');
      const secondSetStart = content.querySelector('.horizontal-scroller-item[data-set-index="1"]');
      let baseSetWidth = 0;
      if (firstSetStart && secondSetStart) {
        baseSetWidth = secondSetStart.offsetLeft - firstSetStart.offsetLeft;
      }

      if (!Number.isFinite(baseSetWidth) || baseSetWidth <= 0) {
        return 2;
      }

      const viewportWidth = content.parentElement?.clientWidth || window.innerWidth || baseSetWidth;

      // Need enough sets so one full loop never reveals a blank trailing area.
      // 3 is the safe baseline, larger viewports with short sets may need more.
      const requiredSets = Math.min(6, Math.max(3, Math.ceil(1 + viewportWidth / baseSetWidth)));

      for (let setIndex = 2; setIndex < requiredSets; setIndex += 1) {
        ensureSet(setIndex);
      }

      const allItems = Array.from(
        content.querySelectorAll('.horizontal-scroller-item[data-set-index]')
      );
      allItems.forEach((item) => {
        const setIndex = Number(item.dataset.setIndex);
        if (Number.isFinite(setIndex) && setIndex >= requiredSets) {
          item.remove();
        }
      });

      return requiredSets;
    },

    debug(message, data = null) {
      // Stripped in production by Vite via `import.meta.env.DEV`; gated by `?hsDebug` in dev.
      if (!import.meta.env.DEV || !this.debugEnabled) {
        return;
      }
      const timestamp = new Date().toLocaleTimeString();
      let logLine = `[${timestamp}] ${message}`;
      if (data !== null) {
        try {
          logLine += `: ${JSON.stringify(data)}`;
        } catch {
          logLine += `: [Object]`;
        }
      }
      this.debugLog = (this.debugLog + logLine + '\n').slice(-5000);
    },

    init() {
      if (this._initStarted) {
        return;
      }
      const root = this.$root;
      if (root instanceof HTMLElement) {
        this.disableScroll = root.dataset.hsDisableScroll === '1';
        const sp = root.dataset.hsScrollSpeed || 'medium';
        this.scrollSpeed = ['slow', 'medium', 'fast'].includes(sp) ? sp : 'medium';
      }
      this._initStarted = true;
      if (import.meta.env.DEV) {
        this.debugEnabled = new URLSearchParams(window.location.search).has('hsDebug');
      }
      this.debug('init() called');
      if (this.disableScroll) {
        this.setupDisableScroll();
        return () => this.destroy();
      }
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        this.debug('Reduced motion preference detected - using static mode');
        this.setupReducedMotion();
        return () => this.destroy();
      }
      this._waitStart = null;
      requestAnimationFrame(() => {
        requestAnimationFrame(() => this.waitForGSAP());
      });
      return () => this.destroy();
    },

    removeDuplicateSets() {
      if (!this.$el) return;
      const duplicateSet = this.$el.querySelectorAll(
        '.horizontal-scroller-item[data-set-index]:not([data-set-index="0"])'
      );
      duplicateSet.forEach((item) => {
        item.remove();
      });
    },

    setupReducedMotion() {
      if (!this.$el) return;
      this.$el.classList.add('horizontal-scroller--reduced-motion');
      this.removeDuplicateSets();
      this._isSetup = true;
    },

    setupDisableScroll() {
      if (!this.$el) return;
      this.$el.classList.add('horizontal-scroller--disable-scroll');
      this.removeDuplicateSets();
      this._isSetup = true;
    },

    waitForGSAP() {
      if (this._waitStart == null) this._waitStart = Date.now();
      const gsapReady = typeof window.gsap !== 'undefined' && window.gsap.utils && window.Observer;
      const scrollTriggerReady = typeof window.ScrollTrigger !== 'undefined';
      // ScrollSmoother is only created at (min-width: 1024px) in gsap-manager.js. On smaller
      // viewports #smooth-wrapper exists but window.smoother stays null — do not wait for it
      // or drag-mode setup is delayed until waitForGSAP’s 4s timeout (felt as “no scroll on mobile”).
      const isMobileViewport = window.matchMedia('(max-width: 1023px)').matches;
      const smootherExpected = !!document.getElementById('smooth-wrapper');
      const hasSmoother = !!window.smoother;
      const ready =
        gsapReady && scrollTriggerReady && (!smootherExpected || hasSmoother || isMobileViewport);
      const timedOut = Date.now() - this._waitStart >= 4000;
      if (ready || timedOut) {
        if (this._waitTimer) {
          clearTimeout(this._waitTimer);
          this._waitTimer = null;
        }
        if (timedOut && !ready) {
          this.debug('⚠️ waitForGSAP timeout, attempting setup');
        }
        this.debug('GSAP and ScrollTrigger available');
        requestAnimationFrame(() => {
          this.trySetup(0);
        });
        return;
      }
      this.debug('Waiting for GSAP...', {
        gsap: typeof window.gsap,
        Observer: typeof window.Observer,
        ScrollTrigger: typeof window.ScrollTrigger,
      });
      this._waitTimer = setTimeout(() => this.waitForGSAP(), 50);
    },

    trySetup(retryCount) {
      if (this._isSetup) return;
      const content = this.$el?.querySelector('.horizontal-scroller__container');
      if (!content) {
        this.debug('❌ Container not found');
        return;
      }

      const items = this.$el?.querySelectorAll('.horizontal-scroller-item');
      if (!items || items.length === 0) {
        this.debug('❌ No floating items found');
        return;
      }

      this.debug(`trySetup attempt ${retryCount}`, {
        containerWidth: content.clientWidth,
        itemsCount: items.length,
      });

      if (content.clientWidth === 0) {
        if (retryCount >= MAX_SETUP_RETRIES) {
          this.debug('❌ Container width is 0 after max retries');
          return;
        }
        if (this._setupRetryTimer) clearTimeout(this._setupRetryTimer);
        this._setupRetryTimer = setTimeout(() => this.trySetup(retryCount + 1), SETUP_RETRY_DELAY);
        return;
      }
      if (this._setupRetryTimer) {
        clearTimeout(this._setupRetryTimer);
        this._setupRetryTimer = null;
      }

      const gsap = window.gsap;
      if (!gsap?.context) {
        this.debug('❌ GSAP context unavailable during setup');
        this._isSetup = false;
        return;
      }

      if (window.gsapManager?.registerComponent) {
        window.gsapManager.registerComponent(this.$el);
      }

      if (typeof window.ScrollTrigger !== 'undefined' && this.$el) {
        window.ScrollTrigger.getAll().forEach((st) => {
          if (st?.trigger && this.$el.contains(st.trigger)) st.kill();
        });
      }

      // Use gsap.context() for proper cleanup
      this.gsapContext = gsap.context(() => {
        this._isSetup = this.setupDragMode(content);
      }, this.$el);
    },

    /**
     * Drag mode: GSAP Observer (drag) + ticker (auto-scroll).
     */
    setupDragMode(content) {
      this.debug('========== SETUP DRAG MODE ==========');
      const gsap = window.gsap;
      const Observer = window.Observer;
      const dragTarget = content;
      // Observer already registered globally in gsap-manager.js

      this.ensureSeamlessSetCoverage(content);
      const baseItems = Array.from(
        content.querySelectorAll('.horizontal-scroller-item[data-set-index="0"]')
      );
      const allItems = Array.from(content.querySelectorAll('.horizontal-scroller-item'));
      const itemsLength = Math.max(1, baseItems.length || Math.floor(allItems.length / 2));
      let total = 0;
      let isDragging = false;
      const itemValues = [];

      let loopWidth = 0;
      const setX = gsap.quickSetter(content, 'x', 'px');

      const measureLoopWidth = () => {
        this.ensureSeamlessSetCoverage(content);
        const firstSetStart = content.querySelector(
          '.horizontal-scroller-item[data-set-index="0"]'
        );
        const secondSetStart = content.querySelector(
          '.horizontal-scroller-item[data-set-index="1"]'
        );
        if (firstSetStart && secondSetStart) {
          const measured = secondSetStart.offsetLeft - firstSetStart.offsetLeft;
          if (Number.isFinite(measured) && measured > 0) return measured;
        }
        const setIndexes = Array.from(
          new Set(
            Array.from(content.querySelectorAll('.horizontal-scroller-item[data-set-index]'))
              .map((el) => Number(el.dataset.setIndex))
              .filter((value) => Number.isFinite(value))
          )
        );
        const setCount = Math.max(1, setIndexes.length);
        return Math.max(1, Math.floor(content.scrollWidth / setCount));
      };

      const applyX = (value) => {
        if (!Number.isFinite(loopWidth) || loopWidth <= 0) {
          setX(0);
          return;
        }
        const wrapped = gsap.utils.wrap(-loopWidth, 0, value);
        total = wrapped;
        setX(wrapped);
      };

      const refreshLoopWidth = () => {
        loopWidth = measureLoopWidth();
        applyX(total);
      };

      gsap.set(content, { x: 0 });
      refreshLoopWidth();

      for (let i = 0; i < itemsLength; i++) {
        itemValues.push((Math.random() - 0.5) * 12);
      }

      gsap.set(allItems, {
        rotate: 0,
        xPercent: 0,
        yPercent: 0,
        scale: 1,
      });

      const tl = gsap.timeline({ paused: true });
      tl.to(allItems, {
        rotate: (index) => itemValues[index % itemsLength] * 0.35,
        xPercent: (index) => itemValues[index % itemsLength] * 0.25,
        yPercent: (index) => itemValues[index % itemsLength],
        scale: 0.985,
        duration: 0.45,
        ease: 'power2.inOut',
      });

      this.tl = tl;

      try {
        this.gsapObserver = Observer.create({
          target: dragTarget,
          type: 'pointer,touch',
          onPress: () => {
            isDragging = true;
            content.classList.add('is-dragging');
            tl.play();
          },
          onDrag: (self) => {
            total += self.deltaX * 1.35;
            applyX(total);
          },
          onRelease: () => {
            isDragging = false;
            content.classList.remove('is-dragging');
            tl.reverse();
          },
          onStop: () => {
            isDragging = false;
            content.classList.remove('is-dragging');
            tl.reverse();
          },
        });
        this.debug('✅ Observer created (drag mode)');
      } catch (error) {
        this.debug(`❌ Observer failed: ${error.message}`);
        return false;
      }

      const speedMult = this.scrollSpeed === 'slow' ? 0.52 : this.scrollSpeed === 'fast' ? 1.68 : 1;
      const tick = (_time, deltaTime) => {
        if (isDragging) return;
        total -= (deltaTime / 16) * speedMult;
        applyX(total);
      };

      const mediaReadyHandler = () => refreshLoopWidth();
      const mediaNodes = Array.from(content.querySelectorAll('img, video'));
      this._dragMediaListeners = [];
      mediaNodes.forEach((media) => {
        if (media instanceof HTMLImageElement) {
          if (!media.complete) {
            media.addEventListener('load', mediaReadyHandler, { once: true });
            this._dragMediaListeners.push({ media, event: 'load', handler: mediaReadyHandler });
          }
        } else if (media instanceof HTMLVideoElement) {
          if (media.readyState < 1) {
            media.addEventListener('loadedmetadata', mediaReadyHandler, { once: true });
            this._dragMediaListeners.push({
              media,
              event: 'loadedmetadata',
              handler: mediaReadyHandler,
            });
          }
        }
      });

      const RESIZE_REFRESH_DEBOUNCE = 150;
      if (this._dragResizeHandler) {
        window.removeEventListener('resize', this._dragResizeHandler);
      }
      this._dragResizeHandler = () => {
        refreshLoopWidth();
        if (this._dragResizeRefreshTimer) clearTimeout(this._dragResizeRefreshTimer);
        this._dragResizeRefreshTimer = setTimeout(() => {
          this._dragResizeRefreshTimer = null;
          window.ScrollTrigger?.refresh?.();
        }, RESIZE_REFRESH_DEBOUNCE);
      };
      window.addEventListener('resize', this._dragResizeHandler);

      this.tick = tick;
      if (window.gsapManager && typeof window.gsapManager.registerTicker === 'function') {
        this._tickerTrigger = window.gsapManager.registerTicker(tick, this.$el || content);
      } else {
        gsap.ticker.add(tick);
      }
      this.xTo = applyX;
      this.debug('✅ Ticker added (auto-scroll). Drag mode ready.');
      return true;
    },

    destroy() {
      if (this._waitTimer) {
        clearTimeout(this._waitTimer);
        this._waitTimer = null;
      }
      if (this._setupRetryTimer) {
        clearTimeout(this._setupRetryTimer);
        this._setupRetryTimer = null;
      }

      // Cleanup gsap context if it exists
      if (this.gsapContext) {
        this.gsapContext.revert();
        this.gsapContext = null;
      }

      // Drag mode cleanup
      if (this.tick && window.gsap?.ticker) {
        window.gsap.ticker.remove(this.tick);
        this.tick = null;
      }
      if (this._tickerTrigger && typeof this._tickerTrigger.kill === 'function') {
        this._tickerTrigger.kill();
        this._tickerTrigger = null;
      }
      if (this.gsapObserver) {
        this.gsapObserver.kill();
        this.gsapObserver = null;
      }
      if (this._dragResizeHandler) {
        window.removeEventListener('resize', this._dragResizeHandler);
        this._dragResizeHandler = null;
      }
      if (this._dragResizeRefreshTimer) {
        clearTimeout(this._dragResizeRefreshTimer);
        this._dragResizeRefreshTimer = null;
      }
      if (Array.isArray(this._dragMediaListeners) && this._dragMediaListeners.length) {
        this._dragMediaListeners.forEach(({ media, event, handler }) => {
          media?.removeEventListener?.(event, handler);
        });
        this._dragMediaListeners = [];
      }
      if (this.tl) {
        this.tl.kill();
        this.tl = null;
      }
      const content = this.$el?.querySelector('.horizontal-scroller__container');
      if (content && window.gsap) {
        window.gsap.killTweensOf(content);
      }

      this.$el?.classList.remove('horizontal-scroller--reduced-motion');
      this.$el?.classList.remove('horizontal-scroller--disable-scroll');

      if (typeof window.ScrollTrigger !== 'undefined' && this.$el) {
        window.ScrollTrigger.getAll().forEach((st) => {
          if (st?.trigger && this.$el.contains(st.trigger)) {
            st.kill();
          }
        });
      }

      this.xTo = null;
      this._isSetup = false;
      this._waitStart = null;
      this._initStarted = false;
      if (this.$el && window.gsapManager?.unregisterComponent) {
        window.gsapManager.unregisterComponent(this.$el);
      }
    },
  };
}
