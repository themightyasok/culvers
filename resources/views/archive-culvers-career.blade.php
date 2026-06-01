@extends('layouts.app')

@section('content')
  @php
    global $wp_query;
    $found_roles = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;

    $departments = get_terms([
        'taxonomy' => 'culvers_career_department',
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);
    if ($departments instanceof \WP_Error) {
        $departments = [];
    }

    $contract_types = [];
    if ($wp_query->have_posts()) {
        foreach ($wp_query->posts as $post) {
            if (! ($post instanceof \WP_Post)) {
                continue;
            }
            $value = function_exists('get_field') ? get_field('career_employment_type', $post->ID) : '';
            $value = is_string($value) ? trim($value) : '';
            if ($value === '') {
                continue;
            }
            $slug = sanitize_title($value);
            if ($slug === '' || isset($contract_types[$slug])) {
                continue;
            }
            $contract_types[$slug] = $value;
        }
        ksort($contract_types);
    }

    $career_department_options = array_map(
        static fn (\WP_Term $term): array => ['slug' => (string) $term->slug, 'name' => (string) $term->name],
        $departments
    );
    $career_contract_options = [];
    foreach ($contract_types as $slug => $label) {
        $career_contract_options[] = ['slug' => (string) $slug, 'name' => (string) $label];
    }

    $careersArchiveHero = \App\Directory\ArchiveHeroComponent::fromOptions(\App\Directory\CareerArchiveFields::FIELD_PREFIX);
    /** @var array<string, mixed> $careersArchiveHero */
    $careersArchiveHero = apply_filters('culvers_careers_archive_hero_component', $careersArchiveHero);
    if (! is_array($careersArchiveHero)) {
        $careersArchiveHero = [];
    }

    $introRaw = function_exists('get_field') ? get_field('careers_archive_intro_copy', 'option') : '';
    $introHtml = is_string($introRaw) ? trim($introRaw) : '';
    if ($introHtml === '') {
        $introHtml = __(
            'Roles in the centre team and across our retail, hospitality and operations partners. Browse open positions below.',
            'culvers'
        );
    }

    $filterGroups = [
        [
            'label' => __('Department', 'culvers'),
            'aria_label' => __('Department', 'culvers'),
            'panel_id' => 'directory-category-panel-careers',
            'state_var' => 'categorySlug',
            'toggle_var' => 'categoryOpen',
            'setter' => 'setCategory',
            'options' => $career_department_options,
        ],
    ];
    if ($career_contract_options !== []) {
        $filterGroups[] = [
            'label' => __('Contract type', 'culvers'),
            'aria_label' => __('Contract type', 'culvers'),
            'panel_id' => 'directory-type-panel-careers',
            'state_var' => 'typeSlug',
            'toggle_var' => 'retailerOpen',
            'setter' => 'setType',
            'options' => $career_contract_options,
        ];
    }
  @endphp

  @include('components.image-hero', ['component' => $careersArchiveHero])

  @include('partials.directory-archive-filter-body', [
      'introHtml' => $introHtml,
      'filtersRegionLabel' => __('Careers filters', 'culvers'),
      'filterToggleId' => 'directory-filter-toggle-careers',
      'filtersControlsId' => 'directory-archive-filters-careers',
      'foundCount' => $found_roles,
      'emptyMessage' => __('No roles open right now — check back soon, or follow us on socials for the latest.', 'culvers'),
      'cardPartial' => 'partials.directory-career-card',
      'filterGroups' => $filterGroups,
  ])

  @php $careersContactCta = \App\Directory\CareerArchiveContactCta::componentOrNull(); @endphp
  @if ($careersContactCta !== null)
    @include('components.info-block', ['component' => $careersContactCta])
  @endif
@endsection
