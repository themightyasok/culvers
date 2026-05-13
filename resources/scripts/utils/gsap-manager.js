/**
 * GSAP Manager
 *
 * GSAP + ScrollTrigger + ScrollSmoother bootstrap for the Culvers theme.
 * Manages ScrollSmoother, ScrollTrigger, component auto-detection, and video management.
 * Based on GSAP 2025 best practices and performance optimizations.
 */

import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollSmoother } from 'gsap/ScrollSmoother';
import { Observer } from 'gsap/Observer';
import { EasePack } from 'gsap/EasePack';
import { GSAPUtils, AnimationPresets, ScrollSmootherUtils } from './gsap-utils.js';

// Register plugins
gsap.registerPlugin(ScrollTrigger, ScrollSmoother, Observer, EasePack);

class GSAPManager {
  constructor() {
    this.smoother = null;
    this.scrollTriggers = new Map();
    this.managedVideos = new Set();
    this.videoTriggers = new Map();
    this.isInitialized = false;
    this.components = new Set();
    this.context = null; // GSAP context for better cleanup
    this.matchMedia = null; // GSAP matchMedia instance
    this.tickers = new Set(); // Track ticker functions for cleanup
    this.scrollPauseDepth = 0;
  }

  /**
   * Whether GSAP manager should auto-play/manage a video element.
   * Use data-gsap-autoplay="off" for manual playback control.
   * @param {HTMLVideoElement} video
   * @returns {boolean}
   */
  shouldManageVideo(video) {
    if (!(video instanceof HTMLVideoElement)) {
      return false;
    }

    // Videos with native controls are user-driven and should not be auto-muted/autoplayed.
    if (video.hasAttribute('controls')) {
      return false;
    }

    // `data-video-manual-start`: user taps play; do not force mute or viewport play/pause.
    if (video.dataset?.videoManualStart === '1') {
      return false;
    }

    return video.dataset?.gsapAutoplay !== 'off';
  }

