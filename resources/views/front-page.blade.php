@extends('layouts.app')

@section('content')
  @while(have_posts())
    @php the_post(); @endphp
    @include('partials.page-header')

    @php
      $existing = get_field('components');
      $existing = is_array($existing) ? $existing : [];
    @endphp

    @include('partials.flexible-components', [
        'field_name' => 'components',
        'raw_components_override' => $existing,
    ])

    @php
      $hasFlexibleOnPage = count($existing) > 0;
    @endphp
    @if (! $hasFlexibleOnPage)
      @include('partials.content-page')
    @endif
  @endwhile
@endsection
