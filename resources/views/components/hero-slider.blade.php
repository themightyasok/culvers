@php
  use App\Helpers\Padding;

  $c = is_array($component ?? null) ? $component : [];
  $padding = Padding::getClasses($c);
  $grid = $c['_grid_classes'] ?? '';
  /* Full-bleed hero: strip grid gutters — horizontal px-* on the section inset the slide and show bg-deep-moss. */
  if ($grid !== '') {
      $grid = trim(preg_replace('/\s+/', ' ', preg_replace('/\b(?:sm:|md:|lg:|xl:)?px-[^\s]+\s*/', '', $grid)));
  }

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
    class="{{ esc_attr(trim($grid . ' ' . $padding)) }} hero-slider--viewport relative isolate bg-deep-moss text-white"
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
              $kicker = trim((string) ($slide['slide_kicker'] ?? ''));
              $body = trim((string) ($slide['slide_body'] ?? ''));
              $ctaLabel = trim((string) ($slide['slide_cta_label'] ?? ''));
              $ctaUrl = trim((string) ($slide['slide_cta_url'] ?? ''));
              $headingTag = $idx === 0 ? 'h1' : 'h2';
            @endphp
            @if($deskUrl !== '')
              <li class="splide__slide hero-slider__slide">
                <div
                  class="relative min-h-[100svh] w-full overflow-hidden"
                  data-background-parallax-trigger>
                  <picture class="absolute inset-0 block size-full">
                    @if($mobUrl !== '')
                      <source media="(max-width: 767px)" srcset="{{ esc_url($mobUrl) }}" />
                    @endif
                    <img
                      class="absolute inset-0 size-full object-cover"
                      src="{{ esc_url($deskUrl) }}"
                      alt="{{ esc_attr($alt) }}"
                      width="{{ isset($desk['width']) ? (int) $desk['width'] : 1920 }}"
                      height="{{ isset($desk['height']) ? (int) $desk['height'] : 1080 }}"
                      decoding="async"
                      fetchpriority="{{ $idx === 0 ? 'high' : 'low' }}"
                      loading="{{ $idx === 0 ? 'eager' : 'lazy' }}"
                      data-background-parallax-image="1" />
                  </picture>

                  <div class="pointer-events-none absolute inset-0 z-0 bg-gradient-to-b from-black/45 via-black/25 to-black/35" aria-hidden="true"></div>

                  <div
                    class="hero-slider__stage relative z-[1] flex min-h-[100svh] w-full items-center px-6 pb-16 pt-[length:var(--site-header-offset,11.25rem)] sm:px-10 md:px-[46px] {{ esc_attr($justify) }}">
                    <div class="hero-slider__copy pointer-events-auto max-w-[40rem] motion-safe:transition-[opacity,transform] motion-safe:duration-300 motion-safe:ease-out {{ esc_attr($textAlign) }}">
                      @if($headline !== '')
                        <{{ $headingTag }} class="font-heading text-4xl leading-[1.08] tracking-tight text-brand-500 sm:text-5xl md:text-6xl lg:text-[4rem] lg:leading-[1.05]">
                          {!! nl2br(e($headline)) !!}
                        </{{ $headingTag }}>
                      @endif

                      @if($kicker !== '')
                        <p class="mt-5 font-sans text-xs font-semibold uppercase tracking-[0.22em] text-white md:text-sm">
                          {{ esc_html($kicker) }}
                        </p>
                      @endif

                      @if($body !== '')
                        <p class="mt-5 font-sans text-base leading-relaxed text-white/90 md:text-lg">
                          {!! nl2br(e($body)) !!}
                        </p>
                      @endif

                      @if($ctaLabel !== '' && $ctaUrl !== '')
                        <div class="mt-10 flex w-full {{ esc_attr($btnWrap) }}">
                          <a class="btn btn-primary px-10 py-3 md:px-12" href="{{ esc_url($ctaUrl) }}">
                            {{ esc_html($ctaLabel) }}
                          </a>
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
  <div class="{{ esc_attr(trim($grid . ' ' . $padding)) }} rounded border border-amber-400 bg-amber-50 px-4 py-3 text-amber-950">
    {{ __('Add at least one hero slide with a desktop image.', 'culvers') }}
  </div>
@endif
