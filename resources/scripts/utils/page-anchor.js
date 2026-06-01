/**
 * In-page anchors — open accordion rows if needed, scroll with header offset.
 * ScrollSmoother smooth scrolling is always used for user clicks.
 */

/** @type {number | undefined} */
let scrollTimer;
/** @type {number} */
let hashNavigationUntil = 0;
/** @type {ReturnType<typeof setTimeout> | undefined} */
let anchorIdleTimer;

/**
 * True while an in-page anchor scroll is in progress — layout refreshes must not
 * restore a stale scroll position mid-animation.
 *
 * @returns {boolean}
 */
export function isHashNavigationActive() {
  return Date.now() < hashNavigationUntil;
}

function scheduleAnchorIdleEvent() {
  clearTimeout(anchorIdleTimer);
  const delay = Math.max(0, hashNavigationUntil - Date.now()) + 80;
  anchorIdleTimer = window.setTimeout(() => {
    anchorIdleTimer = undefined;
    if (Date.now() < hashNavigationUntil) {
      scheduleAnchorIdleEvent();
      return;
    }
    window.dispatchEvent(new CustomEvent('culvers:page-anchor-idle'));
  }, delay);
}

/**
 * @param {number} [ms=1800]
 */
function armHashNavigation(ms = 1800) {
  hashNavigationUntil = Date.now() + ms;
  scheduleAnchorIdleEvent();
}

/**
 * @param {number} [ms=700]
 */
function extendHashNavigation(ms = 700) {
  hashNavigationUntil = Math.max(hashNavigationUntil, Date.now() + ms);
  scheduleAnchorIdleEvent();
}

/**
 * @returns {string}
 */
