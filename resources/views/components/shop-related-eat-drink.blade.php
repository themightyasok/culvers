@php
  use App\Directory\RelatedEatDrinkPostIds;
  use App\Helpers\Component;
  use App\Helpers\LayoutShell;

  /**
   * Eat & drink — related venues. Up to four picks rendered with the directory card
   * partial (same as /eat-drink/ archive).
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $headingTag = Component::headingTagFromComponent($c, 'eat_drink_related_heading_level', 2);

  $heading = trim((string) ($c['eat_drink_related_heading'] ?? __('More flavours to discover', 'culvers')));
  $viewUrl = trim((string) ($c['eat_drink_related_view_all_url'] ?? ''));
  $viewLabel = trim((string) ($c['eat_drink_related_view_all_label'] ?? __('View all', 'culvers')));

  $currentPostId = get_queried_object_id() > 0
      ? get_queried_object_id()
      : (int) get_the_ID();

  /** @var list<int> $venueIds */
  $venueIds = RelatedEatDrinkPostIds::randomPublished($currentPostId, 4);

@endphp

@if($venueIds !== [])
  <section
    class="shop-related-eat-drink {{ esc_attr($root) }} text-deep-moss"
    data-component-root
    data-shop-related-eat-drink
    x-data="shopRelatedShops()">
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      @if($heading !== '')
        <{{ $headingTag }} class="shop-related-eat-drink__heading {{ Component::sectionIntroHeadingClasses('text-faded-olive', 'mx-auto max-w-[52rem] text-center') }}">
          {{ esc_html($heading) }}
        </{{ $headingTag }}>
      @endif

      <div
        class="shop-related-eat-drink__splide splide culvers-splide-dots culvers-splide-dots--pagination-mt-7 block sm:hidden"
        x-ref="splideRoot"
        data-splide-manual
        role="region"
        aria-label="{{ esc_attr($heading !== '' ? $heading : __('Related places to eat & drink', 'culvers')) }}">
        <div class="splide__track overflow-visible">
          <ul class="splide__list">
            @foreach($venueIds as $venueId)
              <li class="splide__slide">
                @include('partials.directory-eat-drink-card', ['directory_card_post_id' => $venueId])
              </li>
            @endforeach
          </ul>
        </div>
      </div>

      <div class="shop-related-eat-drink__grid directory-card-grid directory-card-grid--four-up hidden sm:grid">
        @foreach($venueIds as $venueId)
          @include('partials.directory-eat-drink-card', ['directory_card_post_id' => $venueId])
        @endforeach
      </div>

      @if($viewUrl !== '')
        <div class="{{ Component::sectionBodyToCtaGapClasses('flex justify-center') }}">
          @include('components.button', ['label' => $viewLabel, 'href' => $viewUrl])
        </div>
      @endif
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => $root,
      'message' => __('Pick related eat & drink venues for this block.', 'culvers'),
  ])
@endif
