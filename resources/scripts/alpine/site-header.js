/**
 * Site header — mega menu, site search, scroll-away dock, header offset CSS var.
 *
 * Responsibilities:
 *   - Mega menu open/close (hover + click), preview image swaps, body class `mega-open` (scroll lock).
 *   - Site search UI + debounced REST fetch.
 *   - After a short grace period: scrolling down (past a threshold) starts a delayed hide; the bar
 *     slides up. Scrolling up or returning near the top shows it again. Layout stays the pill /
 *     max-w-8xl chrome — no full-width morph on scroll (width is static Tailwind only).
 *   - Sync `--site-header-offset` on `<html>` so `#smooth-wrapper` padding clears the fixed bar
 *     (0 when the dock is hidden).
 *
 * @param {import('alpinejs').Alpine} Alpine
 */

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

const MEGA_HOVER_CLOSE_DELAY_MS = 90;
const SEARCH_DEBOUNCE_MS = 220;
const SEARCH_MIN_QUERY_LENGTH = 2;

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

    headerRevealed: false,
    /** When true, the fixed header is translated off-screen (scroll-down + delay path). */
    headerDockHidden: false,

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
      if (!window.matchMedia('(hover: hover) and (min-width: 768px)').matches) {
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
      if (!window.matchMedia('(hover: hover) and (min-width: 768px)').matches) {
        return;
      }
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

    setPreviewFromEvent(event) {
      this.cancelCloseMegaHover();
      const el = event.currentTarget;
      if (!(el instanceof HTMLElement)) {
        return;
      }
      let url = typeof el.dataset.previewUrl === 'string' ? el.dataset.previewUrl.trim() : '';
      if (!url) {
        const list = el.closest('[data-mega-parent-id]');
        const pid = list?.dataset?.megaParentId;
        if (pid) {
          const row = this.readMegaDefault(pid);
          url = typeof row.preview === 'string' ? row.preview.trim() : '';
          this.previewSrc = url;
          this.previewAlt =
            url !== '' ? row.alt || el.textContent?.trim() || '' : (el.textContent?.trim() ?? '');

          return;
        }

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
        this.mobileNavTree = Array.isArray(parsed) ? parsed : [];
      } catch {
        this.mobileNavTree = [];
      }
    },

    resetMobileSubmenu() {
      this.mobileNavDepth = 0;
      this.mobileActiveBranch = null;
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
          window.location.assign(branch.url);
        }

        return;
      }
      this.mobileActiveBranch = branch;
      this.mobileNavDepth = 1;
    },

    openSearch() {
      this.mobileOpen = false;
      this.resetMobileSubmenu();
      this.searchOpen = true;
      this.closeMega();
    },

    openSearchFromMobile() {
      this.resetMobileSubmenu();
      this.mobileOpen = false;
      this.closeMega();
      this.searchOpen = true;
    },

    closeSearch() {
      this.searchOpen = false;
      this.searchQuery = '';
      this.searchHtml = '';
      this.searchResultsVisible = false;
    },

    closeAll() {
      this.closeMega();
      this.mobileOpen = false;
      this.resetMobileSubmenu();
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
        url.searchParams.set('search', query);
        url.searchParams.set('per_page', '8');
        const res = await fetch(url.toString(), { credentials: 'same-origin' });
        if (!res.ok) {
          throw new Error(String(res.status));
        }
        const data = await res.json();
        if (!Array.isArray(data) || data.length === 0) {
          this.searchHtml = `<p class="font-sans text-faded-olive/80">${escapeHtml('No results.')}</p>`;
          this.searchResultsVisible = true;

          return;
        }
        this.searchHtml = data
          .map((item) => {
            const title = itemTitle(item);
            const href = typeof item.url === 'string' ? item.url : '#';

            return `<a class="block py-2 font-sans text-xl text-faded-olive hover:text-deep-moss focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-faded-olive" href="${escapeHtml(href)}">${highlightMatch(title, query)}</a>`;
          })
          .join('');
        this.searchResultsVisible = true;
      } catch {
        this.searchHtml = `<p class="font-sans text-red-800">${escapeHtml('Search unavailable.')}</p>`;
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
          if (this.headerDockHidden) {
            document.documentElement.style.setProperty('--site-header-offset', '0px');

            return;
          }
          const h = Math.ceil(measureEl.getBoundingClientRect().height);
          const px = `${Math.max(Math.round(h), 0)}px`;
          document.documentElement.style.setProperty('--site-header-offset', px);
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

      if (y > this._lastScrollY + SCROLL_DIRECTION_EPS && y > DOCK_HIDE_MIN_SCROLL_Y) {
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

    // --- Lifecycle -----------------------------------------------------------

    init() {
      this._dockMountTs = Date.now();
      this._lastScrollY = this.readScrollY();
      this.hydrateMobileNavTree();

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
          this.resetMobileSubmenu();
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
      this.$watch('searchOpen', () => {
        if (this.searchOpen) {
          this.revealDock();
        }
        this.syncDocumentHeaderOffset();
      });
      this.$watch('headerDockHidden', () => this.syncDocumentHeaderOffset());
      this.$watch('headerRevealed', () => this.syncDocumentHeaderOffset());
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

  return `${before}<strong class="font-semibold text-deep-moss">${match}</strong>${after}`;
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
