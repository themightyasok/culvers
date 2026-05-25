@php
  use App\Customizer\FooterCustomizer;
  use App\Helpers\Component;
  use App\Helpers\SocialShare;

  /**
   * Social share — Figma 51:6411: centred Canela H2 + icon row (Instagram, Facebook, WhatsApp).
   * Profile URLs come from Customizer; WhatsApp opens a share intent for the current page.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $heading = trim((string) ($c['share_heading'] ?? ''));
  if ($heading === '') {
      $heading = __('Share with a friend', 'culvers');
  }
  $headingTag = Component::headingTagFromComponent($c, 'share_heading_level', 2);

  $instagramUrl = FooterCustomizer::instagramUrl();
  $facebookUrl = FooterCustomizer::facebookUrl();
  $pageUrl = is_singular() ? (string) get_permalink() : '';
  $pageTitle = is_singular() ? html_entity_decode((string) get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
  $whatsappUrl = $pageUrl !== ''
      ? SocialShare::whatsappShareUrl($pageUrl, $pageTitle)
      : '';

  $links = [];
  if (SocialShare::isRenderableUrl($instagramUrl)) {
      $links[] = [
          'label' => __('Instagram', 'culvers'),
          'url' => $instagramUrl,
          'variant' => 'instagram',
          'icon_class' => 'size-[14px] shrink-0 text-dustleaf',
      ];
  }
  if (SocialShare::isRenderableUrl($facebookUrl)) {
      $links[] = [
          'label' => __('Facebook', 'culvers'),
          'url' => $facebookUrl,
          'variant' => 'facebook',
          'icon_class' => 'size-[15px] shrink-0 text-dustleaf',
      ];
  }
  if ($whatsappUrl !== '') {
      $links[] = [
          'label' => __('Whatsapp', 'culvers'),
          'url' => $whatsappUrl,
          'variant' => 'whatsapp',
          'icon_class' => 'size-[15px] shrink-0 text-dustleaf',
          'external' => true,
      ];
  }
@endphp

@if($links === [])
  @if(current_user_can('edit_posts'))
    @include('partials.component-editor-placeholder', [
        'wrapperClasses' => $root,
        'message' => __('Social share — add Instagram or Facebook URLs in Customizer → Footer, or view on a singular page for WhatsApp.', 'culvers'),
    ])
  @endif
@else
  <section
    class="social-share {{ esc_attr($root) }} text-deep-moss"
    data-component-root
    data-social-share>
    <div class="mx-auto w-full max-w-8xl px-3 sm:px-4 md:px-5 lg:px-6">
      <div class="section-intro-stack mx-auto flex max-w-[50.125rem] flex-col items-center text-center">
        <{{ $headingTag }} class="{{ Component::sectionIntroHeadingClasses('text-faded-olive') }}">
          {{ esc_html($heading) }}
        </{{ $headingTag }}>

        <ul class="section-intro-stack__content social-share__list flex flex-wrap items-center justify-center gap-x-[34px] gap-y-3">
          @foreach($links as $link)
            <li>
              <a
                href="{{ esc_url($link['url']) }}"
                class="social-share__link inline-flex items-center gap-1.5 font-label text-xs font-semibold uppercase tracking-widest text-dustleaf transition-colors hover:text-deep-moss focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-glowleaf focus-visible:ring-offset-2 focus-visible:ring-offset-light-cream"
                @if(! empty($link['external']))
                  target="_blank"
                  rel="noopener noreferrer"
                @endif>
                @include('partials.figma-social-icon', [
                    'social_icon_variant' => $link['variant'],
                    'social_icon_class' => $link['icon_class'],
                ])
                <span>{{ esc_html($link['label']) }}</span>
              </a>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  </section>
@endif
