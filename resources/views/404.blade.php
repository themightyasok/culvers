@extends('layouts.app')

@php
  use App\Helpers\Image;

  /**
   * Culver Square 404 — single hero band that mirrors the homepage hero
   * visual idiom (Glowleaf 6px inset keyline · soft-light rotated decorative
   * square · flat 20 % black scrim · centred Canela headline + Commuters
   * kicker + Glowleaf CTA).
   *
   * Figma frame `51:7648` (1440 × 855) — body is `bg-light-cream` so the
   * footer reads against the same surface.
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
      __('This wasn\u2019t on the map.', 'culvers')
  );
  $kicker = (string) apply_filters(
      'culvers_404_kicker',
      __('Let\u2019s get you back on track', 'culvers')
  );
  $ctaLabel = (string) apply_filters('culvers_404_cta_label', __('Return to homepage', 'culvers'));
  $ctaUrl = (string) apply_filters('culvers_404_cta_url', home_url('/'));
@endphp

@section('content')
  <section
    class="four-oh-four hero-slider--viewport relative isolate flex min-h-[855px] w-full items-center justify-center overflow-hidden bg-deep-moss text-white"
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

    <div class="relative z-10 mx-auto flex w-full max-w-3xl flex-col items-center px-6 pb-16 pt-[length:var(--site-header-offset,11.25rem)] text-center">
      <h1
        id="four-oh-four-heading"
        class="font-heading text-6xl leading-none text-brand-500 sm:text-7xl md:text-8xl lg:text-9xl">
        {{ $headline }}
      </h1>

      <p class="mt-7 font-label text-xl font-semibold uppercase leading-6 tracking-[0.2em] text-white">
        {{ $kicker }}
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
