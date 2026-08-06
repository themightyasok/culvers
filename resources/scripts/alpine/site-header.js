/**
 * Site header — mega menu, site search, scroll-away dock, header offset CSS var.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */

import { followPageAnchorFromClick, navigateToHash } from '../utils/page-anchor.js';

/** Ignore sub-pixel noise when inferring scroll direction (down path / timer start). */
const SCROLL_DIRECTION_EPS = 3;
/**
 * Only treat scroll as “up” for revealing the dock if we moved up by at least this many px.
 * Smaller jitter (smooth scroll, touch, ScrollSmoother) was constantly clearing the hide timer so
 * the bar felt like it only hid after scrolling stopped.
 */
const DOCK_REVEAL_MIN_SCROLL_UP_PX = 28;
/** Near top of page — always keep the dock visible. */
const DOCK_ALWAYS_VISIBLE_BELOW_Y = 24;
/** Require this much scroll before a downward hide timer can start. */
const DOCK_HIDE_MIN_SCROLL_Y = 72;
/**
 * After qualifying scroll-down, hide the dock this long (ms) before the slide-up runs.
 * Keep modest so hide feels comparable to scroll-up reveal (same `duration-*` on `.site-header__chrome`).
 */
const DOCK_HIDE_DELAY_MS = 380;
/** Right after load / init, do not schedule hide (hero legibility). */
const DOCK_INITIAL_GRACE_MS = 2000;
/** Keep header visible briefly while anchor smooth-scroll starts; cleared on idle / user wheel. */
const DOCK_ANCHOR_SUPPRESS_MS = 1200;
/** Wheel-down intent window for hide-at-page-bottom (ScrollSmoother can't increase y further). */
const DOCK_WHEEL_DOWN_MS = 180;
/** px from max scroll treated as "at bottom" for dock hide. */
const DOCK_SCROLL_END_THRESHOLD_PX = 32;
/**
 * Desktop: bring the dock back when the pointer is this close to the top edge.
 * Must stay a *listener* threshold only — never a hit-testing overlay (that stole
 * clicks from homepage News/Events/Offers pills when they sat near the top).
 */
const DOCK_REVEAL_EDGE_PX = 56;

const MEGA_HOVER_CLOSE_DELAY_MS = 90;
const SEARCH_DEBOUNCE_MS = 220;
const SEARCH_MIN_QUERY_LENGTH = 2;

/**
 * Defensive shape coercion for mobile nav branches. Server-side PrimaryNav should always emit
 * well-formed entries, but a stale cache, a broken menu item or an empty taxonomy can produce
 * `null`/missing fields — without this, Alpine's `x-for` over `branch.children` throws and the
 * drawer renders empty.
 *
 * @param {unknown} raw
 */
function normalizeBranch(raw) {
  const b = raw && typeof raw === 'object' ? raw : {};
  const children = Array.isArray(b.children) ? b.children.map(normalizeBranch) : [];

  return {
    id: typeof b.id === 'number' ? b.id : 0,
    title: typeof b.title === 'string' ? b.title : '',
    url: typeof b.url === 'string' ? b.url : '',
    children,
  };
}

