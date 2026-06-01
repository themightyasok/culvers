@extends('layouts.app')

@section('content')
  @php
    global $wp_query;
    $found_news = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;

    $newsArchiveHero = \App\Directory\ArchiveHeroComponent::fromOptions(\App\Directory\NewsArchiveFields::FIELD_PREFIX);
    /** @var array<string, mixed> $newsArchiveHero */
    $newsArchiveHero = apply_filters('culvers_news_archive_hero_component', $newsArchiveHero);
    if (! is_array($newsArchiveHero)) {
        $newsArchiveHero = [];
    }

    $introRaw = function_exists('get_field') ? get_field('news_archive_intro_copy', 'option') : '';
    $introHtml = is_string($introRaw) ? trim($introRaw) : '';
    if ($introHtml === '') {
        $introHtml = __(
            'Centre updates, retailer announcements and editorial from the Culver Square team.',
            'culvers'
        );
    }
  @endphp

  @include('components.image-hero', ['component' => $newsArchiveHero])

  @include('partials.directory-archive-chronological-body', [
      'introHtml' => $introHtml,
      'foundCount' => $found_news,
      'emptyMessage' => __('No news articles published yet — check back soon, or sign up to the newsletter to be the first to hear.', 'culvers'),
      'cardPartial' => 'partials.directory-news-card',
  ])
@endsection
