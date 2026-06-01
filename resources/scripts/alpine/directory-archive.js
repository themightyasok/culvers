/**
 * Shop directory archive — filter sidebar + responsive grid reflow (Figma Shopping Directory).
 *
 * @param {import('alpinejs').Alpine} Alpine
 */

import { scrollToElement, whenScrollReady } from '../utils/page-anchor.js';

/** Mega-menu / seed URLs may use marketing slugs; cards carry taxonomy slugs in data attrs. */
/** Legacy mega-menu / seed URLs that no longer match Figma filter slugs. */
const FILTER_SLUG_ALIASES = {
  'grab-and-go': 'grab-go',
  restaurant: 'restaurants',
  cafe: 'cafes',
  takeaway: 'grab-go',
  healthy: 'healthy-options',
};

/**
 * @param {string} slug
 * @returns {string}
 */
function normalizeFilterSlug(slug) {
  if (typeof slug !== 'string' || slug === '') {
    return '';
  }
  const trimmed = slug.trim().toLowerCase();
  return FILTER_SLUG_ALIASES[trimmed] ?? trimmed;
}

/** Matches Tailwind `lg` — desktop directory layout with optional open filter column. */
function isLgViewport() {
  return window.matchMedia('(min-width: 1024px)').matches;
}

export default function registerDirectoryArchiveAlpine(Alpine) {
  Alpine.data('directoryArchive', () => ({
    /** Collapsed by default on all viewports; deep-linked URLs open the panel on desktop only. */
    filtersVisible: false,
    retailerOpen: true,
    categoryOpen: true,
    categorySlug: '',
    typeSlug: '',

    init() {
      const params = new URLSearchParams(window.location.search);
      const cat = params.get('category');
      const typ = params.get('type');
      const hasUrlFilter =
        (typeof cat === 'string' && cat !== '') || (typeof typ === 'string' && typ !== '');
      if (typeof cat === 'string' && cat !== '') {
        this.categorySlug = normalizeFilterSlug(cat);
      }
      if (typeof typ === 'string' && typ !== '') {
        this.typeSlug = normalizeFilterSlug(typ);
      }
      if (hasUrlFilter && isLgViewport()) {
        this.filtersVisible = true;
      }
      this.$nextTick(() => {
        this.applyFilter();
        // Sheet feedback row 18: when a directory is deep-linked from the mega menu
        // (`/shops/?category=fashion`), scroll the filtered grid into view instead of
        // landing on the hero. Smooth-scroll runs after the grid layout settles so
        // the filtered cards are what the user actually sees.
        if (hasUrlFilter) {
          requestAnimationFrame(() => {
            const grid = this.$refs.grid;
            if (grid instanceof HTMLElement) {
              whenScrollReady(() => {
                scrollToElement(grid, { behavior: 'smooth' });
              });
            }
          });
        }
      });
    },

    toggleFilters() {
      this.filtersVisible = !this.filtersVisible;
      this.$nextTick(() => window.dispatchEvent(new Event('resize')));
    },

    setCategory(slug) {
      this.categorySlug = typeof slug === 'string' ? slug : '';
      this.applyFilter();
      this.syncUrl();
    },

    setType(slug) {
      this.typeSlug = typeof slug === 'string' ? slug : '';
      this.applyFilter();
      this.syncUrl();
    },

    syncUrl() {
      const params = new URLSearchParams(window.location.search);
      if (this.categorySlug) {
        params.set('category', this.categorySlug);
      } else {
        params.delete('category');
      }
      if (this.typeSlug) {
        params.set('type', this.typeSlug);
      } else {
        params.delete('type');
      }
      params.delete('page');
      const qs = params.toString();
      const next = `${window.location.pathname}${qs ? `?${qs}` : ''}${window.location.hash}`;
      window.history.replaceState({}, '', next);
    },

    applyFilter() {
      const grid = this.$refs.grid;
      if (!(grid instanceof HTMLElement)) {
        return;
      }

      const cards = Array.from(grid.querySelectorAll('[data-directory-card]'));
      const visible = [];

      for (const card of cards) {
        if (!(card instanceof HTMLElement)) {
          continue;
        }
        const cats = (card.dataset.categorySlugs || '')
          .split(',')
          .map((s) => s.trim())
          .filter(Boolean);
        const types = (card.dataset.typeSlugs || '')
          .split(',')
          .map((s) => s.trim())
          .filter(Boolean);

        const catOk = !this.categorySlug || cats.includes(this.categorySlug);
        const typeOk = !this.typeSlug || types.includes(this.typeSlug);
        const show = catOk && typeOk;

        if (show) {
          visible.push(card);
        } else {
          card.toggleAttribute('hidden', true);
        }
      }

      visible.sort((a, b) =>
        (a.dataset.sortTitle || '').localeCompare(b.dataset.sortTitle || '', undefined, {
          sensitivity: 'base',
        })
      );

      visible.forEach((el) => {
        grid.appendChild(el);
        el.toggleAttribute('hidden', false);
      });

      grid.classList.add('directory-archive__grid--animating');
      requestAnimationFrame(() => {
        requestAnimationFrame(() => grid.classList.remove('directory-archive__grid--animating'));
      });

      window.dispatchEvent(new Event('resize'));
    },
  }));
}