export default function registerSiteHeaderAlpine(Alpine) {
  Alpine.data('siteHeader', () => ({
    megaOpenId: null,
    previewSrc: '',
    previewAlt: '',
    mobileOpen: false,
    /**
     * Primary nav tree for mobile drill-down (JSON from `#culvers-mobile-nav-tree`).
     *
     * @type {Array<{ id: number, title: string, url: string, children: Array<{ title: string, url: string, preview?: string }> }>}
     */
    mobileNavTree: [],
    /** 0 = root panel, 1 = submenu */
    mobileNavDepth: 0,
    /**
     * @type {null | { id: number, title: string, url: string, children: Array<{ title: string, url: string, preview?: string }> }}
     */
    mobileActiveBranch: null,
    searchOpen: false,
    searchQuery: '',
    searchHtml: '',
    searchResultsVisible: false,

    headerRevealed: true,
    /** When true, the fixed header is translated off-screen (scroll-down + delay path). */
    headerDockHidden: false,
    /** Off until after first paint so Alpine's initial `translate-y-0` does not animate in. */
    headerDockTransitionEnabled: false,
    /** Drill-down carousel slides; disabled when closing drawer or following a link. */
    mobileNavAnimate: true,

    /** @type {number} */
    _suppressDockHideUntil: 0,

    /** @type {number} */
    _wheelDownUntil: 0,

    /** @type {number} */
    _lastScrollY: 0,

    /** @type {ReturnType<typeof setTimeout> | undefined} */
    _dockHideTimer: undefined,

    /** @type {number} */
    _dockMountTs: 0,

    /** @type {IntersectionObserver | undefined} */
    _headerRevealObserver: undefined,

    /** @type {ReturnType<typeof setTimeout> | undefined} */
    _searchTimer: undefined,

    /** @type {ReturnType<typeof setTimeout> | undefined} */
    _megaHoverCloseTimer: undefined,

    /** @type {ResizeObserver | undefined} */
    _headerResizeObserver: undefined,

    _smootherTickerBound: false,

    // --- Mega menu -----------------------------------------------------------

    readMegaDefault(id) {
      const cfg = typeof window !== 'undefined' ? window.culversTheme || {} : {};
      const md = cfg.megaDefaults;
      if (!md || typeof md !== 'object') {
        return { preview: '', alt: '' };
      }
      const row = md[String(id)];
      if (!row || typeof row !== 'object') {
        return { preview: '', alt: '' };
      }

      return {
        preview: typeof row.preview === 'string' ? row.preview : '',
        alt: typeof row.alt === 'string' ? row.alt : '',
      };
    },

    toggleMega(id) {
      this.revealDock();
      const numId = Number(id);
      if (Number.isNaN(numId)) {
        return;
      }
      if (this.megaOpenId === numId) {
        this.megaOpenId = null;

        return;
      }
      this.megaOpenId = numId;
      const row = this.readMegaDefault(numId);
      this.previewSrc = row.preview;
      this.previewAlt = row.alt;
    },

    closeMega() {
      this.megaOpenId = null;
    },

    scheduleCloseMegaHover() {
      if (!window.matchMedia('(hover: hover) and (min-width: 1024px)').matches) {
        return;
      }
      if (this.megaOpenId === null) {
        return;
      }
      window.clearTimeout(this._megaHoverCloseTimer);
      this._megaHoverCloseTimer = window.setTimeout(() => {
        this.closeMega();
        this._megaHoverCloseTimer = undefined;
      }, MEGA_HOVER_CLOSE_DELAY_MS);
    },

    cancelCloseMegaHover() {
      window.clearTimeout(this._megaHoverCloseTimer);
      this._megaHoverCloseTimer = undefined;
    },

    openMegaFromHover(id) {
      if (!window.matchMedia('(hover: hover) and (min-width: 1024px)').matches) {
        return;
      }
      this.revealDock();
      window.clearTimeout(this._megaHoverCloseTimer);
      const numId = Number(id);
      if (Number.isNaN(numId)) {
        return;
      }
      if (this.megaOpenId === numId) {
        return;
      }
      this.megaOpenId = numId;
      const row = this.readMegaDefault(numId);
      this.previewSrc = row.preview;
      this.previewAlt = row.alt;
    },

    /**
     * Bound on each mega submenu link. `mouseenter` per link is reliable; delegated
     * `mouseover` on the list misses moves between inline boxes in some browsers.
     */
    megaSublinkEnter(event) {
      if (!(event.currentTarget instanceof HTMLElement)) {
        return;
      }
      this.cancelCloseMegaHover();
      this.applyMegaPreviewFromLink(event.currentTarget);
    },

    megaListFocusIn(event) {
      const t = event.target;
      if (t instanceof HTMLElement && t.matches('a.mega-nav__sublink')) {
        this.cancelCloseMegaHover();
        this.applyMegaPreviewFromLink(t);
      }
    },

    /**
     * Close the desktop mega panel when a submenu link is followed (in-page hash or full navigation).
     */
    megaSublinkClick(event) {
      if (event.button !== 0) {
        return;
      }
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }
      this.revealDock();
      this.cancelCloseMegaHover();
      this.closeMega();
    },

    /**
     * Header utility pills (Centre Map / Getting Here) — same in-page scroll as mega submenu links.
     *
     * @param {MouseEvent} event
     */
    headerUtilityClick(event) {
      if (event.button !== 0) {
        return;
      }
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }

      this.revealDock();
      this.cancelCloseMegaHover();
      this.closeMega();
      this.closeSearch();

      if (followPageAnchorFromClick(event)) {
        return;
      }

      // Cross-page hash links fall through to native navigation; load handler scrolls to target.
    },

    /**
     * @param {HTMLElement} el
     */
    applyMegaPreviewFromLink(el) {
      const attr = el.getAttribute('data-preview-url');
      const url = typeof attr === 'string' ? attr.trim() : '';
      if (url === '') {
        return;
      }
      this.previewSrc = url;
      this.previewAlt = el.textContent?.trim() ?? '';
    },

    // --- Search & mobile drill-down ------------------------------------------

    hydrateMobileNavTree() {
      const el = document.getElementById('culvers-mobile-nav-tree');
      if (!el?.textContent) {
        this.mobileNavTree = [];

        return;
      }
      try {
        const parsed = JSON.parse(el.textContent.trim());
        this.mobileNavTree = Array.isArray(parsed) ? parsed.map(normalizeBranch) : [];
      } catch {
        this.mobileNavTree = [];
      }
    },

    resetMobileSubmenu(instant = false) {
      this.mobileNavAnimate = !instant;
      this.mobileNavDepth = 0;
      this.mobileActiveBranch = null;
    },

    /**
     * Navigate from the mobile drawer without running close / carousel animations.
     *
     * @param {string} url
     */
    followMobileNavLink(url) {
      const target = typeof url === 'string' ? url.trim() : '';
      if (target === '' || target === '#') {
        return;
      }

      this.mobileNavAnimate = false;
      this.mobileOpen = false;
      this.resetMobileSubmenu(true);
      document.body.classList.remove('mobile-nav-open');

      try {
        const targetUrl = new URL(target, window.location.href);
        const hashId = targetUrl.hash.replace(/^#/, '').trim();
        const currentUrl = new URL(window.location.href);
        const samePage =
          targetUrl.origin === currentUrl.origin &&
          targetUrl.pathname.replace(/\/$/, '') === currentUrl.pathname.replace(/\/$/, '');

        if (hashId !== '' && samePage) {
          navigateToHash(hashId, { updateHistory: true, smooth: true });
          return;
        }
      } catch {
        // Fall through to full navigation.
      }

      window.location.assign(target);
    },

    /**
     * @param {number} index
     */
    openMobileSubmenuByIndex(index) {
      const branch = this.mobileNavTree[Number(index)];
      if (!branch) {
        return;
      }
      const kids = branch.children;
      if (!kids || kids.length === 0) {
        if (typeof branch.url === 'string' && branch.url !== '') {
          this.followMobileNavLink(branch.url);
        }

        return;
      }
      this.mobileNavAnimate = true;
      this.mobileActiveBranch = branch;
      this.mobileNavDepth = 1;
    },

    openSearch() {
      this.mobileOpen = false;
      this.searchOpen = true;
      this.closeMega();
    },

    openSearchFromMobile() {
      this.mobileOpen = false;
      this.closeMega();
      this.searchOpen = true;
    },

    closeSearch() {
      /*
       * Collapse results UI before unmounting the search shell — otherwise the mega bar + offset
       * measurement visibly “step” while the taller results block fades as a sibling.
       */
      this.searchResultsVisible = false;
      this.searchHtml = '';
      this.searchOpen = false;
      window.requestAnimationFrame(() => {
        this.searchQuery = '';
      });
    },

    closeAll() {
      this.closeMega();
      this.mobileOpen = false;
      this.closeSearch();
    },

    async fetchSearch(q) {
      const query = q.trim();
      if (query.length < SEARCH_MIN_QUERY_LENGTH) {
        this.searchHtml = '';
        this.searchResultsVisible = false;

        return;
      }

      const cfg = typeof window !== 'undefined' ? window.culversTheme || {} : {};
      const restUrl =
        typeof cfg.restSearchUrl === 'string' && cfg.restSearchUrl !== ''
          ? cfg.restSearchUrl
          : '/wp-json/wp/v2/search';

      try {
        const url = new URL(restUrl, window.location.origin);
        /*
         * Our custom `culvers/v1/search` endpoint accepts `q=…`. The legacy
         * core fallback `/wp/v2/search` uses `search=…`. Send both so the
         * client tolerates a deploy out-of-sync with the JS bundle.
         */
        url.searchParams.set('q', query);
        url.searchParams.set('search', query);
        url.searchParams.set('per_page', '8');
        const res = await fetch(url.toString(), { credentials: 'same-origin' });
        if (!res.ok) {
          throw new Error(String(res.status));
        }
        const data = await res.json();
        if (!Array.isArray(data) || data.length === 0) {
          this.searchHtml = `<p class="font-sans text-xl leading-[1.3] text-faded-olive/80">${escapeHtml('No results.')}</p>`;
          this.searchResultsVisible = true;

          return;
        }
        this.searchHtml = data
          .map((item) => {
            const title = itemTitle(item);
            const excerpt = itemExcerpt(item);
            const href = typeof item.url === 'string' ? item.url : '#';

            /*
             * Figma `51:8146` row: two stacked lines, both Halyard Display
             * Book 20 / lh 1.3 / Faded Olive. The matched term is the only
             * thing re-weighted to Halyard Medium — no colour change. Row
             * padding mirrors the 14 px / 10 px Figma row inset.
             */
            return (
              '<a class="search-result-row block px-2.5 py-3.5 font-sans text-xl font-light leading-[1.3] text-faded-olive transition-colors hover:bg-faded-olive/[0.06] focus-visible:rounded-sm culvers-focus-ring-compact-faded-olive"' +
              ` href="${escapeHtml(href)}">` +
              `<span class="block">${highlightMatch(title, query)}</span>` +
              (excerpt !== ''
                ? `<span class="search-result-row__excerpt mt-1 block text-faded-olive/80">${highlightMatch(excerpt, query)}</span>`
                : '') +
              '</a>'
            );
          })
          .join('');
        this.searchResultsVisible = true;
      } catch {
        this.searchHtml = `<p class="font-sans text-xl leading-[1.3] text-red-800">${escapeHtml('Search unavailable.')}</p>`;
        this.searchResultsVisible = true;
      }
    },

    // --- Scroll & reveal -----------------------------------------------------

    syncDocumentHeaderOffset() {
      const root = this.$el;
      if (!(root instanceof HTMLElement)) {
        return;
      }
      const chrome = root.querySelector('.site-header__chrome');
      const measureEl = chrome instanceof HTMLElement ? chrome : root;
      window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
          const h = Math.ceil(measureEl.getBoundingClientRect().height);
          const px = `${Math.max(Math.round(h), 0)}px`;
          const current =
            getComputedStyle(document.documentElement)
              .getPropertyValue('--site-header-offset')
              .trim() || '';
          if (px === current) {
            return;
          }
          document.documentElement.style.setProperty('--site-header-offset', px);
          window.dispatchEvent(new CustomEvent('culvers:header-offset'));
        });
      });
    },

    /** Clears dock-hide debounce and forces the header visible. */
    revealDock() {
      window.clearTimeout(this._dockHideTimer);
      this._dockHideTimer = undefined;
      if (this.headerDockHidden) {
        this.headerDockHidden = false;
      }
    },

    readScrollY() {
      const smoother = typeof window !== 'undefined' ? window.smoother : null;
      if (smoother && typeof smoother.scrollTop === 'function') {
        try {
          return smoother.scrollTop();
        } catch {
          /* fall through */
        }
      }

      return window.scrollY ?? document.documentElement.scrollTop ?? 0;
    },

    readMaxScrollY() {
      if (typeof window === 'undefined') {
        return Infinity;
      }

      const wrapper = document.getElementById('smooth-wrapper');
      if (wrapper && window.ScrollTrigger && typeof window.ScrollTrigger.maxScroll === 'function') {
        try {
          return window.ScrollTrigger.maxScroll(wrapper);
        } catch {
          /* fall through */
        }
      }

      return Math.max(0, (document.documentElement.scrollHeight || 0) - (window.innerHeight || 0));
    },

    isNearScrollEnd(y) {
      const max = this.readMaxScrollY();
      if (!Number.isFinite(max)) {
        return false;
      }

      return max - y <= DOCK_SCROLL_END_THRESHOLD_PX;
    },

    /** After anchor navigation, re-baseline so the next downward scroll can hide the dock. */
    resetDockScrollTracking() {
      const y = this.readScrollY();
      this._lastScrollY = Math.max(0, y - DOCK_HIDE_MIN_SCROLL_Y);
      this._wheelDownUntil = 0;
    },

    noteWheelDownIntent() {
      this._wheelDownUntil = Date.now() + DOCK_WHEEL_DOWN_MS;
      if (Date.now() >= this._suppressDockHideUntil) {
        return;
      }
      this._suppressDockHideUntil = 0;
      this.resetDockScrollTracking();
    },

    syncHeaderDock() {
      if (typeof window !== 'undefined' && window.culversHeaderTransformEnabled) {
        this.revealDock();
        this._lastScrollY = this.readScrollY();

        return;
      }

      if (this.megaOpenId !== null || this.searchOpen || this.mobileOpen) {
        this.revealDock();
        this._lastScrollY = this.readScrollY();

        return;
      }

      if (Date.now() < this._suppressDockHideUntil) {
        this.revealDock();
        this._lastScrollY = this.readScrollY();

        return;
      }

      const y = this.readScrollY();
      const now = Date.now();
      if (now - this._dockMountTs < DOCK_INITIAL_GRACE_MS) {
        this.revealDock();
        this._lastScrollY = y;

        return;
      }

      if (y <= DOCK_ALWAYS_VISIBLE_BELOW_Y) {
        this.revealDock();
        this._lastScrollY = y;

        return;
      }

      if (y < this._lastScrollY - DOCK_REVEAL_MIN_SCROLL_UP_PX) {
        this.revealDock();
        this._lastScrollY = y;

        return;
      }

      let shouldScheduleHide = false;
      if (y > this._lastScrollY + SCROLL_DIRECTION_EPS && y > DOCK_HIDE_MIN_SCROLL_Y) {
        shouldScheduleHide = true;
      } else if (
        this.isNearScrollEnd(y) &&
        Date.now() < this._wheelDownUntil &&
        y > DOCK_HIDE_MIN_SCROLL_Y
      ) {
        shouldScheduleHide = true;
      }

      if (shouldScheduleHide) {
        if (this._dockHideTimer === undefined) {
          this._dockHideTimer = window.setTimeout(() => {
            this._dockHideTimer = undefined;
            const y2 = this.readScrollY();
            if (
              y2 > DOCK_HIDE_MIN_SCROLL_Y &&
              this.megaOpenId === null &&
              !this.searchOpen &&
              !this.mobileOpen
            ) {
              this.headerDockHidden = true;
            }
          }, DOCK_HIDE_DELAY_MS);
        }
      }

      /* Don’t advance _lastScrollY on tiny upward noise — avoids starving the hide timer. */
      if (y >= this._lastScrollY - DOCK_REVEAL_MIN_SCROLL_UP_PX) {
        this._lastScrollY = Math.max(this._lastScrollY, y);
      } else {
        this._lastScrollY = y;
      }
    },

    setupHeaderReveal() {
      if (window.matchMedia('(max-width: 1023px)').matches) {
        this.headerRevealed = true;

        return;
      }

      const root = this.$el;
      if (!(root instanceof HTMLElement)) {
        this.headerRevealed = true;

        return;
      }

      const finish = () => {
        this.headerRevealed = true;
        this._headerRevealObserver?.disconnect();
        this._headerRevealObserver = undefined;
      };

      if (!('IntersectionObserver' in window)) {
        finish();

        return;
      }

      this._headerRevealObserver = new IntersectionObserver(
        (entries) => {
          if (entries.some((e) => e.isIntersecting)) {
            finish();
          }
        },
        { threshold: 0.05 }
      );
      this._headerRevealObserver.observe(root);
    },

    /**
     * Desktop: when the dock is hidden off-screen, moving the pointer into the top
     * edge brings it back — without a fixed overlay that intercepts clicks on
     * content sitting near the top of the viewport (e.g. three-card filter tabs).
     */
    setupHeaderDockReveal() {
      if (!window.matchMedia('(min-width: 1024px)').matches) {
        return;
      }

      const nearTopEdge = (event) =>
        typeof event.clientY === 'number' &&
        event.clientY >= 0 &&
        event.clientY <= DOCK_REVEAL_EDGE_PX;

      const onPointerMove = (event) => {
        if (!this.headerDockHidden || !nearTopEdge(event)) {
          return;
        }
        this.revealDock();
      };

      // Touch / pen: same edge, bubble phase so the underlying control still receives the hit.
      const onPointerDown = (event) => {
        if (!this.headerDockHidden || !nearTopEdge(event)) {
          return;
        }
        this.revealDock();
      };

      document.addEventListener('pointermove', onPointerMove, { passive: true });
      document.addEventListener('pointerdown', onPointerDown, { passive: true });
      this._dockRevealMove = onPointerMove;
      this._dockRevealDown = onPointerDown;
    },

    // --- Lifecycle -----------------------------------------------------------

    init() {
      this._dockMountTs = Date.now();
      this._lastScrollY = this.readScrollY();
      this.hydrateMobileNavTree();

      document.documentElement.style.setProperty(
        '--site-header-offset',
        getComputedStyle(document.documentElement)
          .getPropertyValue('--site-header-offset-fallback')
          .trim() || '4.6875rem'
      );

      this.syncHeaderDock();
      const onScroll = () => this.syncHeaderDock();
      window.addEventListener('scroll', onScroll, { passive: true });
      window.addEventListener('resize', () => this.syncDocumentHeaderOffset(), { passive: true });

      const root = this.$el;
      if (root instanceof HTMLElement && typeof ResizeObserver !== 'undefined') {
        this._headerResizeObserver = new ResizeObserver(() => this.syncDocumentHeaderOffset());
        const observeEl = root.querySelector('.site-header__chrome') ?? root;
        if (observeEl instanceof HTMLElement) {
          this._headerResizeObserver.observe(observeEl);
        }
      }

      this.setupHeaderReveal();

      this.syncDocumentHeaderOffset();

      window.addEventListener('culvers:page-anchor-intent', () => {
        this.revealDock();
        this._suppressDockHideUntil = Date.now() + DOCK_ANCHOR_SUPPRESS_MS;
      });

      window.addEventListener('culvers:page-anchor-idle', () => {
        this._suppressDockHideUntil = 0;
        this.resetDockScrollTracking();
      });

      window.addEventListener(
        'wheel',
        (event) => {
          if (event.deltaY > 0) {
            this.noteWheelDownIntent();
          }
        },
        { passive: true }
      );

      this.setupHeaderDockReveal();

      window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
          this.headerDockTransitionEnabled = true;
        });
      });

      const bindSmootherTicker = () => {
        if (
          this._smootherTickerBound ||
          typeof window === 'undefined' ||
          !window.smoother ||
          typeof window.gsap === 'undefined'
        ) {
          return;
        }
        this._smootherTickerBound = true;
        const tick = () => this.syncHeaderDock();
        window.gsap.ticker.add(tick);
      };
      window.addEventListener('gsap:smoother:ready', bindSmootherTicker);
      bindSmootherTicker();

      this.$watch('megaOpenId', () => {
        document.body.classList.toggle('mega-open', this.megaOpenId !== null);
        this.revealDock();
        this.syncDocumentHeaderOffset();
      });
      this.$watch('mobileOpen', (open) => {
        document.body.classList.toggle('mobile-nav-open', !!open);
        if (!open) {
          this.resetMobileSubmenu(true);
        } else {
          this.mobileNavAnimate = true;
        }
        this.revealDock();
        this.syncDocumentHeaderOffset();
      });
      this.$watch('searchQuery', (value) => {
        window.clearTimeout(this._searchTimer);
        this._searchTimer = window.setTimeout(() => {
          this.fetchSearch(typeof value === 'string' ? value : '');
        }, SEARCH_DEBOUNCE_MS);
      });
      this.$watch('searchOpen', (open) => {
        const root = this.$el instanceof HTMLElement ? this.$el : null;
        if (open) {
          this.revealDock();
          this.syncDocumentHeaderOffset();
          this.$nextTick(() => {
            const inp =
              root &&
              typeof root.querySelector === 'function' &&
              root.querySelector('#site-search-input');
            if (inp instanceof HTMLInputElement) {
              inp.focus({ preventScroll: true });
            }
          });
          return;
        }
        /*
         * Clearing `searchQuery` on close would re-fire fetch via the debounced watcher; results are
         * already emptied in closeSearch().
         */
        if (root && typeof root.querySelector === 'function') {
          const inp = root.querySelector('#site-search-input');
          if (inp instanceof HTMLInputElement && document.activeElement === inp) {
            inp.blur();
          }
        }
        this.revealDock();
        this.syncDocumentHeaderOffset();
      });
    },
  }));
}

