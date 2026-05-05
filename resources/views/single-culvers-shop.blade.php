@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php the_post(); @endphp
    <article class="mx-auto max-w-[720px] px-4 py-16 md:px-[46px] md:py-24">
      <h1 class="font-heading text-4xl text-deep-moss md:text-5xl">{{ get_the_title() }}</h1>
      <p class="mt-6 font-sans text-lg text-faded-olive">
        {{ __('Individual shop detail layout (fixed components) will replace this placeholder.', 'culvers') }}
      </p>
      <p class="mt-8">
        <a
          class="font-sans font-semibold text-faded-olive underline-offset-4 hover:text-deep-moss hover:underline"
          href="{{ esc_url(get_post_type_archive_link('culvers_shop')) }}">
          {{ __('← Back to shop directory', 'culvers') }}
        </a>
      </p>
    </article>
  @endwhile
@endsection
