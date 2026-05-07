/**
 * Contact form: posts to `wp-json/culvers/v1/contact-form`, manages
 * loading / success / error state, and clears the form on success.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export default function registerContactAlpine(Alpine) {
  Alpine.data('contactForm', (config = {}) => ({
    endpoint: typeof config.endpoint === 'string' ? config.endpoint : '',
    nonce: typeof config.nonce === 'string' ? config.nonce : '',
    successDefault:
      typeof config.successMessage === 'string' && config.successMessage !== ''
        ? config.successMessage
        : 'Thanks — your message is on its way.',

    firstName: '',
    lastName: '',
    email: '',
    reason: '',
    message: '',
    /** Honeypot — must stay empty (synced from a hidden input). */
    website: '',

    loading: false,
    /** @type {'idle' | 'success' | 'error'} */
    status: 'idle',
    statusMessage: '',

    async submit() {
      if (this.loading) {
        return;
      }
      if (this.endpoint === '' || this.nonce === '') {
        this.status = 'error';
        this.statusMessage = 'The contact form is not configured.';
        return;
      }

      this.loading = true;
      this.status = 'idle';
      this.statusMessage = '';

      try {
        const response = await fetch(this.endpoint, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-WP-Nonce': this.nonce,
          },
          body: JSON.stringify({
            first_name: this.firstName,
            last_name: this.lastName,
            email: this.email,
            reason: this.reason,
            message: this.message,
            website: this.website,
          }),
        });

        const payload = await response.json().catch(() => null);

        if (!response.ok) {
          this.status = 'error';
          this.statusMessage =
            payload && typeof payload.message === 'string'
              ? payload.message
              : `Couldn't send your message (${response.status}).`;
          return;
        }

        this.status = 'success';
        this.statusMessage =
          payload && typeof payload.message === 'string' ? payload.message : this.successDefault;
        this.resetForm();
      } catch (err) {
        this.status = 'error';
        this.statusMessage =
          err instanceof Error && err.message
            ? err.message
            : "Couldn't reach the server. Please try again.";
      } finally {
        this.loading = false;
      }
    },

    resetForm() {
      this.firstName = '';
      this.lastName = '';
      this.email = '';
      this.reason = '';
      this.message = '';
      this.website = '';
    },

    /**
     * Clear the post-submit status banner the moment the user starts editing
     * again, so a fresh attempt isn't presented next to a stale "success" /
     * "error" message.
     */
    onFieldInput() {
      if (this.status !== 'idle') {
        this.status = 'idle';
        this.statusMessage = '';
      }
    },
  }));
}
