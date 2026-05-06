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

  $hasSerifLines = $kicker !== '' || $headline !== '';
  $hasCopy = $hasSerifLines || $bodyPlain !== '';
@endphp

@if($hasCopy && $imgUrl !== '')
  {{--
    Figma — Shopping Individual Page split band (≈51:6191–51:6194).
    Left: Desktop/Titles/H2-equivalent glowleaf serif lines @64px / lh 1.2 + Large body @20px / lh 1.3 Book white.
    Full bleed inner radius 10px; band height reference ~597px desktop.
  --}}
  <section
    class="{{ esc_attr(trim($grid . ' ' . $padding)) }} text-deep-moss"
    data-component-root
    data-shop-split-highlight>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      <div
        class="overflow-hidden rounded-[10px] bg-faded-olive shadow-sm lg:flex lg:min-h-[597px]">
        <div
          class="flex flex-[1.5] flex-col items-center justify-center gap-6 px-8 py-12 text-center lg:w-[55%] lg:flex-none lg:gap-8 lg:px-10 xl:px-14 xl:py-12">
          @if($hasSerifLines)
            <div class="flex max-w-[34.625rem] flex-col gap-0">
              {{-- Canela 64px / lh 1.2 → theme token text-5xl leading-[1.2] --}}
              @if($kicker !== '')
                <p class="font-heading text-5xl leading-[1.2] text-brand-500">
                  {{ esc_html($kicker) }}
                </p>
              @endif
              @if($headline !== '')
                <p class="font-heading text-5xl leading-[1.2] text-brand-500 {{ $kicker !== '' ? '-mt-1' : '' }}">
                  {{ esc_html($headline) }}
                </p>
              @endif
            </div>
          @endif

          @if($bodyPlain !== '')
            <div
              class="shop-split-highlight__body max-w-[34.625rem] font-sans text-lg font-light leading-[1.3] text-white [&_a]:text-brand-500 [&_a]:underline [&_a]:decoration-brand-500 [&_a]:underline-offset-4 [&_li]:marker:text-brand-500 [&_p+p]:mt-4 [&_strong]:font-medium [&_strong]:text-white [&_ul]:my-4 [&_ul]:inline-block [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:text-left">
              {!! $bodyHtml !!}
            </div>
          @endif

          @if($showCta)
            <div class="pt-2">
              <a class="btn btn-primary" href="{{ esc_url($ctaUrl) }}">{{ esc_html($ctaLabel) }}</a>
            </div>
          @endif
        </div>

        <div class="relative min-h-[280px] flex-1 lg:min-h-0 lg:w-[45%] lg:flex-none">
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
