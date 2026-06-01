@extends('layouts.app')

@section('content')
  @php
    global $wp_query;
    $found_offers = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;

    $offersArchiveHero = \App\Directory\ArchiveHeroComponent::fromOptions(\App\Directory\OfferArchiveFields::FIELD_PREFIX);
    /** @var array<string, mixed> $offersArchiveHero */
    $offersArchiveHero = apply_filters('culvers_offers_archive_hero_component', $offersArchiveHero);
    if (! is_array($offersArchiveHero)) {
        $offersArchiveHero = [];
    }

    $introRaw = function_exists('get_field') ? get_field('offers_archive_intro_copy', 'option') : '';
    $introHtml = is_string($introRaw) ? trim($introRaw) : '';
    if ($introHtml === '') {
        $introHtml = __(
            'Promotions, discounts and brand campaigns from across the centre — pick something for your next visit.',
            'culvers'
        );
    }
  @endphp

  @include('components.image-hero', ['component' => $offersArchiveHero])

  @include('partials.directory-archive-chronological-body', [
      'introHtml' => $introHtml,
      'foundCount' => $found_offers,
      'emptyMessage' => __('No offers running right now — check back soon.', 'culvers'),
      'cardPartial' => 'partials.directory-offer-card',
  ])
@endsection