function escapeHtml(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function highlightMatch(title, query) {
  const q = query.trim();
  if (q === '') {
    return escapeHtml(title);
  }
  const lower = title.toLowerCase();
  const idx = lower.indexOf(q.toLowerCase());
  if (idx < 0) {
    return escapeHtml(title);
  }
  const before = escapeHtml(title.slice(0, idx));
  const match = escapeHtml(title.slice(idx, idx + q.length));
  const after = escapeHtml(title.slice(idx + q.length));

  /*
   * Figma re-weights the matched run to Halyard Medium (font-weight 500)
   * but keeps the colour identical — no contrast bump, no semibold.
   */
  return `${before}<strong class="font-medium">${match}</strong>${after}`;
}

/** @param {{ excerpt?: string | { rendered?: string } }} item */
function itemExcerpt(item) {
  const e = item?.excerpt;
  if (typeof e === 'string') {
    return e;
  }
  if (e && typeof e.rendered === 'string') {
    const el = document.createElement('div');
    el.innerHTML = e.rendered;

    return (el.textContent || '').trim();
  }

  return '';
}

/** @param {{ title?: string | { rendered?: string }; url?: string }} item */
function itemTitle(item) {
  const t = item?.title;
  if (typeof t === 'string') {
    return t;
  }
  if (t && typeof t.rendered === 'string') {
    const el = document.createElement('div');
    el.innerHTML = t.rendered;

    return el.textContent || '';
  }

  return '';
}
