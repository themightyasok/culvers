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
  /** @var 'inline'|'stack' $mobileArrowLayout Figma `51:8216` inline vs `51:8347` stacked. */
  $mobileArrowLayout = in_array($mobileArrowLayout ?? 'stack', ['inline', 'stack'], true)
      ? (string) ($mobileArrowLayout ?? 'stack')
      : 'stack';
  $mediaOverlayOpacity = isset($mediaOverlayOpacity) && is_numeric($mediaOverlayOpacity)
      ? max(0, min(100, (int) $mediaOverlayOpacity))
      : 25;
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

      @if($mediaOverlayOpacity > 0)
        <span
          class="pointer-events-none absolute inset-0 z-10"
          style="background-color: rgba(0,0,0,{{ $mediaOverlayOpacity / 100 }})"
          aria-hidden="true"></span>
      @endif
    </span>

    <span
      class="relative z-10 flex w-full flex-1 items-center justify-center px-4 py-5 text-center sm:px-5 sm:py-6 max-sm:px-5 max-sm:py-4">
      <span
        @class([
            'three-card-block__hover-stack relative flex max-w-full flex-col items-center text-center',
            'max-sm:inline-flex max-sm:flex-row max-sm:items-center max-sm:justify-center max-sm:gap-4' => $showMobileArrow && $mobileArrowLayout === 'inline',
            'max-sm:flex max-sm:flex-col max-sm:items-center max-sm:justify-center max-sm:gap-4' => $showMobileArrow && $mobileArrowLayout === 'stack',
        ])>
        <span
          @class([
              'three-card-block__title font-heading text-4xl leading-[1.1] text-white transition-colors duration-300 ease-out motion-safe:group-hover/card:text-glowleaf motion-safe:group-focus-within/card:text-glowleaf md:text-5xl md:leading-none',
              'sm:max-w-full sm:block sm:text-balance' => ! $showMobileArrow,
          ])>
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
          class="three-card-block__cta btn btn-outline pointer-events-none whitespace-nowrap opacity-0 transition-opacity duration-300 ease-out motion-safe:group-hover/card:pointer-events-auto motion-safe:group-hover/card:opacity-100 motion-safe:group-focus-within/card:pointer-events-auto motion-safe:group-focus-within/card:opacity-100 motion-reduce:hidden max-sm:hidden">
          {{ __('Explore', 'culvers') }}
        </span>
      </span>
    </span>
  </a>
@endif
