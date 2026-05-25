@php
  use App\Helpers\Component;
  use App\Helpers\Image;

  /**
   * Image hero — full-bleed page header. Pairs with `hero_slider` (looping
   * homepage hero); this static variant covers every "header hero" usage:
   * Contact, About, brand-lockup pages, etc. Sizing/colour calibrated to the
   * Figma "Header hero" spec (51:9360 — Get In Touch):
   *   • Band: 480 / 580 / 646px across mobile → desktop (Figma is 646px on 1440)
   *   • Title: Canela 46 px / lh 1.1 mobile (`51:9234`), 96 px / lh 96 desktop (`51:9364`)
   *   • Subtitle: 20px / SemiBold / 4px tracking on lg, scales down on mobile
   *   • Overlay default 20% (was 50%); editor can dial up to 85%
   * Bleeds under the fixed header — `includePadding: false` strips the default
   * `pt-16 pb-16` so the glowleaf inset keyline hugs the image (no moss bands).
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $desk = isset($c['hero_image']) && is_array($c['hero_image']) ? $c['hero_image'] : [];
  $mob = isset($c['hero_image_mobile']) && is_array($c['hero_image_mobile']) ? $c['hero_image_mobile'] : [];
  $deskUrl = isset($desk['url']) ? trim((string) $desk['url']) : '';
  $mobUrl = isset($mob['url']) ? trim((string) $mob['url']) : '';

  $logo = isset($c['hero_logo']) && is_array($c['hero_logo']) ? $c['hero_logo'] : [];
  $logoUrl = isset($logo['url']) ? trim((string) $logo['url']) : '';

  $titleLine = trim((string) ($c['hero_title_line'] ?? ''));
  $titleToneRaw = is_string($c['hero_title_tone'] ?? null) ? (string) $c['hero_title_tone'] : 'glowleaf';
  $titleToneClass = match ($titleToneRaw) {
      'white' => 'text-white',
      'lighter-cream' => 'text-lighter-cream',
      default => 'text-glowleaf',
  };

  $subLineRaw = trim((string) ($c['hero_subtitle_line'] ?? ''));
  $subWithBreaks = preg_replace('/<br\s*\/?>/i', "\n", $subLineRaw);
  $subParts = [];
  if ($subWithBreaks !== '') {
      $subParts = array_values(array_filter(array_map(static function (string $line): string {
          return trim($line);
      }, preg_split('/\r\n|\r|\n/', wp_strip_all_tags($subWithBreaks)))));
  }

  $opPct = \App\Helpers\Component::overlayOpacityPercent($c['hero_overlay_opacity'] ?? null, 20);
  $overlayAlpha = $opPct / 100;

  /**
   * When the supplied hero image already has the page title burnt into the
   * artwork (typical for Figma developer-handover assets), suppress the
   * component's visible title/subtitle overlay so it doesn't appear twice;
   * we still render an sr-only h1 so the page keeps a real heading for AT.
   */
  $titleInImage = ! empty($c['hero_title_in_image']);

  $hasHero = $deskUrl !== '';
  /**
   * Editorial completeness check: render a band as long as there is an image,
   * logo, title, or subtitle to anchor it. This lets shop / brand singles
   * without a Figma photograph still ship a deep-moss "logo lockup" hero
   * (logo or brand name centred on the brand colour) instead of disappearing.
   */
  $hasContent = $hasHero || $logoUrl !== '' || $titleLine !== '' || $subParts !== [];
  /** Figma 51:9360 band heights — fixed steps so type sizing reads consistently across breakpoints.
   *  Mobile-only override (Figma 51:9234 / 51:9493 mobile frame is taller than desktop) lives in
   *  `max-sm:min-h-[640px]` so tablet + desktop keep the original 580 / 646 px. */
  $heroBandMin = 'min-h-[480px] md:min-h-[580px] lg:min-h-[646px] max-sm:min-h-[640px]';
@endphp

@if($hasContent)
  <section
    class="image-hero image-hero--viewport {{ esc_attr($root) }} relative isolate w-full text-white {{ $hasHero ? '' : 'text-hero-viewport' }}"
    data-component-root
    data-image-hero>
    <div class="relative {{ $heroBandMin }} w-full overflow-hidden">
      @if(! $hasHero)
        @include('partials.text-hero-backdrop')
      @endif
      @if($hasHero)
        {!! Image::renderResponsiveCover($desk, $mobUrl !== '' ? $mob : null, [
            'class' => 'absolute inset-0 size-full object-cover object-top',
            'alt' => '',
            'loading' => 'eager',
            'decoding' => 'async',
            'fetchpriority' => 'high',
        ]) !!}

        <div
          class="pointer-events-none absolute inset-0 z-10 bg-black"
          style="opacity: {{ esc_attr((string) $overlayAlpha) }}"
          aria-hidden="true"></div>
      @endif

      <div
        class="relative z-20 flex {{ $heroBandMin }} w-full flex-col items-center justify-center px-4 pb-12 pt-[length:var(--site-header-offset,var(--site-header-offset-fallback))] text-center md:px-5 lg:px-6 md:pb-14">
        @if($titleInImage)
          {{-- Title (and any subtitle) is part of the supplied artwork; render --}}
          {{-- only an sr-only h1 so the page still has a real heading for AT. --}}
          <h1 class="sr-only">{{ esc_html($titleLine !== '' ? $titleLine : get_the_title()) }}</h1>
        @elseif($logoUrl !== '')
          <h1 class="sr-only">{{ esc_html($titleLine !== '' ? $titleLine : get_the_title()) }}</h1>
          @php
              $heroLogoAlt = trim((string) ($logo['alt'] ?? ''));
              // Omit HTML width/height (pass 0) so intrinsic px do not cap size; Figma max-*
              // caps live in unlayered `app.css` (`.image-hero__logo`) — never force a display width.
              $heroLogoImgArgs = [
                  'class' => 'image-hero__logo',
                  'width' => 0,
                  'height' => 0,
                  'loading' => 'eager',
                  'decoding' => 'async',
              ];
              if ($heroLogoAlt !== '') {
                  $heroLogoImgArgs['alt'] = $heroLogoAlt;
              } else {
                  $heroLogoImgArgs['alt'] = '';
                  $heroLogoImgArgs['role'] = 'presentation';
              }
          @endphp
          <div class="flex max-w-[min(100%,72rem)] justify-center px-2">
            {!! Image::render($logo, $heroLogoImgArgs) !!}
          </div>
        @elseif($titleLine !== '')
          <h1 class="image-hero__title md:whitespace-nowrap {{ Component::imageHeroTitleClasses($titleToneClass) }}">
            {{ esc_html(preg_replace('/\s+/u', ' ', $titleLine)) }}
          </h1>
        @else
          {{-- Product-only heroes (e.g. Figma 51:6394) leave the title line blank. --}}
          <h1 class="sr-only">{{ esc_html(get_the_title()) }}</h1>
        @endif

        @if(! $titleInImage && ! empty($subParts))
          {{-- Sheet feedback row 21: page-hero secondary line should be Commuter Sans (Figma 51:9080 etc). --}}
          <p class="image-hero__subtitle mt-6 max-w-2xl {{ Component::imageHeroSubtitleClasses() }}">
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
      'wrapperClasses' => $root,
      'message' => __('Add a hero image to this block.', 'culvers'),
  ])
@endif
