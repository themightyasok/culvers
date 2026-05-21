@php
  use App\Helpers\Component;
  use App\Helpers\LayoutShell;

  /**
   * Shop — store details. 2- or 3-column band (Contact | Address | optional
   * Social) inside a narrow readable shell. Phone is auto-linked as `tel:`.
   * Renders the Instagram lockup only when a URL or handle is provided.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $headingTag = Component::headingTagFromComponent($c, 'details_heading_level', 2);

  $sectionHeading = trim((string) ($c['details_heading'] ?? ''));
  if ($sectionHeading === '') {
      $sectionHeading = __('Store Details', 'culvers');
  }

  $contactLabel = __('Contact Number', 'culvers');
  $phone = trim((string) ($c['details_contact_phone'] ?? ''));
  $addressLabel = __('Address', 'culvers');
  $addressRaw = trim((string) ($c['details_address'] ?? ''));
  $addressPlain = trim(wp_strip_all_tags(preg_replace('/<br\s*\/?>/i', ' ', $addressRaw)));
  $addressForDisplay = preg_replace('/\s+/u', ' ', $addressPlain);
  $socialLabel = __('Social Media', 'culvers');
  $igUrl = trim((string) ($c['details_instagram_url'] ?? ''));
  $igHandle = trim((string) ($c['details_instagram_handle'] ?? ''));

  /** ACF `details_show_social_column` (`true_false`, default on). Unchecked ⇒ two-column layout regardless of IG fields. */
  $explicitIncludeSocial = (bool) (int) ($c['details_show_social_column'] ?? 1);
  $hasSocial = $explicitIncludeSocial && ($igUrl !== '' || $igHandle !== '');

  $hasDetails = $phone !== '' || $addressForDisplay !== '' || $hasSocial;
@endphp

@if($hasDetails)
  <section
    class="shop-store-details {{ esc_attr($root) }} py-12 text-deep-moss lg:py-16"
    data-component-root
    data-shop-store-details>
    <div class="{{ LayoutShell::INNER_READABLE_960 }}">
      {{-- Section H2 — Figma shop single `51:6850`: Canela 58 / lh 84 (token: `text-5xl` → `md:text-6xl`). --}}
      <{{ $headingTag }} class="shop-store-details__heading {{ Component::sectionHeadingClasses('text-faded-olive', 'mb-10 text-center md:mb-12') }}">
        {{ esc_html($sectionHeading) }}
      </{{ $headingTag }}>

      {{-- Dividers aligned with opening-hours shop rows ({@see opening-hours.blade.php} `border-faded-olive/40`). --}}
      <div
        class="{{ esc_attr($hasSocial ? 'flex flex-col divide-y divide-faded-olive/40 lg:grid lg:grid-cols-3 lg:gap-0 lg:divide-x lg:divide-y-0' : 'flex flex-col divide-y divide-faded-olive/40 lg:grid lg:grid-cols-2 lg:gap-0 lg:divide-x lg:divide-y-0') }}">
        {{-- Column titles desktop spec preserved (Figma 51:6900 / 51:6903 — Canela 32 / lh 1.1).
             `max-sm:` overrides land Figma 51:8898 / 8902 / 8906 mobile (Halyard Medium 20 / lh 24)
             so tablet + desktop keep Canela 32 exactly as shipped.
             Values (51:6901, 51:6904) remain Halyard Book 24 / lh 30 below. --}}
        <div class="flex flex-col items-center py-10 text-center lg:px-8 lg:py-0 lg:pb-0 lg:pt-1 {{ $hasSocial ? '' : 'lg:pl-0' }}">
          <p class="{{ Component::mobilePanelSubheadClasses('text-faded-olive') }}">{{ esc_html($contactLabel) }}</p>
          @if($phone !== '')
            @php $telHref = preg_replace('/[^0-9+]/', '', str_replace("\xc2\xa0", ' ', $phone)); @endphp
            <p class="mt-3 font-sans text-2xl font-light leading-[30px] text-faded-olive">
              @if($telHref !== '')
                <a class="text-faded-olive underline decoration-brand-500 underline-offset-4 hover:decoration-faded-olive" href="{{ esc_url('tel:' . $telHref) }}">{{ esc_html($phone) }}</a>
              @else
                <span>{{ esc_html($phone) }}</span>
              @endif
            </p>
          @endif
        </div>

        <div class="flex flex-col items-center py-10 text-center lg:px-8 lg:py-0 lg:pt-1">
          <p class="{{ Component::mobilePanelSubheadClasses('text-faded-olive') }}">{{ esc_html($addressLabel) }}</p>
          @if($addressForDisplay !== '')
            <p class="mt-3 font-sans text-2xl font-light leading-[30px] text-faded-olive">
              {{ esc_html($addressForDisplay) }}</p>
          @endif
        </div>

        @if($hasSocial)
          <div class="flex flex-col items-center py-10 text-center lg:px-8 lg:py-0 lg:pt-1 lg:pr-0">
            <p class="{{ Component::mobilePanelSubheadClasses('text-faded-olive') }}">{{ esc_html($socialLabel) }}</p>
            @php
              $socialLinkClass =
                  'shop-store-details__social-link inline-flex items-center gap-2 font-label text-sm font-semibold uppercase '
                  . 'tracking-widest text-faded-olive underline decoration-brand-500 underline-offset-4 transition-colors '
                  . 'hover:decoration-faded-olive';
            @endphp
            <div class="mt-4 flex items-center justify-center">
              @if($igUrl !== '')
                <a
                  class="{{ esc_attr($socialLinkClass) }}"
                  href="{{ esc_url($igUrl) }}"
                  rel="noopener noreferrer nofollow"
                  target="_blank">
                  @include('partials.figma-social-icon', [
                      'social_icon_variant' => 'instagram',
                      'social_icon_class' => 'size-6 shrink-0 text-faded-olive',
                  ])
                  <span>{{ esc_html($igHandle !== '' ? $igHandle : $igUrl) }}</span>
                </a>
              @else
                <span class="inline-flex items-center gap-2 font-label text-sm font-semibold uppercase tracking-widest text-faded-olive">
                  @include('partials.figma-social-icon', [
                      'social_icon_variant' => 'instagram',
                      'social_icon_class' => 'size-6 shrink-0 text-faded-olive',
                  ])
                  <span>{{ esc_html($igHandle) }}</span>
                </span>
              @endif
            </div>
          </div>
        @endif
      </div>
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => $root,
      'message' => __('Add phone, address, or Instagram fields.', 'culvers'),
  ])
@endif
