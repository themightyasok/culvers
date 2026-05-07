@php
  use App\Helpers\Component;
  use App\Helpers\LayoutShell;

  /**
   * Shop — intro block. Centred large body copy with optional primary CTA on
   * a clean white band. Sits under the image hero in the shop single layout.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $tone = Component::bodyTextTone($c, 'light-band');

  $body = (string) ($c['intro_body'] ?? '');
  $bodyPlain = trim(wp_strip_all_tags($body));
  $ctaLabel = trim((string) ($c['intro_cta_label'] ?? ''));
  $ctaUrl = trim((string) ($c['intro_cta_url'] ?? ''));
  $showCta = $ctaLabel !== '' && $ctaUrl !== '';
@endphp

@if($bodyPlain !== '')
  <section
    class="shop-intro-block {{ esc_attr($root) }} bg-white py-12 text-deep-moss lg:py-16"
    data-component-root
    data-shop-intro-block>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      {{-- Figma shop intro copy: Desktop/Body Copy/Large body copy — 20px / lh 1.3 Book (≈886px column). --}}
      <div class="shop-intro-block__column mx-auto max-w-[886px] text-center">
        <div
          class="shop-intro-block__body max-w-none font-sans text-xl font-light [&_p+p]:mt-[1.25em] [&_strong]:font-medium {{ esc_attr($tone) }}">
          {!! $body !!}
        </div>

        @if($showCta)
          <div class="shop-intro-block__cta mt-10 flex justify-center md:mt-12">
            @include('components.button', ['label' => $ctaLabel, 'href' => $ctaUrl])
          </div>
        @endif
      </div>
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => $root,
      'message' => __('Add intro copy to this block.', 'culvers'),
  ])
@endif
