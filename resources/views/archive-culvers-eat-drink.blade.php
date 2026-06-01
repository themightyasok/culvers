@extends('layouts.app')

@section('content')
  @php
    global $wp_query;
    $found_venues = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;
    \App\Directory\EatDrinkTaxonomySeeder::syncNow();

    $eat_drink_types = get_terms([
        'taxonomy' => 'culvers_eat_drink_type',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);
    if ($eat_drink_types instanceof \WP_Error) {
        $eat_drink_types = [];
    }

    $eat_drink_category_options = \App\Directory\DirectoryFilterOptions::fromFigmaOrder(
        \App\Directory\DirectoryFilterDefinitions::eatDrinkCategories(),
        $eat_drink_types
    );

    $eatDrinkArchiveHero = \App\Directory\ArchiveHeroComponent::fromOptions(\App\Directory\EatDrinkArchiveFields::FIELD_PREFIX);
    /** @var array<string, mixed> $eatDrinkArchiveHero */
    $eatDrinkArchiveHero = apply_filters('culvers_eat_drink_archive_hero_component', $eatDrinkArchiveHero);
    if (! is_array($eatDrinkArchiveHero)) {
        $eatDrinkArchiveHero = [];
    }

    $introRaw = function_exists('get_field') ? get_field('eat_drink_archive_intro_copy', 'option') : '';
    $introHtml = is_string($introRaw) ? trim($introRaw) : '';
    if ($introHtml === '') {
        $introHtml = __(
            'From quick coffee stops to long, lazy lunches — find your next favourite spot in the centre.',
            'culvers'
        );
    }
  @endphp

  @include('components.image-hero', ['component' => $eatDrinkArchiveHero])

  @include('partials.directory-archive-filter-body', [
      'introHtml' => $introHtml,
      'filtersRegionLabel' => __('Eat & Drink filters', 'culvers'),
      'filterToggleId' => 'directory-filter-toggle-eat-drink',
      'filtersControlsId' => 'directory-archive-filters-eat-drink',
      'foundCount' => $found_venues,
      'emptyMessage' => __('No Eat & Drink venues published yet. Add venues under Eat & Drink → Add New in the admin.', 'culvers'),
      'cardPartial' => 'partials.directory-eat-drink-card',
      'filterGroups' => [
          [
              'label' => __('Category', 'culvers'),
              'aria_label' => __('Category', 'culvers'),
              'panel_id' => 'directory-category-panel-eat-drink',
              'state_var' => 'typeSlug',
              'toggle_var' => 'categoryOpen',
              'setter' => 'setType',
              'options' => $eat_drink_category_options,
          ],
      ],
  ])

  @php $eatDrinkArchiveStories = \App\Directory\EatDrinkArchiveThreeCard::componentOrNull(); @endphp
  @if ($eatDrinkArchiveStories !== null)
    @include('components.three-card-block', ['component' => $eatDrinkArchiveStories])
  @endif
@endsection
