/**
 * GSAP Utils
 *
 * Collection of GSAP utility functions and animation presets for the Culvers theme.
 * Designed to work seamlessly with ScrollSmoother and provide common animation patterns.
 */

import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

/**
 * GSAP Utilities Class
 * Main utility functions for GSAP operations
 */
export class GSAPUtils {
  /**
   * Create a simple scroll trigger that works with ScrollSmoother
   * @param {HTMLElement} element - Element to observe
   * @param {Function} animation - Animation function to run
   * @param {Object} options - ScrollTrigger options
   */
  static createScrollTrigger(element, animation, options = {}) {
    const defaultOptions = {
      trigger: element,
      start: 'top 80%',
      end: 'bottom 20%',
      once: true,
      ...options,
    };

    return ScrollTrigger.create({
      ...defaultOptions,
      onEnter: () => {
        if (typeof animation === 'function') {
          animation(element);
        }
      },
    });
  }

  /**
   * Animate text character by character
   * @param {HTMLElement} element - Text element to animate
   * @param {Object} config - Animation configuration
   */
  static animateText(element, config = {}) {
    const defaultConfig = {
      duration: 2,
      stagger: 0.05,
      ease: 'power2.out',
      from: { opacity: 0, y: 20 },
      to: { opacity: 1, y: 0 },
    };

    const animConfig = { ...defaultConfig, ...config };

    // Split text into characters (basic implementation)
    const text = element.textContent;
    const chars = text
      .split('')
      .map((char) => `<span class="char">${char === ' ' ? '&nbsp;' : char}</span>`);
    element.innerHTML = chars.join('');

    const charElements = element.querySelectorAll('.char');

    // Use gsap.set() for initial states to avoid unnecessary animations
    gsap.set(charElements, {
      ...animConfig.from,
    });

    return gsap.to(charElements, {
      ...animConfig.to,
      duration: animConfig.duration,
      stagger: animConfig.stagger,
      ease: animConfig.ease,
      overwrite: true, // Prevent animation stacking
    });
  }

  /**
   * Create smooth scroll-based progress animation
   * @param {HTMLElement} element - Element to animate
   * @param {Object} animation - Animation properties
   * @param {Object} scrollConfig - Scroll configuration
   */
  static createScrollAnimation(element, animation, scrollConfig = {}) {
    const defaultScrollConfig = {
      trigger: element,
      start: 'top bottom',
      end: 'bottom top',
      scrub: 1,
      ...scrollConfig,
    };

    return gsap.to(element, {
      ...animation,
      scrollTrigger: defaultScrollConfig,
      overwrite: true, // Prevent animation stacking
    });
  }

  /**
   * Batch animate multiple elements with stagger
   * @param {NodeList|Array} elements - Elements to animate
   * @param {Object} config - Animation configuration
   */
  static batchAnimate(elements, config = {}) {
    const defaultConfig = {
      duration: 0.6,
      stagger: 0.1,
      ease: 'power2.out',
      from: { opacity: 0, y: 30 },
      to: { opacity: 1, y: 0 },
    };

    const animConfig = { ...defaultConfig, ...config };

    // Use gsap.set() for initial states to avoid unnecessary animations
    gsap.set(elements, {
      ...animConfig.from,
    });

    return gsap.to(elements, {
      ...animConfig.to,
      duration: animConfig.duration,
      stagger: animConfig.stagger,
      ease: animConfig.ease,
      overwrite: true, // Prevent animation stacking
    });
  }