export function getPageHash() {
  return window.location.hash.replace(/^#/, '').trim();
}

/**
 * @param {string} hash
 * @returns {string}
 */
function normalizeHashId(hash) {
  return hash.replace(/^#/, '').trim();
}

/**
 * @param {string} path
 * @returns {string}
 */
function normalizePathname(path) {
  if (path === '' || path === '/') {
    return '/';
  }

  return path.endsWith('/') ? path.slice(0, -1) : path;
}

/**
 * @param {URL} url
 * @param {URL} current
 * @returns {boolean}
 */
function isSameDocumentPath(url, current) {
  return (
    url.origin === current.origin &&
    normalizePathname(url.pathname) === normalizePathname(current.pathname)
  );
}

/**
 * @returns {boolean}
 */
function desktopSmootherExpected() {
  return (
    window.matchMedia('(min-width: 1024px)').matches &&
    document.getElementById('smooth-wrapper') instanceof HTMLElement
  );
}

/**
 * @param {() => void} callback
 */
export function whenScrollReady(callback) {
  if (!desktopSmootherExpected()) {
    callback();
    return;
  }
  if (window.smoother && typeof window.smoother.scrollTo === 'function') {
    callback();
    return;
  }
  window.addEventListener(
    'gsap:smoother:ready',
    (event) => {
      if (event.detail?.smoother) {
        callback();
      }
    },
    { once: true }
  );
}

/**
 * @param {HTMLElement} root
 * @param {string} [itemSelector='[data-tis-item]']
 * @param {string | null} [hashId]
 * @returns {number | null}
 */
export function findAccordionIndexFromHash(root, itemSelector = '[data-tis-item]', hashId = null) {
  const hash = hashId ?? getPageHash();
  if (hash === '' || !(root instanceof HTMLElement)) {
    return null;
  }

  let target;
  try {
    target = root.querySelector(`#${CSS.escape(hash)}`);
  } catch {
    return null;
  }

  if (!(target instanceof HTMLElement)) {
    return null;
  }

  const item = target.matches(itemSelector) ? target : target.closest(itemSelector);
  if (!(item instanceof HTMLElement) || !root.contains(item)) {
    return null;
  }

  const raw = item.getAttribute('data-tis-item');
  const index = raw !== null ? Number(raw) : NaN;

  return Number.isNaN(index) ? null : index;
}

/**
 * @returns {number} Positive pixel clearance below the fixed header.
 */
export function getHeaderScrollOffsetPx() {
  const root = document.documentElement;
  const raw =
    getComputedStyle(root).getPropertyValue('--site-header-offset').trim() ||
    getComputedStyle(root).getPropertyValue('--site-header-offset-fallback').trim();

  if (raw === '') {
    return 132;
  }

  const probe = document.createElement('div');
  probe.style.cssText = `position:absolute;visibility:hidden;height:${raw};pointer-events:none;`;
  document.body.appendChild(probe);
  const px = probe.offsetHeight;
  probe.remove();

  return px + 12;
}

/**
 * @param {HTMLElement} target
 * @param {{ behavior?: ScrollBehavior }} [options]
 * @returns {boolean}
 */
export function scrollToElement(target, options = {}) {
  if (!(target instanceof HTMLElement)) {
    return false;
  }

  const offsetPx = getHeaderScrollOffsetPx();
  const behavior = options.behavior ?? 'smooth';
  const smooth = behavior !== 'auto';
  const smoother = window.smoother;

  if (smoother && typeof smoother.scrollTo === 'function') {
    smoother.scrollTo(target, smooth, `top ${offsetPx}px`);
    if (smooth) {
      extendHashNavigation(1000);
    }

    return true;
  }

  const top = Math.max(0, target.getBoundingClientRect().top + window.scrollY - offsetPx);
  window.scrollTo({ top, behavior });

  return true;
}

/**
 * @param {string} hash
 * @param {{ behavior?: ScrollBehavior }} [options]
 * @returns {boolean}
 */
export function scrollToHashTarget(hash, options = {}) {
  const id = normalizeHashId(hash);
  if (id === '') {
    return false;
  }

  const target = document.getElementById(id);
  if (!(target instanceof HTMLElement)) {
    return false;
  }

  return scrollToElement(target, options);
}

/**
 * Open any text-image-slider row targeted by a hash id.
 *
 * @param {string} [hashId]
 * @returns {boolean}
 */
export function openTextImageSlidersForHash(hashId = getPageHash()) {
  const id = normalizeHashId(hashId);
  if (id === '') {
    return false;
  }

  const alpine = window.Alpine;
  if (!alpine || typeof alpine.$data !== 'function') {
    return false;
  }

  let opened = false;

  document.querySelectorAll('[data-text-image-slider]').forEach((root) => {
    if (!(root instanceof HTMLElement)) {
      return;
    }

    const index = findAccordionIndexFromHash(root, '[data-tis-item]', id);
    if (index === null) {
      return;
    }

    const data = alpine.$data(root);
    if (data && typeof data.openAt === 'function') {
      data.openAt(index);
      opened = true;
    }
  });

  return opened;
}

/**
 * @param {string} hashId
 * @param {{ updateHistory?: boolean; smooth?: boolean }} [options]
 */
export function navigateToHash(hashId, options = {}) {
  const id = normalizeHashId(hashId);
  if (id === '') {
    return;
  }

  const updateHistory = options.updateHistory ?? true;
  const smooth = options.smooth ?? true;

  armHashNavigation();

  if (updateHistory) {
    const nextHash = `#${id}`;
    if (window.location.hash !== nextHash) {
      history.pushState(
        null,
        '',
        `${window.location.pathname}${window.location.search}${nextHash}`
      );
    }
  }

  window.dispatchEvent(new CustomEvent('culvers:page-anchor-intent'));
  openTextImageSlidersForHash(id);

  const target = document.getElementById(id);
  const accordionDelay =
    target instanceof HTMLElement && target.closest('[data-text-image-slider]') !== null ? 280 : 0;

  clearTimeout(scrollTimer);
  scrollTimer = window.setTimeout(() => {
    whenScrollReady(() => {
      scrollToHashTarget(`#${id}`, { behavior: smooth ? 'smooth' : 'auto' });
    });
  }, accordionDelay);
}

/**
 * @param {MouseEvent} event
 * @returns {boolean} True when same-page hash navigation was handled.
 */
export function followPageAnchorFromClick(event) {
  if (event.defaultPrevented || event.button !== 0) {
    return false;
  }
  if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
    return false;
  }

  const target = event.target;
  if (!(target instanceof Element)) {
    return false;
  }

  const link = target.closest('a[href]');
  if (!(link instanceof HTMLAnchorElement)) {
    return false;
  }

  const rawHref = link.getAttribute('href');
  if (!rawHref || !rawHref.includes('#')) {
    return false;
  }

  let url;
  try {
    url = new URL(link.href, window.location.href);
  } catch {
    return false;
  }

  const id = normalizeHashId(url.hash);
  if (id === '') {
    return false;
  }

  const current = new URL(window.location.href);
  if (!isSameDocumentPath(url, current)) {
    return false;
  }

  event.preventDefault();
  navigateToHash(id, { updateHistory: true, smooth: true });

  return true;
}

/**
 * @param {MouseEvent} event
 */
function handleHashLinkClick(event) {
  followPageAnchorFromClick(event);
}

/**
 * Open accordion targets (if any), then scroll once layout has settled.
 */
export function handlePageHash() {
  const id = getPageHash();
  if (id === '') {
    return;
  }

  navigateToHash(id, { updateHistory: false, smooth: true });
}

/**
 * Call after Alpine + ScrollSmoother are ready.
 */
export function initPageHashNavigation() {
  document.addEventListener('click', handleHashLinkClick, true);

  window.addEventListener('hashchange', () => {
    const id = getPageHash();
    if (id !== '') {
      navigateToHash(id, { updateHistory: false, smooth: true });
    }
  });

  const scheduleInitialHashScroll = () => {
    whenScrollReady(() => {
      if (getPageHash() !== '') {
        handlePageHash();
      }
    });
  };

  scheduleInitialHashScroll();
  window.addEventListener('load', scheduleInitialHashScroll, { once: true });
  window.addEventListener('gsap:smoother:ready', scheduleInitialHashScroll);
}
