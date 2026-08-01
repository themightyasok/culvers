@php
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;

  /**
   * Video block — contained video or static image in the branded brand-500 frame.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $mediaType = is_string($c['video_media_type'] ?? null) ? trim((string) $c['video_media_type']) : 'video';
  if ($mediaType !== 'image') {
      $mediaType = 'video';
  }

  $video = isset($c['video_file']) && is_array($c['video_file']) ? $c['video_file'] : [];
  $poster = isset($c['video_poster']) && is_array($c['video_poster']) ? $c['video_poster'] : [];
  $still = isset($c['video_image']) && is_array($c['video_image']) ? $c['video_image'] : [];
  $videoUrl = isset($video['url']) ? trim((string) $video['url']) : '';
  $posterUrl = isset($poster['url']) ? trim((string) $poster['url']) : '';
  $stillUrl = isset($still['url']) ? trim((string) $still['url']) : '';
  $mime = isset($video['mime_type']) ? (string) $video['mime_type'] : 'video/mp4';
  $playLabel = trim((string) ($c['video_play_label'] ?? ''));
  if ($playLabel === '') {
      $playLabel = __('Play video', 'culvers');
  }

  $isImage = $mediaType === 'image';
  $hasVideo = ! $isImage && $videoUrl !== '';
  $hasImage = $isImage && $stillUrl !== '';
  $show = $hasVideo || $hasImage;
@endphp

@if($show)
  <section
    class="video-block {{ esc_attr($root) }}"
    data-component-root
    data-video-block
    data-video-block-mode="{{ esc_attr($mediaType) }}">
    <div class="video-block__shell mx-auto w-full max-w-[min(100%,97.5rem)] {{ LayoutShell::GUTTER_X }}">
      <div class="overflow-hidden rounded-3xl">
        <div
          class="video-block__stage group relative rounded-3xl border-4 border-brand-500 bg-deep-moss"
          @if($hasVideo)
            x-data="videoBlock()"
            role="region"
            aria-label="{{ esc_attr__('Video', 'culvers') }}"
          @else
            role="region"
            aria-label="{{ esc_attr__('Featured image', 'culvers') }}"
          @endif>
        <div
          class="relative aspect-video w-full min-h-[240px] overflow-hidden rounded-[1.15rem] sm:min-h-[320px] md:min-h-[400px] lg:aspect-auto lg:h-[742px] sm:rounded-[1.25rem]"
          data-background-parallax-trigger>
          <div class="absolute inset-0 z-0 size-full" data-background-parallax-image="1">
            @if($hasImage)
              {!! Image::render($still, [
                  'class' => 'video-block__image absolute inset-0 z-0 size-full object-cover',
                  'alt' => '',
                  'loading' => 'lazy',
                  'decoding' => 'async',
              ]) !!}
            @else
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
            @endif
          </div>

          @if($hasVideo)
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
                class="btn btn-outline pointer-events-auto inline-flex items-center gap-3 px-7"
                x-on:click="play()"
                aria-label="{{ esc_attr(sprintf(__('Play: %s', 'culvers'), $playLabel)) }}">
                <span
                  class="inline-flex size-0 shrink-0 border-y-[7px] border-l-[12px] border-y-transparent border-l-current"
                  aria-hidden="true"></span>
                <span>{{ esc_html($playLabel) }}</span>
              </button>
            </div>
          @endif
        </div>
        </div>
      </div>
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => $root,
      'message' => $isImage
          ? __('Add an image to this block.', 'culvers')
          : __('Add a video file to this block.', 'culvers'),
  ])
@endif