  /**
   * Create a smooth entrance animation for components
   * @param {HTMLElement} element - Element to animate
   * @param {String} direction - Direction of entrance (up, down, left, right)
   * @param {Object} config - Animation configuration
   */
  static entranceAnimation(element, direction = 'up', config = {}) {
    const directions = {
      up: { y: 30, x: 0 },
      down: { y: -30, x: 0 },
      left: { x: 30, y: 0 },
      right: { x: -30, y: 0 },
    };

    const defaultConfig = {
      duration: 0.8,
      ease: 'power2.out',
      opacity: 0,
      ...directions[direction],
    };

    const animConfig = { ...defaultConfig, ...config };

    // Use gsap.set() for initial state to avoid unnecessary animations
    gsap.set(element, {
      ...animConfig,
    });

    return gsap.to(element, {
      duration: animConfig.duration,
      ease: animConfig.ease,
      opacity: 1,
      x: 0,
      y: 0,
      overwrite: true, // Prevent animation stacking
    });
  }

  /**
   * Create a hover effect animation
   * @param {HTMLElement} element - Element to animate on hover
   * @param {Object} hoverState - Properties for hover state
   * @param {Object} config - Animation configuration
   */
  static hoverEffect(element, hoverState = {}, config = {}) {
    const defaultHover = {
      scale: 1.05,
      ...hoverState,
    };

    const defaultConfig = {
      duration: 0.3,
      ease: 'power2.out',
      ...config,
    };

    const hoverTween = gsap.to(element, {
      ...defaultHover,
      duration: defaultConfig.duration,
      ease: defaultConfig.ease,
      paused: true,
      overwrite: true, // Prevent animation stacking
    });

    element.addEventListener('mouseenter', () => hoverTween.play());
    element.addEventListener('mouseleave', () => hoverTween.reverse());

    return hoverTween;
  }

  /**
   * Refresh all ScrollTriggers
   */
  static refresh() {
    ScrollTrigger.refresh();
  }

  /**
   * Kill all animations on an element
   * @param {HTMLElement} element - Element to clear animations from
   */
  static killAnimations(element) {
    gsap.killTweensOf(element);
  }

  /**
   * Create animations with responsive breakpoints using gsap.matchMedia()
   * Based on Made With GSAP best practices
   *
   * @param {Function} callback - Function that receives matchMedia instance
   * @returns {Object} MatchMedia instance for cleanup
   *
   * @example
   * GSAPUtils.matchMedia((mm) => {
   *   mm.add("(min-width: 800px)", () => {
   *     // Desktop animations
   *     gsap.to('.element', { x: 100 });
   *   });
   *
   *   mm.add("(pointer: fine)", () => {
   *     // Non-touch device animations
   *   });
   * });
   */
  static matchMedia(callback) {
    const mm = gsap.matchMedia();
    if (typeof callback === 'function') {
      callback(mm);
    }
    return mm;
  }

  /**
   * Create a component-scoped GSAP context for better cleanup
   * Based on Made With GSAP best practices
   *
   * @param {Function} callback - Function that receives context scope
   * @returns {Object} GSAP context instance
   *
   * @example
   * const ctx = GSAPUtils.context(() => {
   *   // All tweens go here
   *   gsap.to('.element', { x: 100 });
   * });
   *
   * // Later, destroy all tweens
   * ctx.kill();
   */
  static context(callback) {
    return gsap.context(callback);
  }
}

/**
 * Animation Presets
 * Pre-configured animations for common use cases
 */
export class AnimationPresets {
  /**
   * Fade in from bottom
   * @param {HTMLElement} element - Element to animate
   * @param {Object} config - Animation configuration
   */
  static fadeInUp(element, config = {}) {
    return GSAPUtils.entranceAnimation(element, 'up', config);
  }

  /**
   * Scale in with bounce effect
   * @param {HTMLElement} element - Element to animate
   * @param {Object} config - Animation configuration
   */
  static scaleIn(element, config = {}) {
    const defaultConfig = {
      duration: 0.8,
      ease: 'back.out(1.7)',
      scale: 0,
      opacity: 0,
      ...config,
    };

    // Use gsap.set() for initial state to avoid unnecessary animations
    gsap.set(element, {
      ...defaultConfig,
    });

    return gsap.to(element, {
      duration: defaultConfig.duration,
      ease: defaultConfig.ease,
      scale: 1,
      opacity: 1,
      overwrite: true, // Prevent animation stacking
    });
  }

