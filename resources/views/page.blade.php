@extends('layouts.app')

@section('content')
  @while(have_posts())
    @php
      the_post();
      $rows = get_field('components');
      $hasFlexible = is_array($rows) && count($rows) > 0;
    @endphp

    {{--
      Pages built from the flexible component stack manage their own heading
      (typically `image_hero` or `section_header` as the first row), so we
      suppress the auto-generated page-title H1 to avoid duplicate H1s.
      Default WP pages with classic post_content keep the auto-title.
    --}}
    @if (! $hasFlexible)
      @include('partials.page-header')
    @endif

    @include('partials.flexible-components', ['field_name' => 'components'])

    @if (! $hasFlexible)
      @include('partials.content-page')
    @endif
  @endwhile
@endsection
