@php
  use App\Helpers\Image;

  $href = trim((string) ($card['url'] ?? ''));
  $title = trim((string) ($card['title'] ?? ''));
  $mediaType = (string) ($card['media_type'] ?? 'image');
  $video = isset($card['video']) && is_array($card['video']) ? $card['video'] : [];
  $videoUrl = isset($video['url']) ? (string) $video['url'] : '';
  $mime = isset($video['mime_type']) ? (string) $video['mime_type'] : 'video/mp4';
  $image = isset($card['image']) && is_array($card['image']) ? $card['image'] : [];
  $imageUrl = isset($image['url']) ? (string) $image['url'] : '';
  $alt = trim((string) ($card['alt'] ?? $title));
  $cardAspectClass = $cardAspectClass ?? 'aspect-[2/3]';
  $isManualMode = ! empty($isManualMode);
  $showMobileArrow = ! empty($showMobileArrow);
@endphp

@if($href !== '' && $title !== '')
  <a
    href="{{ esc_url($href) }}"
    class="three-card-block__card group/card relative flex {{ $cardAspectClass }} w-full origin-center rounded-[11px] outline-none motion-safe:transition-transform motion-safe:duration-300 motion-safe:ease-out motion-safe:hover:scale-[1.03] motion-safe:focus-visible:scale-[1.03] motion-reduce:hover:scale-100 motion-reduce:focus-visible:scale-100 focus-visible:ring-2 focus-visible:ring-glowleaf focus-visible:ring-offset-2 focus-visible:ring-offset-light-cream">
    <span
      class="pointer-events-none absolute inset-0 z-0 overflow-hidden rounded-[inherit]"
      aria-hidden="true">
      @if($mediaType === 'video' && $videoUrl !== '')
        <span class="relative z-0 block h-full min-h-0 w-full">
          <video
            class="three-card-block__media absolute inset-0 h-full w-full object-cover motion-safe:transition-transform motion-safe:duration-700 motion-safe:ease-out motion-safe:group-hover/card:scale-[1.08] motion-safe:group-focus-within/card:scale-[1.08] motion-reduce:group-hover/card:scale-100 motion-reduce:group-focus-within/card:scale-100"
            data-three-card-video
            data-gsap-autoplay="off"
            muted
            playsinline
            loop
            preload="auto">
            <source src="{{ esc_url($videoUrl) }}" type="{{ esc_attr($mime) }}" />
          </video>
        </span>
      @elseif($imageUrl !== '')
        <span class="relative z-0 block h-full min-h-0 w-full">
          {!! Image::render($image, [
              'class' => 'three-card-block__media absolute inset-0 h-full w-full object-cover motion-safe:transition-transform motion-safe:duration-700 motion-safe:ease-out motion-safe:group-hover/card:scale-[1.08] motion-safe:group-focus-within/card:scale-[1.08] motion-reduce:group-hover/card:scale-100 motion-reduce:group-focus-within/card:scale-100',
              'alt' => $alt,
              'width' => 800,
              'height' => 1200,
          ]) !!}
        </span>
      @else
        <span class="block h-full w-full bg-gradient-to-br from-dustleaf/40 via-deep-moss/25 to-faded-olive/35"></span>
      @endif

      <span class="pointer-events-none absolute inset-0 z-10 bg-black/25"></span>
    </span>

    <span
      class="relative z-10 flex w-full flex-1 flex-col items-center justify-center gap-5 px-6 py-10 text-center {{ $isManualMode ? 'max-sm:flex-row max-sm:justify-between max-sm:gap-4 max-sm:px-7 max-sm:py-6 max-sm:text-left' : '' }}">
      <span
        class="font-heading text-4xl leading-[1.1] text-white transition-colors duration-300 ease-out motion-safe:group-hover/card:text-glowleaf motion-safe:group-focus-within/card:text-glowleaf md:text-5xl md:leading-none">
        {{ esc_html($title) }}
      </span>
      @if($showMobileArrow)
        <span
          aria-hidden="true"
          class="hidden size-[43px] shrink-0 items-center justify-center rounded-full bg-glowleaf text-deep-moss transition-transform duration-300 ease-out motion-safe:group-hover/card:scale-[1.06] motion-safe:group-focus-within/card:scale-[1.06] max-sm:inline-flex">
          @include('partials.icons.figma-header-icon', [
              'header_icon_variant' => 'explore-arrow',
              'header_icon_class' => 'size-4 shrink-0',
          ])
        </span>
      @endif
      <span
        aria-hidden="true"
        class="three-card-block__cta btn btn-outline pointer-events-auto px-7 opacity-0 transition-opacity duration-300 ease-out motion-safe:group-hover/card:opacity-100 motion-safe:group-focus-within/card:opacity-100 motion-reduce:hidden max-sm:hidden">
        {{ __('Explore', 'culvers') }}
      </span>
    </span>
  </a>
@endif