  /**
   * Slide in from left
   * @param {HTMLElement} element - Element to animate
   * @param {Object} config - Animation configuration
   */
  static slideInLeft(element, config = {}) {
    return GSAPUtils.entranceAnimation(element, 'left', config);
  }

  /**
   * Bounce in effect
   * @param {HTMLElement} element - Element to animate
   * @param {Object} config - Animation configuration
   */
  static bounceIn(element, config = {}) {
    const defaultConfig = {
      duration: 1,
      ease: 'bounce.out',
      scale: 0,
      opacity: 0,
      ...config,
    };

    return gsap.fromTo(element, defaultConfig, {
      duration: defaultConfig.duration,
      ease: defaultConfig.ease,
      scale: 1,
      opacity: 1,
    });
  }

  /**
   * Elastic in effect
   * @param {HTMLElement} element - Element to animate
   * @param {Object} config - Animation configuration
   */
  static elasticIn(element, config = {}) {
    const defaultConfig = {
      duration: 1.2,
      ease: 'elastic.out(1, 0.5)',
      scale: 0,
      opacity: 0,
      ...config,
    };

    return gsap.fromTo(element, defaultConfig, {
      duration: defaultConfig.duration,
      ease: defaultConfig.ease,
      scale: 1,
      opacity: 1,
    });
  }

  /**
   * Stagger reveal animation for multiple elements
   * @param {NodeList|Array} elements - Elements to animate
   * @param {Object} config - Animation configuration
   */
  static staggerReveal(elements, config = {}) {
    const defaultConfig = {
      duration: 0.6,
      stagger: 0.1,
      ease: 'power2.out',
      ...config,
    };

    return gsap.fromTo(
      elements,
      { opacity: 0, y: 30 },
      {
        opacity: 1,
        y: 0,
        duration: defaultConfig.duration,
        stagger: defaultConfig.stagger,
        ease: defaultConfig.ease,
      }
    );
  }
}

/**
 * ScrollSmoother Integration Helpers
 * Functions specifically designed to work with ScrollSmoother
 */
export class ScrollSmootherUtils {
  /**
   * Get the current ScrollSmoother instance
   */
  static getSmoother() {
    return window.smoother || null;
  }

  /**
   * Scroll to element using ScrollSmoother
   * @param {HTMLElement|String} target - Element or selector to scroll to
   * @param {Number} offset - Offset from target
   * @param {Object} vars - Additional ScrollSmoother vars
   */
  static scrollTo(target, offset = -80, vars = {}) {
    const smoother = this.getSmoother();
    if (smoother) {
      smoother.scrollTo(target, true, offset, vars);
    } else {
      // Fallback to regular scroll
      const element = typeof target === 'string' ? document.querySelector(target) : target;
      if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
      }
    }
  }

  /**
   * Create ScrollTrigger that works well with ScrollSmoother
   * @param {Object} config - ScrollTrigger configuration
   */
  static createScrollTrigger(config = {}) {
    const defaultConfig = {
      refreshPriority: -1, // Work well with ScrollSmoother
      ...config,
    };

    return ScrollTrigger.create(defaultConfig);
  }

  /**
   * Pause ScrollSmoother
   */
  static pause() {
    const smoother = this.getSmoother();
    if (smoother) {
      smoother.paused(true);
    }
  }

  /**
   * Resume ScrollSmoother
   */
  static resume() {
    const smoother = this.getSmoother();
    if (smoother) {
      smoother.paused(false);
    }
  }

  /**
   * Get ScrollSmoother progress
   */
  static getProgress() {
    const smoother = this.getSmoother();
    return smoother ? smoother.progress() : 0;
  }
}

// Export main gsap for backward compatibility
export { gsap };

// Make utilities available globally for component use
if (typeof window !== 'undefined') {
  window.GSAPUtils = GSAPUtils;
  window.AnimationPresets = AnimationPresets;
  window.ScrollSmootherUtils = ScrollSmootherUtils;
}
