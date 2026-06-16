@extends('layouts.app')

@section('content')
  @php
    global $wp_query;
    $found_shops = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;
    $shop_categories = get_terms([
        'taxonomy' => 'culvers_shop_category',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);
    $shop_types = get_terms([
        'taxonomy' => 'culvers_shop_type',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);
    if ($shop_categories instanceof \WP_Error) {
        $shop_categories = [];
    }
    if ($shop_types instanceof \WP_Error) {
        $shop_types = [];
    }

    $shop_category_options = \App\Directory\DirectoryFilterOptions::fromFigmaOrder(
        \App\Directory\DirectoryFilterDefinitions::shopCategories(),
        $shop_categories
    );
    $shop_type_options = \App\Directory\DirectoryFilterOptions::fromFigmaOrder(
        \App\Directory\DirectoryFilterDefinitions::shopRetailerTypes(),
        $shop_types
    );

    $shopsArchiveHero = \App\Directory\ArchiveHeroComponent::fromOptions(\App\Directory\ShopArchiveFields::FIELD_PREFIX);
    /** @var array<string, mixed> $shopsArchiveHero */
    $shopsArchiveHero = apply_filters('culvers_shops_archive_hero_component', $shopsArchiveHero);
    if (! is_array($shopsArchiveHero)) {
        $shopsArchiveHero = [];
    }

    $introRaw = function_exists('get_field') ? get_field('shops_archive_intro_copy', 'option') : '';
    $introHtml = is_string($introRaw) ? trim($introRaw) : '';
    if ($introHtml === '') {
        $introHtml = __(
            'At Culver Square, enjoy a variety of shops, dining, and entertainment all in one place. From fashion and unique gifts to tasty meals, there\'s something for everyone. Join us and explore!',
            'culvers'
        );
    }
  @endphp

  @include('components.image-hero', ['component' => $shopsArchiveHero])

  @include('partials.directory-archive-filter-body', [
      'introHtml' => $introHtml,
      'filtersRegionLabel' => __('Directory filters', 'culvers'),
      'filterToggleId' => 'directory-filter-toggle',
      'filtersControlsId' => 'directory-archive-filters',
      'foundCount' => $found_shops,
      'emptyMessage' => __('No shops published yet. Add shops under Shops → Add New in the admin.', 'culvers'),
      'cardPartial' => 'partials.directory-shop-card',
      'filterGroups' => [
          [
              'label' => __('Category', 'culvers'),
              'aria_label' => __('Shop category', 'culvers'),
              'panel_id' => 'directory-category-panel',
              'state_var' => 'categorySlug',
              'toggle_var' => 'categoryOpen',
              'setter' => 'setCategory',
              'options' => $shop_category_options,
          ],
          [
              'label' => __('Retailer type', 'culvers'),
              'aria_label' => __('Retailer type', 'culvers'),
              'panel_id' => 'directory-retailer-panel',
              'state_var' => 'typeSlug',
              'toggle_var' => 'retailerOpen',
              'setter' => 'setType',
              'options' => $shop_type_options,
          ],
      ],
  ])

  @php $shopsArchiveStories = \App\Directory\ArchiveStoriesThreeCard::forShop(); @endphp
  @if ($shopsArchiveStories !== null)
    @include('components.three-card-block', ['component' => $shopsArchiveStories])
  @endif
@endsection
