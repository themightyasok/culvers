/**
 * Travel Calculator: posts to `wp-json/culvers/v1/travel-calculator`,
 * renders the result strip, then reveals the route map only after a
 * successful search (Figma collapsed `51:9027` → expanded `51:9193` /
 * desktop `51:7970` + map band `51:7995`–`7997`).
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export default function registerTravelCalculatorAlpine(Alpine) {
  Alpine.data('travelCalculator', (config = {}) => ({
    endpoint: typeof config.endpoint === 'string' ? config.endpoint : '',
    nonce: typeof config.nonce === 'string' ? config.nonce : '',
    apiKey: typeof config.apiKey === 'string' ? config.apiKey : '',
    destination: {
      address:
        config.destination && typeof config.destination.address === 'string'
          ? config.destination.address
          : '',
      label:
        config.destination && typeof config.destination.label === 'string'
          ? config.destination.label
          : '',
      placeId:
        config.destination && typeof config.destination.placeId === 'string'
          ? config.destination.placeId
          : '',
    },
    showMap: config.showMap === true,

    origin: '',
    mode: typeof config.defaultMode === 'string' ? config.defaultMode : 'driving',
    loading: false,
    mapLoading: false,
    mapRequested: false,
    mapError: '',
    result: null,
    error: '',

    /** @type {ReturnType<typeof setTimeout> | undefined} */
    _mapLoadTimer: undefined,

    /** @returns {boolean} */
    get hasMapPanel() {
      return this.showMap && this.mapRequested;
    },

    /** @returns {string} */
    get embedSrc() {
      if (!this.mapRequested || this.apiKey === '' || this.result === null) {
        return '';
      }
      const trimmed = this.origin.trim();
      if (trimmed === '') {
        return '';
      }
      const destinationParam =
        this.destination.placeId !== ''
          ? `place_id:${this.destination.placeId}`
          : this.destination.address;
      const params = new URLSearchParams({
        key: this.apiKey,
        origin: trimmed,
        destination: destinationParam,
        mode: this.mode,
      });
      return `https://www.google.com/maps/embed/v1/directions?${params.toString()}`;
    },

    async submit() {
      this.error = '';
      const trimmed = this.origin.trim();
      if (trimmed === '') {
        return;
      }
      if (this.endpoint === '' || this.nonce === '') {
        this.error = 'Travel Calculator is not configured.';
        return;
      }

      this.loading = true;
      this.result = null;
      this.mapRequested = false;
      this.mapLoading = false;

      try {
        const response = await fetch(this.endpoint, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-WP-Nonce': this.nonce,
          },
          body: JSON.stringify({ origin: trimmed, mode: this.mode }),
        });

        const payload = await response.json().catch(() => null);

        if (!response.ok || !payload) {
          const message =
            payload && typeof payload.message === 'string'
              ? payload.message
              : `Couldn't calculate the journey (${response.status}).`;
          this.error = message;
          return;
        }

        this.result = payload;
        if (payload.origin && typeof payload.origin === 'string') {
          this.origin = payload.origin;
        }

        if (this.showMap) {
          this.mapRequested = true;
          if (this.apiKey !== '') {
            this.mapLoading = true;
            this.scheduleMapLoadTimeout();
            this.$nextTick(() => {
              const root = this.$root;
              const band =
                root instanceof HTMLElement
                  ? root.querySelector('.travel-calculator__map-band')
                  : null;
              if (band instanceof HTMLElement) {
                band.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
              }
            });
          }
        }
      } catch (err) {
        this.error =
          err instanceof Error && err.message
            ? err.message
            : "Couldn't reach the travel service. Please try again.";
      } finally {
        this.loading = false;
      }
    },

    onMapLoad() {
      this.clearMapLoadTimer();
      this.mapLoading = false;
      this.mapError = '';
    },

    onMapError() {
      this.clearMapLoadTimer();
      this.mapLoading = false;
      this.mapError =
        'The route map could not be loaded. Check that Maps Embed API is enabled for your Google Maps key.';
    },

    scheduleMapLoadTimeout() {
      this.clearMapLoadTimer();
      this._mapLoadTimer = window.setTimeout(() => {
        if (this.mapLoading) {
          this.mapLoading = false;
          this.mapError =
            'The route map is taking longer than expected. Check Maps Embed API and HTTP referrer restrictions on your Google Maps key.';
        }
      }, 12000);
    },

    clearMapLoadTimer() {
      if (this._mapLoadTimer !== undefined) {
        window.clearTimeout(this._mapLoadTimer);
        this._mapLoadTimer = undefined;
      }
    },
  }));
}
