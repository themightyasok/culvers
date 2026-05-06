@php
  use App\Helpers\LayoutShell;
  use App\Helpers\Padding;

  $c = is_array($component ?? null) ? $component : [];
  $padding = Padding::getClasses($c);
  $grid = $c['_grid_classes'] ?? '';

  $heading = trim((string) ($c['related_heading'] ?? __('More shops you might enjoy', 'culvers')));
  $level = isset($c['related_heading_level']) ? (int) $c['related_heading_level'] : 2;
  if ($level < 2 || $level > 4) {
      $level = 2;
  }
  $headingTag = 'h' . $level;

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
    class="{{ esc_attr(trim($grid . ' ' . $padding)) }} bg-white text-deep-moss"
    data-component-root
    data-shop-related-shops>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      @if($heading !== '')
        <{{ $headingTag }} class="mx-auto mb-10 max-w-[52rem] text-center font-heading text-[58px] leading-[1.15] tracking-tight text-faded-olive md:mb-14">
          {{ esc_html($heading) }}
        </{{ $headingTag }}>
      @endif

      <div class="mx-auto grid max-w-[1272px] grid-cols-1 gap-7 sm:grid-cols-2 lg:grid-cols-4">
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
      'wrapperClasses' => trim($grid . ' ' . $padding),
      'message' => __('Pick related shops for this block.', 'culvers'),
  ])
@endif
