@extends('layouts.app')

@section('content')
  @while(have_posts())
    @php the_post(); @endphp
    @include('partials.page-header')

    @php
      $existing = get_field('components');
      $existing = is_array($existing) ? $existing : [];
      if ($existing === []) {
          $raw_components_override = \App\Helpers\HomepageFlexibleDefaults::fullStack();
      } else {
          $raw_components_override = $existing;
          $hasThreeCard = false;
          foreach ($existing as $row) {
              if (is_array($row) && ($row['acf_fc_layout'] ?? '') === 'three_card_block') {
                  $hasThreeCard = true;
                  break;
              }
          }
          if (! $hasThreeCard) {
              array_unshift($raw_components_override, \App\Helpers\ThreeCardBlock::homepageFeaturedFlexibleRow());
          }
      }
    @endphp

    @include('partials.flexible-components', [
        'field_name' => 'components',
        'raw_components_override' => $raw_components_override,
    ])

    @php
      $hasFlexibleOnPage = isset($raw_components_override) && is_array($raw_components_override)
          && count($raw_components_override) > 0;
    @endphp
    @if (! $hasFlexibleOnPage)
      @include('partials.content-page')
    @endif
  @endwhile
@endsection
