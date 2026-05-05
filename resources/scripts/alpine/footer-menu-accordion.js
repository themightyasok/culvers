/**
 * Footer “What's Here” / “Useful Links”: accordion below lg breakpoint, static columns on desktop.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export default function registerFooterMenuAccordionAlpine(Alpine) {
  Alpine.data('footerMenuAccordion', () => ({
    openWhatsHere: false,
    openUsefulLinks: false,
    isDesktop: false,
    /** @type {MediaQueryList | null} */
    media: null,
    /** @type {(() => void) | undefined} */
    _boundSync: undefined,

    syncDesktop() {
      if (!this.media) {
        return;
      }
      this.isDesktop = this.media.matches;
      if (this.isDesktop) {
        this.openWhatsHere = true;
        this.openUsefulLinks = true;
      }
    },

    toggleWhatsHere() {
      if (this.isDesktop) {
        return;
      }
      this.openWhatsHere = !this.openWhatsHere;
    },

    toggleUsefulLinks() {
      if (this.isDesktop) {
        return;
      }
      this.openUsefulLinks = !this.openUsefulLinks;
    },

    init() {
      if (typeof window === 'undefined' || !window.matchMedia) {
        this.openWhatsHere = true;
        this.openUsefulLinks = true;

        return;
      }
      this.media = window.matchMedia('(min-width: 1024px)');
      this._boundSync = () => this.syncDesktop();
      this.media.addEventListener('change', this._boundSync);
      this.syncDesktop();
    },

    destroy() {
      if (this.media && this._boundSync) {
        this.media.removeEventListener('change', this._boundSync);
      }
    },
  }));
}
