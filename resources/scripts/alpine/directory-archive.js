/**
 * Shop directory archive — filter sidebar + responsive grid reflow (Figma Shopping Directory).
 */

export default function registerDirectoryArchiveAlpine(Alpine) {
  Alpine.data('directoryArchive', () => ({
    /** Matches Figma Shopping Directory default: filters hidden, four-column grid (frame 51:5152). */
    filtersVisible: false,
    retailerOpen: false,
    categoryOpen: true,
    categorySlug: '',
    typeSlug: '',

    init() {
      const params = new URLSearchParams(window.location.search);
      const cat = params.get('category');
      const typ = params.get('type');
      if (typeof cat === 'string' && cat !== '') {
        this.categorySlug = cat;
      }
      if (typeof typ === 'string' && typ !== '') {
        this.typeSlug = typ;
      }
      if ((typeof cat === 'string' && cat !== '') || (typeof typ === 'string' && typ !== '')) {
        this.filtersVisible = true;
      }
      this.$nextTick(() => this.applyFilter());
    },

    toggleFilters() {
      this.filtersVisible = !this.filtersVisible;
      this.$nextTick(() => window.dispatchEvent(new Event('resize')));
    },

    toggleCategoryPanel() {
      this.categoryOpen = !this.categoryOpen;
    },

    setCategory(slug) {
      this.categorySlug = typeof slug === 'string' ? slug : '';
      this.applyFilter();
    },

    setType(slug) {
      this.typeSlug = typeof slug === 'string' ? slug : '';
      this.applyFilter();
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

        card.toggleAttribute('hidden', !show);
        if (show) {
          visible.push(card);
        }
      }

      visible.sort((a, b) =>
        (a.dataset.sortTitle || '').localeCompare(b.dataset.sortTitle || '', undefined, {
          sensitivity: 'base',
        })
      );

      visible.forEach((el) => grid.appendChild(el));

      grid.classList.add('directory-archive__grid--animating');
      requestAnimationFrame(() => {
        requestAnimationFrame(() => grid.classList.remove('directory-archive__grid--animating'));
      });

      window.dispatchEvent(new Event('resize'));
    },
  }));
}
