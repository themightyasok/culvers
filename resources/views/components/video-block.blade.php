@php
  use App\Helpers\Padding;

  $c = is_array($component ?? null) ? $component : [];
  $padding = Padding::getClasses($c);
  $grid = $c['_grid_classes'] ?? '';

  $video = isset($c['video']) && is_array($c['video']) ? $c['video'] : [];
  $poster = isset($c['poster']) && is_array($c['poster']) ? $c['poster'] : [];
  $videoUrl = isset($video['url']) ? trim((string) $video['url']) : '';
  $posterUrl = isset($poster['url']) ? trim((string) $poster['url']) : '';
  $mime = isset($video['mime_type']) ? (string) $video['mime_type'] : 'video/mp4';
  $playLabel = trim((string) ($c['play_button_label'] ?? ''));
  if ($playLabel === '') {
      $playLabel = __('Play video', 'culvers');
  }
@endphp

@if($videoUrl !== '')
  <section
    class="{{ esc_attr(trim($grid . ' ' . $padding)) }}"
    data-component-root
    data-video-block>
    <div class="mx-auto w-full max-w-[min(100%,112rem)] px-4 sm:px-6 lg:px-10">
      <div
        class="video-block__stage group relative origin-center rounded-3xl border-4 border-brand-500 bg-deep-moss motion-safe:transition-transform motion-safe:duration-300 motion-safe:ease-out motion-safe:hover:scale-[1.03] motion-safe:focus-within:scale-[1.03] motion-reduce:hover:scale-100 motion-reduce:focus-within:scale-100 motion-reduce:transition-none"
        x-data="videoBlock()"
        role="region"
        aria-label="{{ esc_attr__('Video', 'culvers') }}">
        <div
          class="relative aspect-video w-full min-h-[240px] overflow-hidden rounded-[1.15rem] sm:min-h-[320px] md:min-h-[400px] lg:min-h-[min(52vw,760px)] sm:rounded-[1.25rem]"
          data-background-parallax-trigger>
          <div class="absolute inset-0 z-0 size-full" data-background-parallax-image="1">
            <video
              x-ref="video"
              class="video-block__video absolute inset-0 z-0 size-full object-cover motion-safe:transition-transform motion-safe:duration-700 motion-safe:ease-out motion-safe:group-hover:scale-[1.08] motion-safe:group-focus-within:scale-[1.08] motion-reduce:group-hover:scale-100 motion-reduce:group-focus-within:scale-100"
              data-gsap-autoplay="off"
              data-video-manual-start="1"
              preload="{{ $posterUrl !== '' ? 'none' : 'metadata' }}"
              playsinline
              muted
              x-bind:controls="playing"
              data-needs-frame-poster="{{ $posterUrl === '' ? '1' : '0' }}"
              @if($posterUrl !== '')
                poster="{{ esc_url($posterUrl) }}"
              @endif>
              <source src="{{ esc_url($videoUrl) }}" type="{{ esc_attr($mime) }}" />
            </video>
          </div>

          <div
            class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-t from-black/35 via-transparent to-black/15 transition-opacity duration-300"
            x-show="!playing"
            x-transition.opacity.duration.200ms
            aria-hidden="true"></div>

          <div
            class="absolute inset-0 z-[2] flex items-center justify-center p-6 transition-opacity duration-300"
            x-show="!playing"
            x-transition.opacity.duration.200ms>
            <button
              type="button"
              class="pointer-events-auto inline-flex items-center gap-3 rounded-full border border-brand-500 bg-transparent px-8 py-3 font-sans text-micro font-semibold uppercase tracking-cta text-brand-500 shadow-none outline-none transition-[transform,background-color,color] duration-150 hover:bg-brand-500/15 focus-visible:scale-[1.02] focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 focus-visible:ring-offset-deep-moss motion-safe:active:scale-[0.99]"
              x-on:click="play()"
              aria-label="{{ esc_attr(sprintf(__('Play: %s', 'culvers'), $playLabel)) }}">
              <span class="inline-flex size-0 shrink-0 border-y-[7px] border-l-[12px] border-y-transparent border-l-brand-500" aria-hidden="true"></span>
              <span>{{ esc_html($playLabel) }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  <div class="{{ esc_attr(trim($grid . ' ' . $padding)) }} rounded border border-amber-400 bg-amber-50 px-4 py-3 text-amber-950">
    {{ __('Add a video file to this block.', 'culvers') }}
  </div>
@endif