  /**
   * Initialize the GSAP system with ScrollSmoother and ScrollTrigger
   * Uses gsap.matchMedia() for accessibility and responsive handling
   */
  init() {
    if (this.isInitialized) return this.smoother;

    // Setup global ScrollTrigger config BEFORE ScrollSmoother (reduces render loop risk)
    // IMPORTANT: 'resize' must be included so ScrollTrigger recalculates when viewport changes.
    // Without it, pins, scrub animations, and scroll break after browser resize.
    ScrollTrigger.config({
      autoRefreshEvents: 'visibilitychange,DOMContentLoaded,load,resize',
      limitCallbacks: true,
      anticipatePin: 1,
    });

    gsap.ticker.lagSmoothing(1000, 20);
    gsap.defaults({ overwrite: 'auto' });

    // Make globals available immediately for components.
    window.gsap = gsap;
    window.ScrollTrigger = ScrollTrigger;
    window.ScrollSmoother = ScrollSmoother;
    window.Observer = Observer;
    window.smoother = null;

    // Setup matchMedia outside context for proper lifecycle management
    this.matchMedia = gsap.matchMedia();

    // Fire gsap:smoother:ready immediately on mobile (with null) so listeners can proceed
    const isMobile = window.matchMedia('(max-width: 1023px)').matches;
    if (isMobile) {
      window.dispatchEvent(
        new CustomEvent('gsap:smoother:ready', {
          detail: { smoother: null },
        })
      );
    }

    // ScrollSmoother and scroll-based effects only on desktop; no GSAP effects on mobile
    this.matchMedia.add('(min-width: 1024px)', () => {
      const wrapper = document.getElementById('smooth-wrapper');
      const content = document.getElementById('smooth-content');

      if (wrapper && content) {
        this.smoother = ScrollSmoother.create({
          smooth: 1,
          effects: false,
          /* Keep off — normalizer can steal taps; directory hit order is fixed in directory-archive.css (z-index). */
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

    // Mark as initialized AFTER setting up context
    this.isInitialized = true;

    // Make utility classes globally available
    window.GSAPUtils = GSAPUtils;
    window.AnimationPresets = AnimationPresets;
    window.ScrollSmootherUtils = ScrollSmootherUtils;

    // Make matchMedia available globally for component use
    window.gsapMatchMedia = this.matchMedia;

    return this.smoother;
  }

  /**
   * Register a video with performance optimizations using GSAP ScrollTrigger
   * @param {HTMLVideoElement} video - Video element to manage
   */
  registerVideo(video, retryCount = 0) {
    if (!video || this.managedVideos.has(video)) return;

    if (!this.shouldManageVideo(video)) return;

    // Safety check: ensure video is a valid DOM element
    if (!video || !(video instanceof Element)) {
      return;
    }

    // Prevent infinite retry loops
    if (retryCount > 5) {
      return;
    }

    // Ensure the manager is initialized
    if (!this.isInitialized) {
      // Defer registration until next tick
      setTimeout(() => this.registerVideo(video, retryCount + 1), 100);
      return;
    }

    // Performance optimizations based on GSAP video best practices
    video.muted = true;
    video.playsInline = true;
    video.preload = 'metadata'; // Only load metadata initially
    video.setAttribute('playsinline', '');
    video.setAttribute('muted', '');

    // Add unique identifier for tracking
    if (!video.dataset.videoId) {
      video.dataset.videoId = `video_${this.managedVideos.size}_${Date.now()}`;
    }

    // Conservative hardware acceleration
    video.style.transform = 'translateZ(0)'; // Force hardware acceleration
    video.style.willChange = 'auto'; // Will be set to 'transform' when playing

    // Add hardware acceleration to video container if needed
    const videoContainer =
      video.closest('.video-container, [class*="video"], [class*="bg-video"]') ||
      video.parentElement;
    if (videoContainer && videoContainer !== document.body) {
      videoContainer.style.transform = 'translateZ(0)';
    }

    // Additional safety checks before creating ScrollTrigger
    if (!video.offsetParent && !video.getBoundingClientRect) {
      setTimeout(() => this.registerVideo(video, retryCount + 1), 50);
      return;
    }

    // Check if video is actually in the DOM
    if (!document.contains(video)) {
      setTimeout(() => this.registerVideo(video, retryCount + 1), 50);
      return;
    }

    try {
      const videoId = video.dataset.videoId;

      // Create GSAP ScrollTrigger for video management (more efficient than IntersectionObserver)
      const videoTrigger = ScrollTrigger.create({
        trigger: video,
        start: 'top 100%', // Conservative trigger - start when video just enters viewport
        end: 'bottom 0%', // Conservative end - stop when video fully leaves
        refreshPriority: -1, // Lower priority for ScrollSmoother compatibility
        fastScrollEnd: false, // Disable fast scroll end to reduce judder
        anticipatePin: 0, // Reduce anticipation
        invalidateOnRefresh: true, // Prevent stale calculations
        onEnter: () => {
          // Video enters viewport - play with minimal processing
          if (video.paused && video.readyState >= 2) {
            // Set will-change only when actively playing
            video.style.willChange = 'transform';
            video.play().catch(() => {
              // Reset will-change if play fails
              video.style.willChange = 'auto';
            });
          }
        },
        onLeave: () => {
          // Video leaves viewport - pause and reset will-change
          if (!video.paused) {
            video.pause();
            video.style.willChange = 'auto';
          }
        },
        onEnterBack: () => {
          // Video re-enters viewport from bottom - play
          if (video.paused && video.readyState >= 2) {
            video.style.willChange = 'transform';
            video.play().catch(() => {
              video.style.willChange = 'auto';
            });
          }
        },
        onLeaveBack: () => {
          // Video leaves viewport from top - pause and reset will-change
          if (!video.paused) {
            video.pause();
            video.style.willChange = 'auto';
          }
        },
      });

      // Store the trigger for cleanup
      this.videoTriggers.set(videoId, videoTrigger);
      this.managedVideos.add(video);
    } catch {
      // Fallback: just add to managed videos without ScrollTrigger
      this.managedVideos.add(video);
    }
  }

  /**
   * Unregister a video with proper cleanup
   * @param {HTMLVideoElement} video - Video element to unregister
   */
  unregisterVideo(video) {
    if (!video || !this.managedVideos.has(video)) return;

    const videoId = video.dataset.videoId;

    // Clean up video state
    video.pause();

    // Kill GSAP ScrollTrigger for this video
    if (videoId && this.videoTriggers.has(videoId)) {
      this.videoTriggers.get(videoId).kill();
      this.videoTriggers.delete(videoId);
    }

    this.managedVideos.delete(video);
  }

  /**
   * Create a ScrollTrigger with maximum performance optimizations
   * @param {string} id - Unique identifier
   * @param {HTMLElement} element - Element to observe
   * @param {Object} config - ScrollTrigger configuration
   * @returns {ScrollTrigger} The created ScrollTrigger instance
   */
  createScrollTrigger(id, element, config = {}) {
    // Kill existing if it exists
    if (this.scrollTriggers.has(id)) {
      this.scrollTriggers.get(id).kill();
    }

    // Apply performance optimizations from GSAP docs
    const optimizedConfig = {
      trigger: element,
      start: 'top 85%',
      end: 'bottom 15%',
      once: true,
      refreshPriority: -1, // Lower priority for ScrollSmoother compatibility
      anticipatePin: 1, // Better pin performance
      invalidateOnRefresh: true, // Prevent stale calculations
      fastScrollEnd: true, // Better performance on fast scrolling
      ...config,
    };

    const scrollTrigger = ScrollTrigger.create(optimizedConfig);
    this.scrollTriggers.set(id, scrollTrigger);
    return scrollTrigger;
  }

  /**
   * Kill a scroll trigger
   * @param {string} id - ScrollTrigger identifier
   */
  killScrollTrigger(id) {
    if (this.scrollTriggers.has(id)) {
      this.scrollTriggers.get(id).kill();
      this.scrollTriggers.delete(id);
    }
  }

  /**
   * Register a component for GSAP management
   * @param {HTMLElement} component - Component element
   */
  registerComponent(component) {
    if (!component || this.components.has(component)) return;

    this.components.add(component);

    // Auto-register any videos in this component
    const videos = component.querySelectorAll('video');
    videos.forEach((video) => {
      if (this.shouldManageVideo(video)) {
        this.registerVideo(video);
      }
    });
  }

  /**
   * Unregister a component
   * @param {HTMLElement} component - Component element
   */
  unregisterComponent(component) {
    if (!component || !this.components.has(component)) return;

    // Unregister videos in this component
    const videos = component.querySelectorAll('video');
    videos.forEach((video) => {
      this.unregisterVideo(video);
    });

    this.components.delete(component);
  }

  /**
   * Auto-detect and register all components on the page
   */
  autoDetectComponents() {
    // Find all components by class pattern or data attribute
    const components = document.querySelectorAll(
      '[class*="-component"], [data-gsap], [data-component-root]'
    );
    components.forEach((component) => {
      this.registerComponent(component);
    });
  }

  /**
   * Refresh ScrollTrigger (call after DOM changes)
   */
  refresh() {
    ScrollTrigger.refresh();
  }

  /**
   * Scroll to element using ScrollSmoother
   * @param {HTMLElement|string} target - Element or selector to scroll to
   * @param {number} offset - Offset from target (default: -80px for header)
   * @param {Object} vars - Additional ScrollSmoother options
   */
  scrollTo(target, offset = -80, vars = {}) {
    if (this.smoother) {
      this.smoother.scrollTo(target, true, offset, vars);
    } else {
      // Fallback to regular scroll
      const element = typeof target === 'string' ? document.querySelector(target) : target;
      if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
      }
    }
  }

  /**
   * Get ScrollSmoother progress
   * @returns {number} Progress value between 0 and 1
   */
  getScrollProgress() {
    return this.smoother ? this.smoother.progress() : 0;
  }

  /**
   * Pause ScrollSmoother
   */
  pauseScroll() {
    this.scrollPauseDepth += 1;
    if (this.smoother) {
      this.smoother.paused(true);
    }
  }

  /**
   * Resume ScrollSmoother
   */
  resumeScroll() {
    if (this.scrollPauseDepth > 0) {
      this.scrollPauseDepth -= 1;
    }
    if (this.scrollPauseDepth > 0) {
      return;
    }
    if (this.smoother) {
      this.smoother.paused(false);
    }
  }

  /**
   * Get status information
   */
  getStatus() {
    return {
      initialized: this.isInitialized,
      smootherExists: !!this.smoother,
      managedVideos: this.managedVideos.size,
      scrollTriggers: this.scrollTriggers.size,
      components: this.components.size,
    };
  }

  /**
   * Register a ticker function for pause-off-viewport functionality
   * @param {Function} tickerFunction - Function to add to ticker
   * @param {HTMLElement} element - Element to observe for viewport visibility
   */
  registerTicker(tickerFunction, element) {
    if (!tickerFunction || !element) return;

    // Create ScrollTrigger to pause ticker when element is off-viewport
    const tickerTrigger = ScrollTrigger.create({
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

    return tickerTrigger;
  }

  /**
   * Complete cleanup with proper memory management
   * Based on Made With GSAP best practices
   */
  destroy() {
    // Remove all tickers
    this.tickers.forEach((tickerFunction) => {
      gsap.ticker.remove(tickerFunction);
    });
    this.tickers.clear();

    // Kill all video ScrollTriggers
    if (this.videoTriggers) {
      this.videoTriggers.forEach((trigger) => {
        if (trigger && trigger.kill) {
          trigger.kill();
        }
      });
      this.videoTriggers.clear();
    }

    // Kill all ScrollTriggers with proper cleanup
    this.scrollTriggers.forEach((st) => {
      if (st && st.kill) {
        st.kill();
      }
    });
    this.scrollTriggers.clear();

    // Properly cleanup all managed videos
    this.managedVideos.forEach((video) => {
      if (video) {
        video.pause();
      }
    });
    this.managedVideos.clear();

    // Clear components
    this.components.clear();

    // Kill matchMedia (reverts all media queries)
    if (this.matchMedia && this.matchMedia.revert) {
      this.matchMedia.revert();
      this.matchMedia = null;
    }

    // Kill smoother with proper cleanup
    if (this.smoother) {
      this.smoother.kill();
      this.smoother = null;
    }

    // Kill GSAP context (destroys all tweens created within context)
    if (this.context && this.context.kill) {
      this.context.kill();
      this.context = null;
    }

    // Comprehensive GSAP cleanup to prevent memory leaks
    // Note: context.kill() should handle most of this, but we'll do a final cleanup
    ScrollTrigger.killAll();

    // Force garbage collection hints
    this.videoTriggers = null;
    this.scrollPauseDepth = 0;

    this.isInitialized = false;
  }
}

// Create global instance
const gsapManager = new GSAPManager();

// Make globally available
window.gsapManager = gsapManager;

export default gsapManager;
