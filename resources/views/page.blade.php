@extends('layouts.app')

@section('content')
  @while(have_posts())
    @php the_post(); @endphp
    @include('partials.page-header')
    @include('partials.flexible-components', ['field_name' => 'components'])
    @php
      $rows = get_field('components');
      $hasFlexible = is_array($rows) && count($rows) > 0;
    @endphp
    @if (! $hasFlexible)
      @include('partials.content-page')
    @endif
  @endwhile
@endsection
