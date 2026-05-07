/**
 * Travel Calculator: posts to `wp-json/culvers/v1/travel-calculator`,
 * renders the result strip + swaps the Maps Embed iframe to a directions URL
 * built from the resolved origin.
 *
 * Configuration is JSON-injected at render time from the Blade template:
 *   - endpoint:   REST URL
 *   - nonce:      X-WP-Nonce token (wp_rest)
 *   - apiKey:     Maps Embed API key (browser-restricted in Google Cloud)
 *   - destination: { address, label, placeId }
 *   - defaultMode: pre-selected mode in the dropdown
 *   - resultTemplateLoading / resultTemplateInitial: editor-localised strings
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
    showMap: config.showMap !== false,

    origin: '',
    mode: typeof config.defaultMode === 'string' ? config.defaultMode : 'driving',
    loading: false,
    result: null,
    error: '',

    init() {
      this.refreshMapSrc();
    },

    /** @returns {string} */
    get formattedResult() {
      if (this.error !== '') {
        return this.error;
      }
      if (this.loading) {
        return typeof this.config?.resultTemplateLoading === 'string'
          ? this.config.resultTemplateLoading
          : 'Calculating…';
      }
      if (this.result && typeof this.result.message === 'string') {
        return this.result.message;
      }
      return '';
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
        this.refreshMapSrc();
      } catch (err) {
        this.error =
          err instanceof Error && err.message
            ? err.message
            : "Couldn't reach the travel service. Please try again.";
      } finally {
        this.loading = false;
      }
    },

    refreshMapSrc() {
      const iframe = this.$refs?.map;
      if (!(iframe instanceof HTMLIFrameElement) || !this.showMap || this.apiKey === '') {
        return;
      }
      iframe.src = this.buildEmbedUrl();
    },

    /** @returns {string} */
    buildEmbedUrl() {
      const base = 'https://www.google.com/maps/embed/v1';
      const destinationParam =
        this.destination.placeId !== ''
          ? `place_id:${this.destination.placeId}`
          : this.destination.address;

      if (this.result && typeof this.origin === 'string' && this.origin.trim() !== '') {
        const params = new URLSearchParams({
          key: this.apiKey,
          origin: this.origin,
          destination: destinationParam,
          mode: this.mode,
        });
        return `${base}/directions?${params.toString()}`;
      }

      const params = new URLSearchParams({
        key: this.apiKey,
        q: destinationParam,
      });
      return `${base}/place?${params.toString()}`;
    },
  }));
}
