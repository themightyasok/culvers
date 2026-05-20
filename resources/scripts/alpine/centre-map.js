/**
 * Centre map — Alpine state for the dark map band.
 *
 *   • `panelOpen`           — visibility of the filter panel (filter-toggle pill)
 *   • `openGroup`           — currently open accordion in the panel (one at a time)
 *   • `activeCategorySlug`  — currently selected category slug; also surfaced on the
 *                             map wrap as `[data-active-category]` so SVG shop layers
 *                             with `data-category` can highlight via CSS.
 *   • `activeCategoryLabel` — human-readable label shown in the on-map status pill.
 *   • `zoom`                — map zoom level driven by the +/- buttons (1..2.5)
 *   • `panX` / `panY`       — drag offset when zoomed in (pointer pan)
 *
 * The Blade emits the initial JSON via `x-data="centreMap({...})"`, so we
 * accept any partial shape and merge defensible defaults around it.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export default function registerCentreMapAlpine(Alpine) {
  Alpine.data('centreMap', (init = {}) => ({
    panelOpen: typeof init.panelOpen === 'boolean' ? init.panelOpen : true,
    openGroup: typeof init.openGroup === 'string' ? init.openGroup : '',
    activeCategorySlug: typeof init.activeCategorySlug === 'string' ? init.activeCategorySlug : '',
    activeCategoryLabel:
      typeof init.activeCategoryLabel === 'string' ? init.activeCategoryLabel : '',
    zoom: typeof init.zoom === 'number' && init.zoom >= 1 ? init.zoom : 1,
    panX: 0,
    panY: 0,
    isDragging: false,
    dragPointerId: null,
    dragStartX: 0,
    dragStartY: 0,
    panStartX: 0,
    panStartY: 0,

    init() {
      this.$watch('activeCategorySlug', (slug) => {
        this.syncMapCategoryHighlight(slug);
      });
      this.syncMapCategoryHighlight(this.activeCategorySlug);

      this.$watch('zoom', (level) => {
        if (level <= 1) {
          this.panX = 0;
          this.panY = 0;
          this.isDragging = false;
        } else {
          this.clampPan();
        }
      });
    },

    mapTransformStyle() {
      return {
        transform: `translate(${this.panX}px, ${this.panY}px) scale(${this.zoom})`,
      };
    },

    onMapPointerDown(event) {
      if (this.zoom <= 1 || !(event.currentTarget instanceof HTMLElement)) {
        return;
      }

      this.isDragging = true;
      this.dragPointerId = event.pointerId;
      this.dragStartX = event.clientX;
      this.dragStartY = event.clientY;
      this.panStartX = this.panX;
      this.panStartY = this.panY;
      event.currentTarget.setPointerCapture(event.pointerId);
    },

    onMapPointerMove(event) {
      if (!this.isDragging || event.pointerId !== this.dragPointerId) {
        return;
      }

      this.panX = this.panStartX + (event.clientX - this.dragStartX);
      this.panY = this.panStartY + (event.clientY - this.dragStartY);
      this.clampPan();
    },

    onMapPointerEnd(event) {
      if (!this.isDragging || event.pointerId !== this.dragPointerId) {
        return;
      }

      this.isDragging = false;
      this.dragPointerId = null;
      if (event.currentTarget instanceof HTMLElement) {
        event.currentTarget.releasePointerCapture(event.pointerId);
      }
    },

    clampPan() {
      const wrap = this.$refs.mapWrap;
      if (!(wrap instanceof HTMLElement) || this.zoom <= 1) {
        this.panX = 0;
        this.panY = 0;
        return;
      }

      const maxX = (wrap.clientWidth * (this.zoom - 1)) / 2;
      const maxY = (wrap.clientHeight * (this.zoom - 1)) / 2;
      this.panX = Math.max(-maxX, Math.min(maxX, this.panX));
      this.panY = Math.max(-maxY, Math.min(maxY, this.panY));
    },

    /** Figma mobile (51:8950): pill tabs switch groups; filters stay visible (no panel toggle). */
    selectGroup(slug) {
      this.openGroup = slug;
    },

    isGroupActive(slug) {
      return this.openGroup === slug;
    },

    categoryInActiveGroup(groupSlug) {
      return this.openGroup === groupSlug;
    },

    /**
     * Mark SVG `[data-category]` nodes when a category slug is active.
     * Flat PNG maps still get the raster dim via CSS on `.centre-map__image-stage`.
     *
     * @param {string} slug
     */
    syncMapCategoryHighlight(slug) {
      const wrap = this.$root?.querySelector?.('.centre-map__map-wrap');
      if (!wrap) {
        return;
      }

      const normalized = typeof slug === 'string' ? slug.trim().toLowerCase() : '';
      const showAll = normalized === '' || normalized === 'all' || normalized.endsWith('-all');

      wrap.querySelectorAll('[data-category]').forEach((node) => {
        if (!(node instanceof HTMLElement)) {
          return;
        }

        node.removeAttribute('data-active-category-match');
        node.removeAttribute('data-active');

        if (showAll) {
          return;
        }

        const tokens = (node.getAttribute('data-category') || '')
          .toLowerCase()
          .split(/\s+/)
          .filter(Boolean);
        const shopSlug = (node.getAttribute('data-shop-slug') || '').toLowerCase();
        const matches =
          tokens.includes(normalized) ||
          shopSlug === normalized ||
          tokens.some((token) => token.replace(/_/g, '-') === normalized);

        if (matches) {
          node.setAttribute('data-active-category-match', '1');
          node.setAttribute('data-active', 'true');
        } else {
          node.setAttribute('data-active', 'false');
        }
      });
    },
  }));
}
