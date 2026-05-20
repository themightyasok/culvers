@php
  use App\Helpers\Component;
  use App\Helpers\Image;

  /**
   * Image hero — full-bleed page header. Pairs with `hero_slider` (looping
   * homepage hero); this static variant covers every "header hero" usage:
   * Contact, About, brand-lockup pages, etc. Sizing/colour calibrated to the
   * Figma "Header hero" spec (51:9360 — Get In Touch):
   *   • Band: 480 / 580 / 646px across mobile → desktop (Figma is 646px on 1440)
   *   • Title: text-9xl (96px) on lg, glowleaf default, normal tracking, lh 1
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

  $opRaw = $c['hero_overlay_opacity'] ?? null;
  $opPct = is_numeric($opRaw) ? (int) $opRaw : 20;
  $opPct = max(0, min(85, $opPct));
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
    class="image-hero image-hero--viewport {{ esc_attr($root) }} relative isolate w-full text-white {{ $hasHero ? '' : 'bg-deep-moss' }}"
    data-component-root
    data-image-hero>
    <div class="relative {{ $heroBandMin }} w-full overflow-hidden" data-background-parallax-trigger>
      @if($hasHero)
        <picture class="absolute inset-0 block size-full">
          @if($mobUrl !== '')
            <source media="(max-width: 767px)" srcset="{{ esc_url($mobUrl) }}" />
          @endif
          {!! Image::render($desk, [
              'class' => 'absolute inset-0 size-full object-cover',
              'alt' => '',
              'loading' => 'eager',
              'decoding' => 'async',
              'fetchpriority' => 'high',
              'data' => ['background-parallax-image' => '1'],
          ]) !!}
        </picture>

        <div
          class="pointer-events-none absolute inset-0 z-10 bg-black"
          style="opacity: {{ esc_attr((string) $overlayAlpha) }}"
          aria-hidden="true"></div>
      @endif

      <div
        class="relative z-20 flex {{ $heroBandMin }} w-full flex-col items-center justify-center px-4 pb-12 pt-[length:var(--site-header-offset,11.25rem)] text-center md:px-5 lg:px-6 md:pb-14">
        @if($titleInImage)
          {{-- Title (and any subtitle) is part of the supplied artwork; render --}}
          {{-- only an sr-only h1 so the page still has a real heading for AT. --}}
          <h1 class="sr-only">{{ esc_html($titleLine !== '' ? $titleLine : get_the_title()) }}</h1>
        @elseif($logoUrl !== '')
          <h1 class="sr-only">{{ esc_html($titleLine !== '' ? $titleLine : get_the_title()) }}</h1>
          @php
              $heroLogoAlt = trim((string) ($logo['alt'] ?? ''));
              // Large centre lockups (shop singles etc.): cap by viewport + px ceiling so logos read boldly on wide bands.
              $heroLogoImgArgs = [
                  'class' => 'max-h-[min(60vw,400px)] w-auto max-w-full object-contain md:max-h-[min(52vw,520px)] lg:max-h-[min(50vw,640px)]',
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
        {{-- Image-hero H1 — same type ramp as hero-slider; wider max-width for wrapping; editorial `<br>` / newlines kept. --}}
        @elseif($titleLine !== '')
          @php
              $titleSafe = preg_replace('#<br\s*/?>#i', "\n", $titleLine);
          @endphp
          {{-- Desktop H1 (md:9xl, lg:7.75rem) kept exactly as shipped — mobile uses
               `max-sm:` overrides to land Figma's H1 Mobile token (Canela 48 / lh 1.1)
               without touching the tablet+ ramp above. --}}
          <h1 class="image-hero__title mx-auto max-w-[min(100%,68rem)] text-balance break-words {{ Component::imageHeroTitleClasses($titleToneClass) }}">
            {!! nl2br(e($titleSafe)) !!}
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
