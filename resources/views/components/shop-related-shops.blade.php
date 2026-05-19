@php
  use App\Helpers\Component;
  use App\Helpers\LayoutShell;

  /**
   * Shop — related shops. Up to four picks rendered with the directory card
   * partial so card styling stays in lockstep with the archive listing.
   * The current shop is filtered out before rendering.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $headingTag = Component::headingTag($c['related_heading_level'] ?? null);

  $heading = trim((string) ($c['related_heading'] ?? __('More shops you might enjoy', 'culvers')));
  $viewUrl = trim((string) ($c['related_view_all_url'] ?? ''));
  $viewLabel = trim((string) ($c['related_view_all_label'] ?? __('View all', 'culvers')));

  $rawPosts = $c['related_shop_posts'] ?? null;
  /** @var array<int, \WP_Post> $picked */
  $picked = [];
  if ($rawPosts instanceof \WP_Post) {
      $picked = [$rawPosts];
  } elseif (is_array($rawPosts)) {
      foreach ($rawPosts as $p) {
          if ($p instanceof \WP_Post) {
              $picked[] = $p;
          }
      }
  }

  $currentId = get_the_ID();
  $shops = [];
  foreach ($picked as $p) {
      if ((int) $p->ID !== (int) $currentId) {
          $shops[] = $p;
      }
      if (count($shops) >= 4) {
          break;
      }
  }

@endphp

@if($shops !== [])
  <section
    class="shop-related-shops {{ esc_attr($root) }} bg-lighter-cream py-12 text-deep-moss lg:py-16"
    data-component-root
    data-shop-related-shops
    x-data="shopRelatedShops()">
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      @if($heading !== '')
        {{-- Section H2: 64px desktop / 48px mobile (Component::sectionHeadingClasses). --}}
        <{{ $headingTag }} class="shop-related-shops__heading {{ Component::sectionHeadingClasses('text-faded-olive', 'mx-auto mb-10 max-w-[52rem] text-center md:mb-14') }}">
          {{ esc_html($heading) }}
        </{{ $headingTag }}>
      @endif

      <div
        class="shop-related-shops__splide splide block sm:hidden"
        x-ref="splideRoot"
        data-splide-manual
        role="region"
        aria-label="{{ esc_attr($heading !== '' ? $heading : __('Related shops', 'culvers')) }}">
        <div class="splide__track overflow-visible">
          <ul class="splide__list">
            @foreach($shops as $shopPost)
              <li class="splide__slide">
                @include('partials.directory-shop-card', ['directory_card_post_id' => (int) $shopPost->ID])
              </li>
            @endforeach
          </ul>
        </div>
      </div>

      <div class="shop-related-shops__grid mx-auto hidden max-w-7xl grid-cols-2 gap-7 sm:grid lg:grid-cols-4">
        @foreach($shops as $shopPost)
          @include('partials.directory-shop-card', ['directory_card_post_id' => (int) $shopPost->ID])
        @endforeach
      </div>

      @if($viewUrl !== '')
        <div class="mt-12 flex justify-center md:mt-14">
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
