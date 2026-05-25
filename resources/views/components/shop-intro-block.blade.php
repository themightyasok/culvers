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
    class="shop-intro-block {{ esc_attr($root) }} text-deep-moss"
    data-component-root
    data-shop-intro-block>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      {{-- Figma 51:6154 / 51:6679 (shop + food singles): intro at y=746 after 646px hero
           → 100px top; split at y≈1016 with 174px copy → 96px below (grid gap-y-24).
           Mobile 51:8886: 90px between intro copy and next band. Bottom padding stays 0
           so we do not stack inner pb + grid gap. --}}
      <div class="shop-intro-block__column mx-auto max-w-[886px] pb-0 pt-[90px] text-center lg:pt-[100px]">
        <div
          class="shop-intro-block__body max-w-none font-sans text-xl font-light [&_p:first-child]:mt-0 [&_p:last-child]:mb-0 [&_p+p]:mt-[1.25em] [&_strong]:font-medium {{ esc_attr($tone) }}">
          {!! $body !!}
        </div>

        @if($showCta)
          <div class="shop-intro-block__cta {{ Component::sectionBodyToCtaGapClasses('flex justify-center') }}">
            @include('components.button', [
                'label' => $ctaLabel,
                'href' => $ctaUrl,
                'attributes' => str_starts_with($ctaUrl, 'http')
                    ? ['target' => '_blank', 'rel' => 'noopener noreferrer']
                    : [],
            ])
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
