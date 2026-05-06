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
      <div class="mx-auto max-w-[46rem] text-center">
        <div
          class="prose prose-lg max-w-none text-deep-moss prose-headings:font-heading prose-headings:text-deep-moss prose-p:font-sans prose-p:text-deep-moss prose-p:leading-relaxed prose-li:text-deep-moss {{ esc_attr($tone) }}">
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
