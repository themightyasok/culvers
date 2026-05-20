@php
  use App\Helpers\Component;
  use App\Helpers\LayoutShell;

  /**
   * Video block — contained video (742px stage at lg+) with branded brand-500 frame. Hover uses a light
   * scale on the framed stage (clipped) plus in-frame video zoom — capped a bit wider
   * than the site 8xl shell but well under the old 112rem full-bleed width.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $video = isset($c['video_file']) && is_array($c['video_file']) ? $c['video_file'] : [];
  $poster = isset($c['video_poster']) && is_array($c['video_poster']) ? $c['video_poster'] : [];
  $videoUrl = isset($video['url']) ? trim((string) $video['url']) : '';
  $posterUrl = isset($poster['url']) ? trim((string) $poster['url']) : '';
  $mime = isset($video['mime_type']) ? (string) $video['mime_type'] : 'video/mp4';
  $playLabel = trim((string) ($c['video_play_label'] ?? ''));
  if ($playLabel === '') {
      $playLabel = __('Play video', 'culvers');
  }
@endphp

@if($videoUrl !== '')
  <section
    class="video-block {{ esc_attr($root) }}"
    data-component-root
    data-video-block>
    <div class="video-block__shell mx-auto w-full max-w-[min(100%,97.5rem)] {{ LayoutShell::GUTTER_X }}">
      {{-- Clips stage scale so hover growth stays inside rounded chrome (no viewport bleed). --}}
      <div class="overflow-hidden rounded-3xl">
        <div
          {{-- Sheet feedback row 13: drop the hover scale ramp on the homepage video tile. --}}
          class="video-block__stage group relative rounded-3xl border-4 border-brand-500 bg-deep-moss"
          x-data="videoBlock()"
          role="region"
          aria-label="{{ esc_attr__('Video', 'culvers') }}">
        <div
          class="relative aspect-video w-full min-h-[240px] overflow-hidden rounded-[1.15rem] sm:min-h-[320px] md:min-h-[400px] lg:aspect-auto lg:h-[742px] sm:rounded-[1.25rem]"
          data-background-parallax-trigger>
          <div class="absolute inset-0 z-0 size-full" data-background-parallax-image="1">
            <video
              x-ref="video"
              class="video-block__video absolute inset-0 z-0 size-full object-cover"
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
            class="pointer-events-none absolute inset-0 z-10 bg-gradient-to-t from-black/35 via-transparent to-black/15 transition-opacity duration-300"
            x-show="!playing"
            x-transition.opacity.duration.200ms
            aria-hidden="true"></div>

          <div
            class="absolute inset-0 z-20 flex items-center justify-center p-6 transition-opacity duration-300"
            x-show="!playing"
            x-transition.opacity.duration.200ms>
            <button
              type="button"
              class="pointer-events-auto inline-flex items-center gap-3 rounded-full border border-brand-500 bg-transparent px-8 py-3 font-sans text-xs font-semibold uppercase tracking-wider text-brand-500 shadow-none outline-none transition-[transform,background-color,color] duration-150 hover:bg-brand-500/15 focus-visible:scale-[1.02] focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 focus-visible:ring-offset-deep-moss motion-safe:active:scale-[0.99]"
              x-on:click="play()"
              aria-label="{{ esc_attr(sprintf(__('Play: %s', 'culvers'), $playLabel)) }}">
              <span class="inline-flex size-0 shrink-0 border-y-[7px] border-l-[12px] border-y-transparent border-l-brand-500" aria-hidden="true"></span>
              <span>{{ esc_html($playLabel) }}</span>
            </button>
          </div>
        </div>
        </div>
      </div>
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => $root,
      'message' => __('Add a video file to this block.', 'culvers'),
  ])
@endif
