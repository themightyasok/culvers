@php
  use App\Helpers\Grid;
  use App\Helpers\Padding;

  $c = is_array($component ?? null) ? $component : [];
  $padding = Padding::getClasses($c);
  $grid = $c['_grid_classes'] ?? '';
  if ($grid !== '') {
      $grid = Grid::stripHorizontalInsetPadding($grid);
  }

  $desk = isset($c['hero_image']) && is_array($c['hero_image']) ? $c['hero_image'] : [];
  $mob = isset($c['hero_image_mobile']) && is_array($c['hero_image_mobile']) ? $c['hero_image_mobile'] : [];
  $deskUrl = isset($desk['url']) ? trim((string) $desk['url']) : '';
  $mobUrl = isset($mob['url']) ? trim((string) $mob['url']) : '';

  $logo = isset($c['hero_logo']) && is_array($c['hero_logo']) ? $c['hero_logo'] : [];
  $logoUrl = isset($logo['url']) ? trim((string) $logo['url']) : '';

  $titleLine = trim((string) ($c['hero_title_line'] ?? ''));
  $subLineRaw = trim((string) ($c['hero_subtitle_line'] ?? ''));
  $subWithBreaks = preg_replace('/<br\s*\/?>/i', "\n", $subLineRaw);
  $subParts = [];
  if ($subWithBreaks !== '') {
      $subParts = array_values(array_filter(array_map(static function (string $line): string {
          return trim($line);
      }, preg_split('/\r\n|\r|\n/', wp_strip_all_tags($subWithBreaks)))));
  }

  $opRaw = $c['hero_overlay_opacity'] ?? null;
  $opPct = is_numeric($opRaw) ? (int) $opRaw : 50;
  $opPct = max(0, min(85, $opPct));
  $overlayAlpha = $opPct / 100;

  $hasHero = $deskUrl !== '';
@endphp

@if($hasHero)
  {{-- Full-viewport hero under fixed header (matches hero_slider slides). Omit vertical padding — default pt/pb would show as moss bands. --}}
  <section
    class="{{ esc_attr(trim($grid)) }} shop-image-hero--viewport relative isolate w-full text-white"
    data-component-root
    data-shop-image-hero>
    <div class="relative min-h-[100svh] w-full overflow-hidden" data-background-parallax-trigger>
      <picture class="absolute inset-0 block size-full">
        @if($mobUrl !== '')
          <source media="(max-width: 767px)" srcset="{{ esc_url($mobUrl) }}" />
        @endif
        <img
          src="{{ esc_url($deskUrl) }}"
          alt=""
          class="absolute inset-0 size-full object-cover"
          data-background-parallax-image="1"
          loading="eager"
          decoding="async"
          fetchpriority="high"
          @if(isset($desk['width'])) width="{{ (int) $desk['width'] }}" @endif
          @if(isset($desk['height'])) height="{{ (int) $desk['height'] }}" @endif />
      </picture>

      <div
        class="pointer-events-none absolute inset-0 z-[1] bg-black"
        style="opacity: {{ esc_attr((string) $overlayAlpha) }}"
        aria-hidden="true"></div>

      <div
        class="relative z-[2] flex min-h-[100svh] w-full flex-col items-center justify-center px-6 pb-16 pt-[length:var(--site-header-offset,11.25rem)] text-center sm:px-10 md:px-[46px]">
        @if($logoUrl !== '')
          <h1 class="sr-only">{{ esc_html($titleLine !== '' ? $titleLine : get_the_title()) }}</h1>
          <div class="flex max-w-[min(100%,52rem)] justify-center">
            <img
              src="{{ esc_url($logoUrl) }}"
              alt=""
              role="presentation"
              class="max-h-[min(35vw,200px)] w-auto max-w-full object-contain md:max-h-[min(28vw,260px)]"
              loading="eager"
              decoding="async"
              @if(isset($logo['width'])) width="{{ (int) $logo['width'] }}" @endif
              @if(isset($logo['height'])) height="{{ (int) $logo['height'] }}" @endif />
          </div>
        @elseif($titleLine !== '')
          <h1 class="font-heading text-[clamp(2.75rem,8vw,5.25rem)] leading-[1.05] tracking-tight text-white">
            {{ esc_html($titleLine) }}
          </h1>
        @else
          <h1 class="font-heading text-[clamp(2.75rem,8vw,5.25rem)] leading-[1.05] tracking-tight text-white">
            {{ esc_html(get_the_title()) }}
          </h1>
        @endif

        @if(! empty($subParts))
          <p class="mt-5 max-w-xl font-sans text-micro font-medium uppercase tracking-kicker text-white md:text-xs md:tracking-[0.28em]">
            @foreach($subParts as $i => $line)
              @if($i > 0)
                <br />
              @endif
              {{ esc_html(trim($line)) }}
            @endforeach
          </p>
        @endif
      </div>
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => trim($grid . ' ' . $padding),
      'message' => __('Add a hero image to this block.', 'culvers'),
  ])
@endif
