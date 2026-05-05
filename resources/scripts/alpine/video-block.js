/**
 * Video block: poster / first frame, click-to-play, respects reduced motion for hover scale (handled in CSS).
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export default function registerVideoBlockAlpine(Alpine) {
  Alpine.data('videoBlock', () => ({
    playing: false,

    init() {
      const video = this.$refs.video;
      if (!(video instanceof HTMLVideoElement)) {
        return undefined;
      }

      if (video.dataset.needsFramePoster === '1') {
        const paintFirstFrame = () => {
          try {
            video.pause();
            video.currentTime = 0;
          } catch {
            /* ignore */
          }
        };

        if (video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
          paintFirstFrame();
        } else {
          video.addEventListener('loadeddata', paintFirstFrame, { once: true });
        }
      }

      return () => {
        video.pause();
        try {
          video.currentTime = 0;
        } catch {
          /* ignore */
        }
      };
    },

    play() {
      const video = this.$refs.video;
      if (!(video instanceof HTMLVideoElement)) {
        return;
      }
      this.playing = true;
      video.muted = false;
      video.play().catch(() => {});
    },
  }));
}
