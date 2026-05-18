@extends('layouts.app')

@php
  use App\Helpers\Image;

  /**
   * Culver Square 404 — single hero band that mirrors the homepage hero
   * visual idiom (Glowleaf 6px inset keyline · soft-light rotated decorative
   * square · flat 20 % black scrim · centred Canela headline + Commuters
   * kicker + Glowleaf CTA).
   *
   * Figma frame `51:7648` referenced 855px band height — live behaviour matches the
   * homepage hero viewport fill (`min-h-[100svh]`).
   *
   * Image source:
   *   1. `culvers_404_image_id` theme mod (Appearance → Customize).
   *   2. First hero slide on the front page (re-uses already-uploaded media).
   *   3. Falls back to a flat deep-moss tone if neither is available.
   */
  $bgId = (int) get_theme_mod('culvers_404_image_id', 0);

  if ($bgId === 0) {
      $homeId = (int) get_option('page_on_front', 0);
      if ($homeId > 0 && function_exists('get_field')) {
          $components = get_field('components', $homeId);
          if (is_array($components)) {
              foreach ($components as $row) {
                  if (! is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'hero_slider') {
                      continue;
                  }
                  $slides = $row['hero_slides'] ?? [];
                  if (! is_array($slides)) {
                      continue;
                  }
                  foreach ($slides as $slide) {
                      $img = is_array($slide) ? ($slide['slide_image'] ?? null) : null;
                      if (is_array($img) && (int) ($img['ID'] ?? 0) > 0) {
                          $bgId = (int) $img['ID'];
                          break 2;
                      }
                  }
              }
          }
      }
  }

  $bgUrl = $bgId > 0 ? (string) wp_get_attachment_image_url($bgId, 'full') : '';

  $headline = (string) apply_filters(
      'culvers_404_headline',
      // Real apostrophes (U+2019) — `\u2019` in PHP single-quoted strings is not decoded and leaked to HTML.
      __('This wasn’t on the map.', 'culvers')
  );
  $kicker = (string) apply_filters(
      'culvers_404_kicker',
      __('Let’s get you back on track', 'culvers')
  );
  $ctaLabel = (string) apply_filters('culvers_404_cta_label', __('Return to homepage', 'culvers'));
  $ctaUrl = (string) apply_filters('culvers_404_cta_url', home_url('/'));
@endphp

@section('content')
  {{-- Same viewport fill as homepage hero slides (`hero-slider.blade.php` inner `min-h-[100svh]`). --}}
  <section
    class="four-oh-four hero-slider--viewport relative isolate flex min-h-[100svh] w-full items-center justify-center overflow-hidden bg-deep-moss text-white"
    aria-labelledby="four-oh-four-heading">
    @if($bgUrl !== '' && $bgId > 0)
      <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
        {!! Image::render(
            [
                'ID' => $bgId,
                'url' => $bgUrl,
                'alt' => '',
            ],
            [
                'class' => 'absolute inset-0 size-full object-cover',
                'alt' => '',
                'width' => 1920,
                'height' => 1080,
                'loading' => 'eager',
                'decoding' => 'async',
                'fetchpriority' => 'high',
            ]
        ) !!}
      </div>
    @endif

    {{-- Soft-light rotated white square (Figma `51:7651`, mix-blend-overlay 20%). --}}
    <div
      class="pointer-events-none absolute left-[20%] top-[-220px] hidden size-[932px] -rotate-45 bg-white/20 mix-blend-overlay md:block"
      aria-hidden="true"></div>

    {{-- Flat 20 % black scrim — Figma uses a single scrim, not a gradient. --}}
    <div class="pointer-events-none absolute inset-0 z-0 bg-black/20" aria-hidden="true"></div>

    <div
      class="relative z-10 mx-auto flex w-full max-w-[min(100%,60rem)] flex-col items-center px-4 pb-16 pt-[length:var(--site-header-offset,11.25rem)] text-center md:px-5 lg:px-6">
      {{-- Match `.hero-slider__copy` width + title ramp so the lockup wraps ~two lines like the homepage hero. --}}
      <h1
        id="four-oh-four-heading"
        class="text-balance break-words font-heading text-5xl leading-[1.1] text-brand-500 sm:text-7xl sm:leading-none md:text-8xl lg:text-9xl">
        {{ esc_html($headline) }}
      </h1>

      <p class="mt-5 font-label text-xl font-semibold uppercase leading-6 tracking-[0.2em] text-white md:mt-7">
        {{ esc_html($kicker) }}
      </p>

      <div class="mt-8 flex justify-center">
        @include('components.button', [
            'label' => $ctaLabel,
            'href' => $ctaUrl,
            'size' => 'large',
        ])
      </div>
    </div>
  </section>
@endsection
