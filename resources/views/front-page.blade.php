@extends('layouts.app')

@section('content')
  @while(have_posts())
    @php
      the_post();
      $existing = get_field('components');
      $existing = is_array($existing) ? $existing : [];
      $hasFlexibleOnPage = count($existing) > 0;
    @endphp

    {{--
      Same rule as page.blade.php — flexible-content stacks supply their own
      H1 via `hero_slider` / `image_hero`, so the auto page-title H1 is only
      shown when the editor falls back to classic post_content.
    --}}
    @if (! $hasFlexibleOnPage)
      @include('partials.page-header')
    @endif

    @include('partials.flexible-components', [
        'field_name' => 'components',
        'raw_components_override' => $existing,
    ])

    @if (! $hasFlexibleOnPage)
      @include('partials.content-page')
    @endif
  @endwhile
@endsection
