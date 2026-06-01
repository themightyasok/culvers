@extends('layouts.app')

@section('content')
  @while(have_posts())
    @php
      the_post();
      $hasFlexible = \App\Helpers\FlexibleComponents::hasRows((int) get_the_ID());
    @endphp

    @if (! is_front_page() && ! $hasFlexible)
      @include('partials.page-header')
    @endif

    @include('partials.flexible-components', ['field_name' => 'components'])
  @endwhile
@endsection
