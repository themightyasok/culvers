@php
  use App\Helpers\Component;
  use App\Helpers\Image;

  /**
   * Hero slider — full-viewport Splide carousel. First slide hosts the page H1
   * (lazy/fetchpriority="high"); subsequent slides demote to H2.
   * Bleeds under the fixed header via `--site-header-offset` reserved padding.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $slidesRaw = $c['hero_slides'] ?? [];
  $slides = is_array($slidesRaw) ? $slidesRaw : [];
  $slides = array_values(array_filter($slides, static function ($row): bool {
      if (! is_array($row)) {
          return false;
      }
      $img = $row['slide_image'] ?? null;

      return is_array($img) && ! empty($img['url']);
  }));
  $slideCount = count($slides);

  $align = (string) ($c['hero_content_align'] ?? 'center');
  if (! in_array($align, ['left', 'center', 'right'], true)) {
      $align = 'center';
  }

  $justifyMap = [
      'left' => 'justify-start',
      'center' => 'justify-center',
      'right' => 'justify-end',
  ];
  $textMap = [
      'left' => 'text-left',
      'center' => 'text-center',
      'right' => 'text-right',
  ];
  $justify = $justifyMap[$align];
  $textAlign = $textMap[$align];

  $btnWrapMap = [
      'left' => 'justify-start',
      'center' => 'justify-center',
      'right' => 'justify-end',
  ];
  $btnWrap = $btnWrapMap[$align];
@endphp

@if($slides !== [])
  <section
    class="hero-slider hero-slider--viewport {{ esc_attr($root) }} relative isolate bg-deep-moss text-white"
    data-component-root
    data-hero-slider
    data-hero-slide-count="{{ $slideCount }}"
    x-data="heroSlider()"
    aria-roledescription="{{ esc_attr__('Carousel', 'culvers') }}"
    aria-label="{{ esc_attr__('Hero', 'culvers') }}">
    <div
      class="splide hero-slider__splide overflow-hidden"
      x-ref="splideRoot"
      data-splide-manual
      aria-live="polite">
      <div class="splide__track hero-slider__track">
        <ul class="splide__list">
          @foreach($slides as $idx => $slide)
            @php
              $slide = is_array($slide) ? $slide : [];
              $desk = isset($slide['slide_image']) && is_array($slide['slide_image']) ? $slide['slide_image'] : [];
              $mob = isset($slide['slide_image_mobile']) && is_array($slide['slide_image_mobile']) ? $slide['slide_image_mobile'] : [];
              $deskUrl = isset($desk['url']) ? (string) $desk['url'] : '';
              $mobUrl = isset($mob['url']) ? (string) $mob['url'] : '';
              $alt = isset($desk['alt']) ? trim((string) $desk['alt']) : '';
              if ($alt === '') {
                  $alt = isset($mob['alt']) ? trim((string) $mob['alt']) : '';
              }
              if ($alt === '') {
                  $alt = __('Hero slide', 'culvers');
              }

              $headline = trim((string) ($slide['slide_headline'] ?? ''));
              if ($headline !== '') {
                  $headline = preg_replace('/<br\s*\/?>/i', "\n", $headline);
              }
              $kicker = trim((string) ($slide['slide_kicker'] ?? ''));
              $body = trim((string) ($slide['slide_body'] ?? ''));
              $ctaLabel = trim((string) ($slide['slide_cta_label'] ?? ''));
              $ctaUrl = trim((string) ($slide['slide_cta_url'] ?? ''));
              $headingTag = $idx === 0 ? 'h1' : 'h2';
            @endphp
            @if($deskUrl !== '')
              <li class="splide__slide hero-slider__slide">
                {{-- The 6px Glowleaf inset keyline lives on `.hero-slider--viewport::after`
                     (single source — see resources/styles/app.css). --}}
                <div
                  class="relative min-h-[100svh] w-full overflow-hidden"
                  data-background-parallax-trigger>
                  {!! Image::renderResponsiveCover($desk, $mobUrl !== '' ? $mob : null, [
                      'class' => 'absolute inset-0 size-full object-cover',
                      'alt' => $alt,
                      'width' => isset($desk['width']) ? (int) $desk['width'] : 1920,
                      'height' => isset($desk['height']) ? (int) $desk['height'] : 1080,
                      'loading' => $idx === 0 ? 'eager' : 'lazy',
                      'decoding' => 'async',
                      'fetchpriority' => $idx === 0 ? 'high' : 'low',
                      'data' => ['background-parallax-image' => '1'],
                  ]) !!}

                  {{-- Soft-light rotated white square decoration (Figma `51:4920`).
                       1048px diamond, 28% white, mix-blend soft-light, anchored to the
                       upper-left third — purely decorative, hidden on small viewports. --}}
                  <div
                    class="pointer-events-none absolute left-[12.5%] top-[-200px] hidden size-[1048px] -rotate-45 bg-white/[0.28] mix-blend-soft-light md:block"
                    aria-hidden="true"></div>

                  {{-- Figma uses a single 40% black scrim across the hero, not a gradient. --}}
                  <div class="pointer-events-none absolute inset-0 z-0 bg-black/40" aria-hidden="true"></div>

                  <div
                    class="hero-slider__stage relative z-10 flex min-h-[100svh] w-full items-center px-4 pb-16 pt-[length:var(--site-header-offset,11.25rem)] md:px-5 lg:px-6 {{ esc_attr($justify) }}">
                    <div class="hero-slider__copy pointer-events-auto max-w-[min(100%,60rem)] motion-safe:transition-opacity motion-safe:duration-300 motion-safe:ease-out {{ esc_attr($textAlign) }}">
                      @if($headline !== '')
                        @php
                          /* Editors can use `<br />` or newlines in the slide headline — preserve as `<br>`
                             after escaping; widen `.hero-slider__copy` so automatic wrapping hits ~two lines
                             once manual breaks end. */
                          $headlineSafe = preg_replace('#<br\s*/?>#i', "\n", $headline);
                        @endphp
                        <{{ $headingTag }} class="hero-slider__title text-balance break-words font-heading text-5xl leading-[1.1] text-brand-500 sm:text-7xl sm:leading-none md:text-8xl lg:text-9xl">
                          {!! nl2br(e($headlineSafe)) !!}
                        </{{ $headingTag }}>
                      @endif

                      @if($kicker !== '')
                        <p class="mt-5 {{ Component::heroKickerClasses() }}">
                          {{ esc_html($kicker) }}
                        </p>
                      @endif

                      @if($body !== '')
                        <p class="mt-5 font-sans text-lg leading-relaxed text-white/90 md:text-xl">
                          {!! nl2br(e($body)) !!}
                        </p>
                      @endif

                      @if($ctaLabel !== '' && $ctaUrl !== '')
                        <div class="mt-10 flex w-full {{ esc_attr($btnWrap) }}">
                          {{-- Banner-scale CTA: `size=large` keeps the canonical Figma hover-widen
                               (40px → 56px on hover) instead of being killed by an inline `px-*`. --}}
                          @include('components.button', [
                              'label' => $ctaLabel,
                              'href' => $ctaUrl,
                              'size' => 'large',
                          ])
                        </div>
                      @endif
                    </div>
                  </div>
                </div>
              </li>
            @endif
            @endforeach
        </ul>
      </div>
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => $root,
      'message' => __('Add at least one hero slide with a desktop image.', 'culvers'),
  ])
@endif
