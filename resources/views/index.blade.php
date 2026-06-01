@extends('layouts.app')

@section('content')
  @include('partials.page-header')

  @if (! have_posts())
    <p class="mx-auto max-w-3xl px-4 text-faded-olive">{{ __('No posts matched your criteria.', 'culvers') }}</p>
    {!! get_search_form(false) !!}
  @endif

  @while(have_posts())
    @php the_post(); @endphp
    <article class="mx-auto max-w-3xl px-4 py-8">
      <h2 class="font-heading text-4xl font-normal text-deep-moss">
        <a class="hover:text-brand-600" href="{{ get_permalink() }}">{{ get_the_title() }}</a>
      </h2>
      <div class="prose prose-lg mt-4 max-w-none text-deep-moss prose-headings:text-deep-moss prose-p:text-deep-moss prose-li:text-deep-moss rt-link-prose">
        {!! get_the_excerpt() !!}
      </div>
    </article>
  @endwhile

  {!! get_the_posts_navigation() !!}
@endsection
