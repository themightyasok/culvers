/**
 * Three card block: optional category tabs; video cards show first frame only (poster or primed decode).
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export default function registerThreeCardBlockAlpine(Alpine) {
  Alpine.data('threeCardBlock', () => ({
    activeTab: 0,

    selectTab(index) {
      this.activeTab = index;
      this.syncTabAccessibility();
      this.$nextTick(() => this.primeVideoFirstFrames());
    },

    syncTabAccessibility() {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }
      const tabs = root.querySelectorAll('[role="tab"]');
      tabs.forEach((tab, i) => {
        if (!(tab instanceof HTMLElement)) {
          return;
        }
        const selected = i === this.activeTab;
        tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        tab.tabIndex = selected ? 0 : -1;
      });
    },

    /**
     * Without an image poster, browsers keep `<video>` blank until metadata loads / play().
     * Prime the first decoded frame for cards flagged `data-needs-frame-poster="1"`.
     */
    primeVideoFirstFrames() {
      const root = this.$root;
      if (!(root instanceof HTMLElement)) {
        return;
      }

      root
        .querySelectorAll('[data-three-card-video][data-needs-frame-poster="1"]')
        .forEach((el) => {
          if (!(el instanceof HTMLVideoElement)) {
            return;
          }

          const paintFirstFrame = () => {
            try {
              el.pause();
              el.currentTime = 0;
            } catch {
              /* ignore */
            }
          };

          if (el.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
            paintFirstFrame();

            return;
          }

          el.addEventListener('loadeddata', paintFirstFrame, { once: true });
          el.addEventListener(
            'error',
            () => {
              el.removeEventListener('loadeddata', paintFirstFrame);
            },
            { once: true }
          );
        });
    },

    init() {
      this.syncTabAccessibility();
      this.$nextTick(() => this.primeVideoFirstFrames());
    },
  }));
}
