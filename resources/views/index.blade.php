@extends('layouts.app')

@section('content')
  @include('partials.page-header')

  @if (! have_posts())
    <p class="mx-auto max-w-3xl px-4 text-text-muted">{{ __('No posts matched your criteria.', 'culvers') }}</p>
    {!! get_search_form(false) !!}
  @endif

  @while(have_posts())
    @php the_post(); @endphp
    <article class="mx-auto max-w-3xl px-4 py-8">
      <h2 class="font-heading text-4xl font-semibold text-text">
        <a class="hover:text-brand-600" href="{{ get_permalink() }}">{{ get_the_title() }}</a>
      </h2>
      <div class="prose prose-invert prose-zinc mt-4">
        {!! get_the_excerpt() !!}
      </div>
    </article>
  @endwhile

  {!! get_the_posts_navigation() !!}
@endsection
