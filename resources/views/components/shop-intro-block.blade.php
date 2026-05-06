@php
  use App\Helpers\LayoutShell;
  use App\Helpers\Padding;
  use App\Helpers\TailwindColors;

  $c = is_array($component ?? null) ? $component : [];
  $padding = Padding::getClasses($c);
  $grid = $c['_grid_classes'] ?? '';
  $tone = TailwindColors::sanitizeBodyTextTone($c['body_text_tone'] ?? TailwindColors::DEFAULT_LIGHT_BAND_BODY_TEXT_TONE);

  $body = (string) ($c['intro_body'] ?? '');
  $bodyPlain = trim(wp_strip_all_tags($body));
  $ctaLabel = trim((string) ($c['intro_cta_label'] ?? ''));
  $ctaUrl = trim((string) ($c['intro_cta_url'] ?? ''));
  $showCta = $ctaLabel !== '' && $ctaUrl !== '';
@endphp

@if($bodyPlain !== '')
  <section
    class="{{ esc_attr(trim($grid . ' ' . $padding)) }} bg-white text-deep-moss"
    data-component-root
    data-shop-intro-block>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      {{-- Figma shop intro copy: Desktop/Body Copy/Large body copy — 20px / lh 1.3 Book (≈886px column). --}}
      <div class="mx-auto max-w-[886px] text-center">
        <div
          class="max-w-none font-sans text-lg font-light leading-[1.3] text-deep-moss [&_p+p]:mt-[1.25em] [&_strong]:font-medium {{ esc_attr($tone) }}">
          {!! $body !!}
        </div>

        @if($showCta)
          <div class="mt-10 flex justify-center md:mt-12">
            <a class="btn btn-primary" href="{{ esc_url($ctaUrl) }}">{{ esc_html($ctaLabel) }}</a>
          </div>
        @endif
      </div>
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => trim($grid . ' ' . $padding),
      'message' => __('Add intro copy to this block.', 'culvers'),
  ])
@endif
