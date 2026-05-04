/**
 * Alpine component: `x-data="siteHeader"` on `.site-header` (see `sections/header.blade.php`).
 *
 * Responsibilities:
 *   Mega menu open/close (hover + click), preview image swaps, body class `mega-open`.
 *   Site search UI + debounced REST fetch.
 *   Scroll threshold → `headerScrolled` (full-width bar) + optional entrance reveal.
 *
 * Private fields use a leading underscore (`_megaHoverCloseTimer`, …).
 */

/** @param {import('alpinejs').Alpine} Alpine */

const HEADER_SCROLL_FULL_WIDTH_AT = 50;
/** Lets `mouseenter` on bridge/panel win over `mouseleave` ordering on the nav wrapper. */
const MEGA_HOVER_CLOSE_DELAY_MS = 90;
const SEARCH_DEBOUNCE_MS = 220;
const SEARCH_MIN_QUERY_LENGTH = 2;

export default function registerSiteHeaderAlpine(Alpine) {
  Alpine.data('siteHeader', () => ({
    megaOpenId: null,
    previewSrc: '',
    previewAlt: '',
    mobileOpen: false,
    searchOpen: false,
    searchQuery: '',
    searchHtml: '',
    searchResultsVisible: false,

    headerRevealed: false,
    headerScrolled: false,

    /** @type {IntersectionObserver | undefined} */
    _headerRevealObserver: undefined,

    /** @type {ReturnType<typeof setTimeout> | undefined} */
    _searchTimer: undefined,

    /** @type {ReturnType<typeof setTimeout> | undefined} */
    _megaHoverCloseTimer: undefined,

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
      const url = el.dataset.previewUrl;
      if (url) {
        this.previewSrc = url;
        this.previewAlt = el.textContent?.trim() ?? '';
      }
    },

    // --- Search --------------------------------------------------------------

    openSearch() {
      this.mobileOpen = false;
      this.searchOpen = true;
      this.closeMega();
    },

    openSearchFromMobile() {
      this.mobileOpen = false;
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

            return `<a role="option" class="block py-2 font-sans text-lg text-faded-olive hover:text-deep-moss" href="${escapeHtml(href)}">${highlightMatch(title, query)}</a>`;
          })
          .join('');
        this.searchResultsVisible = true;
      } catch {
        this.searchHtml = `<p class="font-sans text-red-800">${escapeHtml('Search unavailable.')}</p>`;
        this.searchResultsVisible = true;
      }
    },

    // --- Scroll & reveal -----------------------------------------------------

    syncHeaderScroll() {
      const y = window.scrollY ?? document.documentElement.scrollTop ?? 0;
      if (typeof window !== 'undefined' && window.culversHeaderTransformEnabled) {
        this.headerScrolled = true;

        return;
      }
      this.headerScrolled = y > HEADER_SCROLL_FULL_WIDTH_AT;
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
      this.syncHeaderScroll();
      const onScroll = () => this.syncHeaderScroll();
      window.addEventListener('scroll', onScroll, { passive: true });

      this.setupHeaderReveal();

      this.$watch('megaOpenId', () => {
        document.body.classList.toggle('mega-open', this.megaOpenId !== null);
      });
      this.$watch('mobileOpen', () => {
        document.body.classList.toggle('mobile-nav-open', this.mobileOpen);
      });
      this.$watch('searchQuery', (value) => {
        window.clearTimeout(this._searchTimer);
        this._searchTimer = window.setTimeout(() => {
          this.fetchSearch(typeof value === 'string' ? value : '');
        }, SEARCH_DEBOUNCE_MS);
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
