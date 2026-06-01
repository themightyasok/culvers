@extends('layouts.app')

@section('content')
  @php
    global $wp_query;
    $found_events = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;

    $eventsArchiveHero = \App\Directory\ArchiveHeroComponent::fromOptions(\App\Directory\EventArchiveFields::FIELD_PREFIX);
    /** @var array<string, mixed> $eventsArchiveHero */
    $eventsArchiveHero = apply_filters('culvers_events_archive_hero_component', $eventsArchiveHero);
    if (! is_array($eventsArchiveHero)) {
        $eventsArchiveHero = [];
    }

    $introRaw = function_exists('get_field') ? get_field('events_archive_intro_copy', 'option') : '';
    $introHtml = is_string($introRaw) ? trim($introRaw) : '';
    if ($introHtml === '') {
        $introHtml = __(
            'Workshops, performances, family days and seasonal moments — see what’s coming up at Culver Square.',
            'culvers'
        );
    }
  @endphp

  @include('components.image-hero', ['component' => $eventsArchiveHero])

  @include('partials.directory-archive-chronological-body', [
      'introHtml' => $introHtml,
      'foundCount' => $found_events,
      'emptyMessage' => __('Nothing on the calendar yet — check back soon, or sign up to the newsletter to be the first to hear.', 'culvers'),
      'cardPartial' => 'partials.directory-event-card',
  ])
@endsection
