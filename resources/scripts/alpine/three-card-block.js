/**
 * Three card block: optional category tabs; video plays on hover (desktop) or
 * in-viewport autoplay (touch / narrow viewports). CPT / blog mode mounts Splide
 * on the active tab panel (mobile carousel).
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
import Splide from '@splidejs/splide';

/** @type {import('@splidejs/splide').Options} */
const THREE_CARD_SPLIDE_OPTIONS = {
  type: 'loop',
  speed: 600,
  arrows: false,
  pagination: true,
  drag: true,
  gap: '1rem',
  perPage: 1,
  perMove: 1,
  trimSpace: false,
};

/** Minimum visible ratio before a card video autoplays on touch / mobile. */
const MOBILE_VIDEO_IO_THRESHOLD = 0.2;

function prefersReducedMotion() {
  return (
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches
  );
}

/** @returns {'static' | 'hover' | 'viewport'} */
function threeCardVideoPlaybackMode() {
  if (prefersReducedMotion()) {
    return 'static';
  }

  if (typeof window.matchMedia !== 'function') {
    return 'viewport';
  }

  // Narrow / touch first — never pair with desktop hover priming on the same card.
  if (
    window.matchMedia('(max-width: 639px)').matches ||
    window.matchMedia('(hover: none)').matches ||
    window.matchMedia('(pointer: coarse)').matches
  ) {
    return 'viewport';
  }

  return 'hover';
}

function usesHoverVideoPlayback() {
  return threeCardVideoPlaybackMode() === 'hover';
}

function usesViewportVideoPlayback() {
  return threeCardVideoPlaybackMode() === 'viewport';
}

/** Skip hidden breakpoint duplicates (mobile stack vs desktop grid). */
function isCardVisible(card) {
  if (!(card instanceof HTMLElement)) {
    return false;
  }

  const rect = card.getBoundingClientRect();

  return rect.width > 0 && rect.height > 0;
}

function pauseVideoAtStart(video) {
  try {
    video.pause();
  } catch {
    /* ignore */
  }
  try {
    video.currentTime = 0;
  } catch {
    /* ignore */
  }
}

function visibleIntersectionRatio(el) {
  if (!(el instanceof HTMLElement)) {
    return 0;
  }

  const rect = el.getBoundingClientRect();
  if (rect.width <= 0 || rect.height <= 0) {
    return 0;
  }

  const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
  const visibleTop = Math.max(rect.top, 0);
  const visibleBottom = Math.min(rect.bottom, viewportHeight);
  const visibleHeight = Math.max(0, visibleBottom - visibleTop);

  return visibleHeight / rect.height;
}

function playThreeCardVideo(video) {
  if (!(video instanceof HTMLVideoElement)) {
    return;
  }

  video.muted = true;
  video.playsInline = true;

  const attemptPlay = () => {
    if (video.paused) {
      video.play().catch(() => {});
    }
  };

  if (video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
    attemptPlay();
    return;
  }

  const onReady = () => {
    attemptPlay();
  };

  video.addEventListener('loadeddata', onReady, { once: true });
  video.addEventListener('canplay', onReady, { once: true });

  if (video.networkState === HTMLMediaElement.NETWORK_EMPTY) {
    try {
      video.load();
    } catch {
      /* ignore */
    }
  }
}

function refreshVisibleThreeCardVideos(root) {
  if (!(root instanceof HTMLElement)) {
    return;
  }

  root.querySelectorAll('[data-three-card-video]').forEach((el) => {
    if (!(el instanceof HTMLVideoElement)) {
      return;
    }

    const card = el.closest('a.three-card-block__card');
    if (!isCardVisible(card)) {
      return;
    }

    const target = card instanceof HTMLElement ? card : el;
    if (visibleIntersectionRatio(target) >= MOBILE_VIDEO_IO_THRESHOLD) {
      playThreeCardVideo(el);
      return;
    }

    if (!el.autoplay && el.dataset.threeCardVideoAutoplay !== '1') {
      pauseVideoAtStart(el);
    }
  });
}

function maybePlayVisibleThreeCardVideo(video) {
  if (!(video instanceof HTMLVideoElement)) {
    return;
  }

  const card = video.closest('a.three-card-block__card');
  if (!isCardVisible(card)) {
    return;
  }

  const target = card instanceof HTMLElement ? card : video;
  if (visibleIntersectionRatio(target) >= MOBILE_VIDEO_IO_THRESHOLD) {
    playThreeCardVideo(video);
  }
}

