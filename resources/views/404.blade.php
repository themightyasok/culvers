@extends('layouts.app')

@section('content')
  @include('partials.page-header')

  <p class="mx-auto max-w-3xl px-4 text-lg text-zinc-300">
    {{ __('That page could not be found.', 'culvers') }}
  </p>

  <div class="mx-auto max-w-3xl px-4 pt-6">
    {!! get_search_form(false) !!}
  </div>
@endsection
