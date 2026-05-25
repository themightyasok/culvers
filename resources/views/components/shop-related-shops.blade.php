@php
  use App\Directory\RelatedShopPostIds;
  use App\Helpers\Component;
  use App\Helpers\LayoutShell;

  /**
   * Shop — related shops. Up to four picks rendered with the directory card
   * partial (same as /shops/ archive) so subtitles use {@see
   * App\Directory\OpeningHoursCardLine} for today's hours from each shop's
   * `opening_hours` flexible row, then `opening_hours_summary` as fallback.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $headingTag = Component::headingTagFromComponent($c, 'shops_related_heading_level', 2);

  $heading = trim((string) ($c['shops_related_heading'] ?? __('More shops you might enjoy', 'culvers')));
  $relatedHeadingSpacing = Component::sectionHeadingSpacingClasses(
      Component::resolveSectionHeadingSpacing($c, 'shops_related_heading_spacing', 'carousel')
  );
  $viewUrl = trim((string) ($c['shops_related_view_all_url'] ?? ''));
  $viewLabel = trim((string) ($c['shops_related_view_all_label'] ?? __('View all', 'culvers')));

  /** @var list<int> $shopIds */
  $shopIds = RelatedShopPostIds::randomPublished((int) get_the_ID(), 4);

@endphp

@if($shopIds !== [])
  <section
    class="shop-related-shops {{ esc_attr($root) }} text-deep-moss"
    data-component-root
    data-shop-related-shops
    x-data="shopRelatedShops()">
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      @if($heading !== '')
        {{-- Section H2: 64px desktop / 48px mobile (Component::sectionHeadingClasses). --}}
        <{{ $headingTag }} class="shop-related-shops__heading {{ Component::sectionIntroHeadingClasses('text-faded-olive', 'mx-auto max-w-[52rem] text-center ' . $relatedHeadingSpacing) }}">
          {{ esc_html($heading) }}
        </{{ $headingTag }}>
      @endif

      <div
        class="shop-related-shops__splide splide culvers-splide-dots culvers-splide-dots--pagination-mt-7 block sm:hidden"
        x-ref="splideRoot"
        data-splide-manual
        role="region"
        aria-label="{{ esc_attr($heading !== '' ? $heading : __('Related shops', 'culvers')) }}">
        <div class="splide__track overflow-visible">
          <ul class="splide__list">
            @foreach($shopIds as $shopId)
              <li class="splide__slide">
                @include('partials.directory-shop-card', ['directory_card_post_id' => $shopId])
              </li>
            @endforeach
          </ul>
        </div>
      </div>

      <div class="shop-related-shops__grid directory-card-grid directory-card-grid--four-up hidden sm:grid">
        @foreach($shopIds as $shopId)
          @include('partials.directory-shop-card', ['directory_card_post_id' => $shopId])
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
      'message' => __('Pick related shops for this block.', 'culvers'),
  ])
@endif
