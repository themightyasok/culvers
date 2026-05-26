@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php the_post(); @endphp
    @include('partials.flexible-components')
  @endwhile

  <section class="pb-16 md:pb-28">
    <div class="px-4 md:px-12">
      <div class="mx-auto w-full max-w-8xl">
        @include('partials.whats-on-return-cta')
      </div>
    </div>
  </section>
@endsection
