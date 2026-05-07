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
  $headingTag = Component::headingTag($c['details_heading_level'] ?? null);

  $sectionHeading = trim((string) ($c['details_heading'] ?? ''));
  if ($sectionHeading === '') {
      $sectionHeading = __('Store Details', 'culvers');
  }

  $contactLabel = trim((string) ($c['details_contact_label'] ?? __('Contact Number', 'culvers')));
  $phone = trim((string) ($c['details_contact_phone'] ?? ''));
  $addressLabel = trim((string) ($c['details_address_label'] ?? __('Address', 'culvers')));
  $addressRaw = trim((string) ($c['details_address'] ?? ''));
  $addressPlain = trim(wp_strip_all_tags(preg_replace('/<br\s*\/?>/i', ' ', $addressRaw)));
  $addressForDisplay = preg_replace('/\s+/u', ' ', $addressPlain);
  $socialLabel = trim((string) ($c['details_social_label'] ?? __('Social Media', 'culvers')));
  $igUrl = trim((string) ($c['details_instagram_url'] ?? ''));
  $igHandle = trim((string) ($c['details_instagram_handle'] ?? ''));
  $hasSocial = $igUrl !== '' || $igHandle !== '';

  $hasDetails = $phone !== '' || $addressForDisplay !== '' || $hasSocial;
@endphp

@if($hasDetails)
  <section
    class="shop-store-details {{ esc_attr($root) }} bg-white text-deep-moss"
    data-component-root
    data-shop-store-details>
    <div class="{{ LayoutShell::INNER_READABLE_960 }}">
      <{{ $headingTag }} class="shop-store-details__heading mb-10 text-center font-heading text-6xl tracking-tight text-faded-olive md:mb-12">
        {{ esc_html($sectionHeading) }}
      </{{ $headingTag }}>

      <div
        class="{{ esc_attr($hasSocial ? 'grid gap-10 divide-y divide-faded-olive/15 lg:grid-cols-3 lg:gap-0 lg:divide-x lg:divide-y-0' : 'grid gap-10 divide-y divide-faded-olive/15 lg:grid-cols-2 lg:gap-0 lg:divide-x lg:divide-y-0') }}">
        <div class="flex flex-col items-center text-center lg:px-8 lg:pb-0 lg:pt-1 {{ $hasSocial ? '' : 'lg:pl-0' }}">
          <p class="font-heading text-3xl text-faded-olive">{{ esc_html($contactLabel) }}</p>
          @if($phone !== '')
            @php $telHref = preg_replace('/[^0-9+]/', '', str_replace("\xc2\xa0", ' ', $phone)); @endphp
            <p class="mt-3 font-sans text-2xl font-light text-faded-olive">
              @if($telHref !== '')
                <a class="text-faded-olive underline decoration-brand-500 underline-offset-4 hover:decoration-faded-olive" href="{{ esc_url('tel:' . $telHref) }}">{{ esc_html($phone) }}</a>
              @else
                <span>{{ esc_html($phone) }}</span>
              @endif
            </p>
          @endif
        </div>

        <div class="flex flex-col items-center pt-10 text-center lg:px-8 lg:pt-1">
          <p class="font-heading text-3xl text-faded-olive">{{ esc_html($addressLabel) }}</p>
          @if($addressForDisplay !== '')
            <p class="mt-3 font-sans text-2xl font-light text-faded-olive">
              {{ esc_html($addressForDisplay) }}</p>
          @endif
        </div>

        @if($hasSocial)
          <div class="flex flex-col items-center pt-10 text-center lg:px-8 lg:pt-1 lg:pr-0">
            <p class="font-heading text-3xl text-faded-olive">{{ esc_html($socialLabel) }}</p>
            <div class="mt-4 flex items-center justify-center gap-3 font-sans">
              <span class="inline-flex shrink-0 items-center justify-center text-faded-olive" aria-hidden="true">
                <svg class="size-[22px]" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M12 7.2c2.48 0 2.79.01 3.77.05.91.04 1.4.19 1.73.31.43.17.74.37 1.06.69.32.32.52.63.69 1.06.12.33.27.82.31 1.73.04.98.05 1.29.05 3.77s-.01 2.79-.05 3.77c-.04.91-.19 1.4-.31 1.73-.17.43-.37.74-.69 1.06-.32.32-.63.52-1.06.69-.33.12-.82.27-1.73.31-.98.04-1.29.05-3.77.05s-2.79-.01-3.77-.05c-.91-.04-1.4-.19-1.73-.31-.43-.17-.74-.37-1.06-.69-.32-.32-.52-.63-.69-1.06-.12-.33-.27-.82-.31-1.73-.04-.98-.05-1.29-.05-3.77s.01-2.79.05-3.77c.04-.91.19-1.4.31-1.73.17-.43.37-.74.69-1.06.32-.32.63-.52 1.06-.69.33-.12.82-.27 1.73-.31.98-.04 1.29-.05 3.77-.05Zm0-1.68c-2.52 0-2.84.01-3.83.06-1 .05-1.68.23-2.27.49-.62.24-1.14.56-1.66 1.08-.52.52-.84 1.04-1.08 1.66-.26.59-.44 1.27-.49 2.27-.05.99-.06 1.31-.06 3.83s.01 2.84.06 3.83c.05 1 .23 1.68.49 2.27.24.62.56 1.14 1.08 1.66.52.52 1.04.84 1.66 1.08.59.26 1.27.44 2.27.49.99.05 1.31.06 3.83.06s2.84-.01 3.83-.06c1-.05 1.68-.23 2.27-.49.62-.24 1.14-.56 1.66-1.08.52-.52.84-1.04 1.08-1.66.26-.59.44-1.27.49-2.27.05-.99.06-1.31.06-3.83s-.01-2.84-.06-3.83c-.05-1-.23-1.68-.49-2.27-.24-.62-.56-1.14-1.08-1.66-.52-.52-1.04-.84-1.66-1.08-.59-.26-1.27-.44-2.27-.49-.99-.05-1.31-.06-3.83-.06Z" fill="currentColor"/>
                  <path d="M12 8.27c-2.06 0-3.73 1.67-3.73 3.73 0 2.06 1.67 3.73 3.73 3.73 2.06 0 3.73-1.67 3.73-3.73 0-2.06-1.67-3.73-3.73-3.73Zm0 6.15c-1.34 0-2.42-1.08-2.42-2.42 0-1.34 1.08-2.42 2.42-2.42 1.34 0 2.42 1.08 2.42 2.42 0 1.34-1.08 2.42-2.42 2.42Zm4.76-6.31c0 .48-.39.87-.87.87-.48 0-.87-.39-.87-.87 0-.48.39-.87.87-.87.48 0 .87.39.87.87Z" fill="currentColor"/>
                </svg>
              </span>
              @if($igUrl !== '')
                <a
                  class="text-xs font-semibold uppercase tracking-widest text-faded-olive underline decoration-brand-500 underline-offset-4 hover:decoration-faded-olive"
                  href="{{ esc_url($igUrl) }}"
                  rel="noopener noreferrer nofollow"
                  target="_blank">
                  {{ esc_html($igHandle !== '' ? $igHandle : $igUrl) }}
                </a>
              @else
                <span class="text-xs font-semibold uppercase tracking-widest text-faded-olive">{{ esc_html($igHandle) }}</span>
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
