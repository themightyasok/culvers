@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php the_post(); @endphp
    @include('partials.flexible-components')
  @endwhile

  <section class="pb-[100px]">
    <div class="{{ \App\Helpers\LayoutShell::INNER_MAX_GUTTERED }}">
      @include('partials.whats-on-return-cta')
    </div>
  </section>
@endsection
