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
    class="shop-related-shops {{ esc_attr($root) }} bg-white text-deep-moss"
    data-component-root
    data-shop-related-shops>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      @if($heading !== '')
        <{{ $headingTag }} class="shop-related-shops__heading mx-auto mb-10 max-w-[52rem] text-center font-heading text-6xl tracking-tight text-faded-olive md:mb-14">
          {{ esc_html($heading) }}
        </{{ $headingTag }}>
      @endif

      <div class="shop-related-shops__grid mx-auto grid max-w-7xl grid-cols-1 gap-7 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($shops as $shopPost)
          @include('partials.directory-shop-card', ['directory_card_post_id' => (int) $shopPost->ID])
        @endforeach
      </div>

      @if($viewUrl !== '')
        <div class="mt-12 flex justify-center md:mt-14">
          <a class="btn btn-primary" href="{{ esc_url($viewUrl) }}">{{ esc_html($viewLabel) }}</a>
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
