@extends('layouts.app')

@section('content')
  @while(have_posts())
    @php the_post(); @endphp
    @php
      $policy = \App\Legal\PolicyPages::layoutDataForPost(get_queried_object());
    @endphp
    @if(is_array($policy))
      @include('partials.policy-page', $policy)
    @else
      @include('partials.page-header')
      @include('partials.content-page')
    @endif
  @endwhile
@endsection