export default function registerThreeCardBlockAlpine(Alpine) {
  Alpine.data('threeCardBlock', () => ({
    activeTab: 0,

    /** @type {InstanceType<typeof Splide> | null} */
    splide: null,

    /** @type {HTMLElement | null} */
    splideRoot: null,

    /** @type {(() => void) | undefined} */
    boundOnResize: undefined,

    /** @type {ReturnType<typeof setTimeout> | undefined} */
    resizeTimer: undefined,

    /** @type {(() => void) | undefined} */
    boundOnScroll: undefined,

    /** @type {ReturnType<typeof setTimeout> | undefined} */
    scrollTimer: undefined,

    /** @type {MediaQueryList | null} */
    mobileMql: null,

    /** @type {((event: MediaQueryListEvent) => void) | undefined} */
    boundOnMobileChange: undefined,

    /** @type {IntersectionObserver | null} */
    videoViewportObserver: null,

    /** @type {Set<HTMLVideoElement>} */
    viewportObservedVideos: new Set(),

    shouldUseSplide() {
      return typeof window.matchMedia === 'function'
        ? window.matchMedia('(max-width: 639px)').matches
        : false;
    },

    bindMobileQuery() {
      if (typeof window.matchMedia !== 'function') {
        return;
      }

      this.mobileMql = window.matchMedia('(max-width: 639px)');
      this.boundOnMobileChange = () => {
        this.$nextTick(() => {
          this.mountActiveSplide();
          this.syncVideoPlayback();
        });
      };

      if (typeof this.mobileMql.addEventListener === 'function') {
        this.mobileMql.addEventListener('change', this.boundOnMobileChange);
      } else if (typeof this.mobileMql.addListener === 'function') {
        this.mobileMql.addListener(this.boundOnMobileChange);
      }
    },

    unbindMobileQuery() {
      if (this.mobileMql && this.boundOnMobileChange) {
        if (typeof this.mobileMql.removeEventListener === 'function') {
          this.mobileMql.removeEventListener('change', this.boundOnMobileChange);
        } else if (typeof this.mobileMql.removeListener === 'function') {
          this.mobileMql.removeListener(this.boundOnMobileChange);
        }
      }
      this.mobileMql = null;
      this.boundOnMobileChange = undefined;
    },

    /**
     * @param {number} index
     * @param {boolean} [focus] When true (keyboard nav), move focus to the newly active tab.
     */
    selectTab(index, focus = false) {
      this.activeTab = index;
      this.syncTabAccessibility();
      if (focus) {
        const root = this.$root;
        if (root instanceof HTMLElement) {
          const next = root.querySelectorAll('[role="tab"]')[index];
          if (next instanceof HTMLElement) {
            next.focus();
          }
        }
      }
      this.$nextTick(() => {
        this.mountActiveSplide();
        this.syncVideoPlayback();
      });
    },

    syncTabAccessibility() {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }
      const tabs = root.querySelectorAll('[role="tab"]');
      tabs.forEach((tab, i) => {
        if (!(tab instanceof HTMLElement)) {
          return;
        }
        const selected = i === this.activeTab;
        tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        tab.tabIndex = selected ? 0 : -1;
      });
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

    findActiveSplideRoot() {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return null;
      }

      const panels = root.querySelectorAll('.three-card-block__panel');
      const panel = panels[this.activeTab];
      if (!(panel instanceof HTMLElement)) {
        return null;
      }

      const splideEl = panel.querySelector('[data-three-card-splide]');
      return splideEl instanceof HTMLElement ? splideEl : null;
    },

    mountActiveSplide() {
      if (!this.shouldUseSplide()) {
        this.destroySplide();
        return;
      }

      const nextRoot = this.findActiveSplideRoot();
      if (!nextRoot) {
        this.destroySplide();
        return;
      }

      if (this.splideRoot === nextRoot && this.splide) {
        this.splide.refresh();
        return;
      }

      this.destroySplide();
      this.splideRoot = nextRoot;

      const slideCount = nextRoot.querySelectorAll('.splide__slide').length;
      const options = {
        ...THREE_CARD_SPLIDE_OPTIONS,
        type: slideCount > 1 ? 'loop' : 'slide',
        pagination: slideCount > 1,
      };

      this.splide = new Splide(nextRoot, options);
      this.splide.on('mounted', () => {
        requestAnimationFrame(() => {
          this.splide?.refresh();
          this.syncVideoPlayback();
        });
      });
      this.splide.on('move', () => {
        this.syncVideoPlayback();
      });
      this.splide.mount();
      nextRoot.dataset.splideMounted = '1';
    },

    onResize() {
      clearTimeout(this.resizeTimer);
      this.resizeTimer = setTimeout(() => {
        if (this.splide) {
          this.splide.refresh();
        }
        this.syncVideoPlayback();
      }, 150);
    },

    syncVideoPlayback() {
      this.unbindVideoViewportPlayback();

      const mode = threeCardVideoPlaybackMode();

      if (mode === 'hover') {
        this.primeVideoFirstFrames();
        this.bindVideoHoverPlayback();
        return;
      }

      if (mode === 'viewport') {
        this.bindVideoViewportPlayback();
        return;
      }

      this.primeVideoFirstFrames();
    },

    /**
     * Desktop: pause at frame 0 so the first decoded raster shows until hover/play.
     */
    primeVideoFirstFrames() {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }

      root.querySelectorAll('[data-three-card-video]').forEach((el) => {
        if (!(el instanceof HTMLVideoElement)) {
          return;
        }

        if (el.autoplay || el.dataset.threeCardVideoAutoplay === '1') {
          return;
        }

        const card = el.closest('a.three-card-block__card');
        if (!isCardVisible(card)) {
          return;
        }

        const snapToFirstFrame = () => {
          pauseVideoAtStart(el);
          requestAnimationFrame(() => {
            try {
              el.pause();
            } catch {
              /* ignore */
            }
          });
        };

        const tryNow = () => {
          if (el.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
            snapToFirstFrame();
            return true;
          }
          return false;
        };

        if (tryNow()) {
          return;
        }

        if (el.networkState === HTMLMediaElement.NETWORK_EMPTY) {
          try {
            if (el.preload === 'none') {
              el.preload = 'metadata';
            }
            el.load();
          } catch {
            /* ignore */
          }
        }

        let painted = false;
        const oncePaint = () => {
          if (painted) {
            return;
          }
          painted = true;
          snapToFirstFrame();
        };

        el.addEventListener('loadeddata', oncePaint, { once: true });
        el.addEventListener('canplay', oncePaint, { once: true });
      });
    },

    bindVideoHoverPlayback() {
      const root = this.$root;
      if (!(root instanceof HTMLElement) || !usesHoverVideoPlayback()) {
        return;
      }

      root.querySelectorAll('a.three-card-block__card').forEach((card) => {
        if (!(card instanceof HTMLElement) || !isCardVisible(card)) {
          return;
        }
        if (card.dataset.culversThreeCardHover === '1') {
          return;
        }

        const video = card.querySelector('[data-three-card-video]');
        if (!(video instanceof HTMLVideoElement)) {
          return;
        }

        card.dataset.culversThreeCardHover = '1';

        const playClip = () => {
          video.play().catch(() => {});
        };

        const pauseAtStart = () => {
          pauseVideoAtStart(video);
          requestAnimationFrame(() => {
            try {
              video.pause();
            } catch {
              /* ignore */
            }
          });
        };

        card.addEventListener('mouseenter', playClip);
        card.addEventListener('mouseleave', pauseAtStart);
        card.addEventListener('focusin', playClip);
        card.addEventListener('focusout', pauseAtStart);
      });
    },

    bindVideoViewportPlayback() {
      const root = this.$root;
      if (!(root instanceof HTMLElement) || !usesViewportVideoPlayback()) {
        return;
      }

      const videos = [...root.querySelectorAll('[data-three-card-video]')].filter((el) => {
        if (!(el instanceof HTMLVideoElement)) {
          return false;
        }

        const card = el.closest('a.three-card-block__card');

        return isCardVisible(card);
      });

      if (videos.length === 0) {
        return;
      }

      if (!('IntersectionObserver' in window)) {
        videos.forEach((video) => {
          maybePlayVisibleThreeCardVideo(video);
        });

        return;
      }

      this.videoViewportObserver = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            const card = entry.target;
            if (!(card instanceof HTMLElement)) {
              return;
            }

            const video = card.querySelector('[data-three-card-video]');
            if (!(video instanceof HTMLVideoElement)) {
              return;
            }

            if (entry.isIntersecting && entry.intersectionRatio >= MOBILE_VIDEO_IO_THRESHOLD) {
              playThreeCardVideo(video);
              return;
            }

            if (!entry.isIntersecting) {
              pauseVideoAtStart(video);
            }
          });
        },
        { threshold: [0, MOBILE_VIDEO_IO_THRESHOLD, 0.5, 1] }
      );

      videos.forEach((video) => {
        const card = video.closest('a.three-card-block__card');
        if (!(card instanceof HTMLElement) || this.viewportObservedVideos.has(video)) {
          return;
        }

        this.viewportObservedVideos.add(video);
        this.videoViewportObserver?.observe(card);
      });

      refreshVisibleThreeCardVideos(root);

      if (!this.boundOnScroll) {
        this.boundOnScroll = () => {
          clearTimeout(this.scrollTimer);
          this.scrollTimer = setTimeout(() => {
            refreshVisibleThreeCardVideos(root);
          }, 120);
        };
        window.addEventListener('scroll', this.boundOnScroll, { passive: true });
      }
    },

    unbindVideoViewportPlayback() {
      if (this.videoViewportObserver) {
        this.videoViewportObserver.disconnect();
        this.videoViewportObserver = null;
      }

      if (this.boundOnScroll) {
        window.removeEventListener('scroll', this.boundOnScroll);
        this.boundOnScroll = undefined;
      }
      clearTimeout(this.scrollTimer);

      this.viewportObservedVideos.clear();
    },

    init() {
      this.syncTabAccessibility();
      this.bindMobileQuery();
      this.boundOnResize = this.onResize.bind(this);
      window.addEventListener('resize', this.boundOnResize, { passive: true });

      this.$nextTick(() => {
        requestAnimationFrame(() => {
          this.mountActiveSplide();
          this.syncVideoPlayback();
        });
      });
    },

    destroy() {
      clearTimeout(this.resizeTimer);
      clearTimeout(this.scrollTimer);
      this.unbindMobileQuery();
      this.unbindVideoViewportPlayback();
      if (this.boundOnResize) {
        window.removeEventListener('resize', this.boundOnResize);
      }
      this.destroySplide();
    },
  }));
}
