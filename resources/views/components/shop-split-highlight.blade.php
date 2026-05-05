@php
  use App\Helpers\LayoutShell;
  use App\Helpers\Padding;

  $c = is_array($component ?? null) ? $component : [];
  $padding = Padding::getClasses($c);
  $grid = $c['_grid_classes'] ?? '';

  $kicker = trim((string) ($c['split_kicker'] ?? ''));
  $headline = trim((string) ($c['split_headline'] ?? ''));
  $bodyHtml = trim((string) ($c['split_body'] ?? ''));
  $bodyPlain = trim(wp_strip_all_tags($bodyHtml));
  $ctaLabel = trim((string) ($c['split_cta_label'] ?? ''));
  $ctaUrl = trim((string) ($c['split_cta_url'] ?? ''));
  $showCta = $ctaLabel !== '' && $ctaUrl !== '';

  $img = isset($c['split_image']) && is_array($c['split_image']) ? $c['split_image'] : [];
  $imgUrl = isset($img['url']) ? trim((string) $img['url']) : '';
  $imgAlt = isset($img['alt']) ? trim((string) $img['alt']) : '';

  $hasCopy = $kicker !== '' || $headline !== '' || $bodyPlain !== '';
@endphp

@if($hasCopy && $imgUrl !== '')
  <section
    class="{{ esc_attr(trim($grid . ' ' . $padding)) }} text-deep-moss"
    data-component-root
    data-shop-split-highlight>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      <div class="overflow-hidden rounded-[22px] bg-deep-moss shadow-sm lg:flex lg:min-h-[420px]">
        <div class="flex flex-[1.5] flex-col justify-center gap-6 px-8 py-12 lg:w-[60%] lg:flex-none lg:px-12 xl:px-16 xl:py-14">
          @if($kicker !== '')
            <p class="font-heading text-[clamp(1.85rem,4vw,2.75rem)] leading-[1.12] text-brand-500">
              {{ esc_html($kicker) }}
            </p>
          @endif
          @if($headline !== '')
            <p class="font-heading text-[clamp(2rem,4.5vw,3rem)] leading-[1.08] text-brand-500 lg:-mt-2">
              {{ esc_html($headline) }}
            </p>
          @endif

          @if($bodyPlain !== '')
            <div
              class="shop-split-highlight__body prose prose-lg max-w-none font-sans text-white prose-headings:font-heading prose-headings:text-white prose-p:text-white prose-p:leading-relaxed prose-strong:text-white prose-li:text-white prose-li:marker:text-brand-500 [&_a]:text-brand-500 [&_a]:underline [&_a]:decoration-brand-500 [&_a]:underline-offset-4">
              {!! $bodyHtml !!}
            </div>
          @endif

          @if($showCta)
            <div class="pt-2">
              <a class="btn btn-primary" href="{{ esc_url($ctaUrl) }}">{{ esc_html($ctaLabel) }}</a>
            </div>
          @endif
        </div>

        <div class="relative min-h-[280px] flex-1 lg:min-h-0 lg:w-[40%] lg:flex-none">
          <img
            src="{{ esc_url($imgUrl) }}"
            alt="{{ esc_attr($imgAlt !== '' ? $imgAlt : ($headline !== '' ? $headline : __('Feature image', 'culvers'))) }}"
            class="absolute inset-0 size-full object-cover"
            loading="lazy"
            decoding="async"
            @if(isset($img['width'])) width="{{ (int) $img['width'] }}" @endif
            @if(isset($img['height'])) height="{{ (int) $img['height'] }}" @endif />
        </div>
      </div>
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @php
      $editorHint = '';
      if ($imgUrl === '' && ! $hasCopy) {
          $editorHint = __('Add split copy and a right-column image.', 'culvers');
      } elseif ($imgUrl === '') {
          $editorHint = __('Add a right-column image.', 'culvers');
      } else {
          $editorHint = __('Add a kicker, headline, or body copy.', 'culvers');
      }
  @endphp
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => trim($grid . ' ' . $padding),
      'message' => $editorHint,
  ])
@endif
