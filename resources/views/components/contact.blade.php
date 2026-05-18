@php
  use App\Contact\ContactFormEndpoint;
  use App\Customizer\FooterCustomizer;
  use App\Customizer\GoogleMapsCustomizer;
  use App\Helpers\Component;
  use App\Helpers\LayoutShell;

  /**
   * Contact — sidebar with "Getting here / Contact Us" details (single-source
   * via FooterCustomizer) plus a contact form posting to
   * `wp-json/culvers/v1/contact-form`. Optional Maps Embed below the band.
   * Figma ref: `51:9378` (frame under contact page `51:9353`).
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $headingTag = Component::headingTag($c['contact_heading_level'] ?? 2);
  $heading = trim((string) ($c['contact_heading'] ?? ''));

  $showPanel = ! isset($c['contact_show_panel']) || ! empty($c['contact_show_panel']);
  $showMap = ! isset($c['contact_show_map']) || ! empty($c['contact_show_map']);

  $firstNameLabel = trim((string) ($c['contact_form_first_name_label'] ?? '')) !== ''
      ? trim((string) $c['contact_form_first_name_label'])
      : __('First name*', 'culvers');
  $firstNamePlaceholder = trim((string) ($c['contact_form_first_name_placeholder'] ?? '')) !== ''
      ? trim((string) $c['contact_form_first_name_placeholder'])
      : __('Name', 'culvers');
  $lastNameLabel = trim((string) ($c['contact_form_last_name_label'] ?? '')) !== ''
      ? trim((string) $c['contact_form_last_name_label'])
      : __('Last name*', 'culvers');
  $lastNamePlaceholder = trim((string) ($c['contact_form_last_name_placeholder'] ?? '')) !== ''
      ? trim((string) $c['contact_form_last_name_placeholder'])
      : __('Last name', 'culvers');
  $emailLabel = trim((string) ($c['contact_form_email_label'] ?? '')) !== ''
      ? trim((string) $c['contact_form_email_label'])
      : __('Email*', 'culvers');
  $emailPlaceholder = trim((string) ($c['contact_form_email_placeholder'] ?? '')) !== ''
      ? trim((string) $c['contact_form_email_placeholder'])
      : __('Email address', 'culvers');
  $reasonLabel = trim((string) ($c['contact_form_reason_label'] ?? '')) !== ''
      ? trim((string) $c['contact_form_reason_label'])
      : __('Reason for enquiry*', 'culvers');
  $reasonPlaceholder = trim((string) ($c['contact_form_reason_placeholder'] ?? '')) !== ''
      ? trim((string) $c['contact_form_reason_placeholder'])
      : __('Select', 'culvers');
  $messageLabel = trim((string) ($c['contact_form_message_label'] ?? '')) !== ''
      ? trim((string) $c['contact_form_message_label'])
      : __('Message', 'culvers');
  $messagePlaceholder = trim((string) ($c['contact_form_message_placeholder'] ?? '')) !== ''
      ? trim((string) $c['contact_form_message_placeholder'])
      : __('Type message here', 'culvers');
  $submitLabel = trim((string) ($c['contact_form_submit_label'] ?? '')) !== ''
      ? trim((string) $c['contact_form_submit_label'])
      : __('Submit', 'culvers');
  $successMessage = trim((string) ($c['contact_form_success_message'] ?? '')) !== ''
      ? trim((string) $c['contact_form_success_message'])
      : __('Thanks — your message is on its way.', 'culvers');

  $reasons = [];
  if (isset($c['contact_form_reasons']) && is_array($c['contact_form_reasons'])) {
      foreach ($c['contact_form_reasons'] as $row) {
          if (! is_array($row)) {
              continue;
          }
          $reasonValue = trim((string) ($row['item_reason'] ?? ''));
          if ($reasonValue !== '') {
              $reasons[] = $reasonValue;
          }
      }
  }
  $hasReasonChoices = $reasons !== [];

  $instanceId = 'contact-' . uniqid();
  $headingId = $heading !== '' ? $instanceId . '-heading' : '';
  $statusId = $instanceId . '-status';

  $alpineConfig = wp_json_encode([
      'endpoint' => esc_url_raw(rest_url(ContactFormEndpoint::NAMESPACE . ContactFormEndpoint::ROUTE)),
      'nonce' => wp_create_nonce('wp_rest'),
      'successMessage' => $successMessage,
  ]);
  if (! is_string($alpineConfig)) {
      $alpineConfig = '{}';
  }

  $address = FooterCustomizer::gettingHereAddress();
  $mapUrl = FooterCustomizer::gettingHereMapUrl();
  $mapLabel = FooterCustomizer::gettingHereMapLabel();
  $phone = FooterCustomizer::contactPhone();
  $contactEmail = FooterCustomizer::contactEmail();
  $instagramUrl = FooterCustomizer::instagramUrl();
  $facebookUrl = FooterCustomizer::facebookUrl();
  $hasSocial = $instagramUrl !== '' || $facebookUrl !== '';

  $apiKey = GoogleMapsCustomizer::apiKey();
  $apiConfigured = $apiKey !== '';
  $destLabel = GoogleMapsCustomizer::destinationLabel();
  $destAddress = GoogleMapsCustomizer::destinationAddress();
  $destPlaceId = GoogleMapsCustomizer::destinationPlaceId();
  $embedQuery = $destPlaceId !== ''
      ? 'place_id:' . $destPlaceId
      : ($destAddress !== '' ? $destAddress : 'Culver Square Colchester');
  $embedSrc = $apiConfigured
      ? 'https://www.google.com/maps/embed/v1/place?key=' . rawurlencode($apiKey) . '&q=' . rawurlencode($embedQuery) . '&zoom=14'
      : '';
@endphp

<section
  class="contact {{ esc_attr($root) }} bg-lighter-cream text-deep-moss"
  data-component-root
  data-contact
  x-data='contactForm({{ $alpineConfig }})'
  @if($heading !== '') aria-labelledby="{{ esc_attr($headingId) }}" @endif>
  <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
    @if($heading !== '')
      {{-- Optional band title (page often uses `image_hero` for H1). Figma section H2: Canela 58 / faded olive when used. --}}
      <{{ $headingTag }}
        id="{{ esc_attr($headingId) }}"
        class="contact__heading {{ Component::sectionHeadingClasses('text-faded-olive', 'mb-10 text-center md:mb-14') }}">
        {{ esc_html($heading) }}
      </{{ $headingTag }}>
    @endif

    <div class="contact__band grid gap-10 md:gap-12 @if($showPanel) lg:grid-cols-[minmax(0,30%)_minmax(0,1fr)] lg:gap-16 @endif">
      @if($showPanel)
        <aside class="contact__panel flex flex-col text-faded-olive" aria-label="{{ esc_attr__('Getting here and contact details', 'culvers') }}">
          <div class="contact__panel-block">
            {{-- Figma 51:9546 mobile: panel subheads are Halyard Medium 20 / lh 24 — quieter than
                 the image-hero H1 above so the address copy below feels related, not announced.
                 (Was Canela 32 — too display-y next to the body copy.) --}}
            <h3 class="contact__panel-heading font-sans text-xl font-medium leading-6 text-faded-olive">
              {{ esc_html(FooterCustomizer::gettingHereTitle()) }}
            </h3>
            @if($address !== '')
              <p class="contact__panel-address mt-5 font-sans text-xl font-light leading-[1.3] text-faded-olive">
                {!! nl2br(esc_html($address)) !!}
              </p>
            @endif
            @if($mapUrl !== '')
              <a
                class="contact__panel-map-link mt-5 inline-flex items-center gap-2 border-b border-faded-olive/40 pb-1 font-label text-xs font-semibold uppercase tracking-widest text-faded-olive transition-colors hover:border-deep-moss hover:text-deep-moss"
                href="{{ esc_url($mapUrl) }}"
                @if(str_starts_with($mapUrl, 'http')) target="_blank" rel="noopener noreferrer" @endif>
                {{ esc_html($mapLabel) }}
                <span aria-hidden="true">↗</span>
                @if(str_starts_with($mapUrl, 'http'))
                  <span class="sr-only">{{ __('(opens in new tab)', 'culvers') }}</span>
                @endif
              </a>
            @endif
          </div>

          @if($phone !== '' || $contactEmail !== '' || $hasSocial)
            <div class="contact__panel-block mt-12">
              {{-- Figma 51:9556 mobile: matches Getting Here subhead spec above. --}}
              <h3 class="contact__panel-heading font-sans text-xl font-medium leading-6 text-faded-olive">
                {{ esc_html(FooterCustomizer::contactTitle()) }}
              </h3>
              @if($phone !== '')
                <a
                  class="contact__panel-phone mt-5 block font-sans text-xl font-light leading-[30px] text-faded-olive transition-colors hover:text-deep-moss"
                  href="{{ esc_url('tel:' . preg_replace('/\s+/', '', $phone)) }}">
                  {{ esc_html($phone) }}
                </a>
              @endif
              @if($contactEmail !== '')
                <a
                  class="contact__panel-email mt-2 inline-block w-fit font-sans text-xl font-light leading-[30px] text-faded-olive underline decoration-faded-olive underline-offset-[5px] transition-colors hover:text-deep-moss hover:decoration-deep-moss"
                  href="{{ esc_url('mailto:' . $contactEmail) }}">
                  {{ esc_html($contactEmail) }}
                </a>
              @endif
              @if($hasSocial)
                <div class="contact__panel-social mt-6 flex flex-wrap gap-x-8 gap-y-3">
                  @if($instagramUrl !== '')
                    <a
                      class="contact__panel-social-link inline-flex items-center gap-2 font-label text-sm font-semibold uppercase tracking-widest text-faded-olive transition-colors hover:text-deep-moss"
                      href="{{ esc_url($instagramUrl) }}"
                      rel="noopener noreferrer"
                      target="_blank">
                      @include('partials.figma-social-icon', [
                          'social_icon_variant' => 'instagram',
                          'social_icon_class' => 'size-6 shrink-0 overflow-visible text-faded-olive',
                      ])
                      {{ __('Instagram', 'culvers') }}
                    </a>
                  @endif
                  @if($facebookUrl !== '')
                    <a
                      class="contact__panel-social-link inline-flex items-center gap-2 font-label text-sm font-semibold uppercase tracking-widest text-faded-olive transition-colors hover:text-deep-moss"
                      href="{{ esc_url($facebookUrl) }}"
                      rel="noopener noreferrer"
                      target="_blank">
                      @include('partials.figma-social-icon', [
                          'social_icon_variant' => 'facebook',
                          'social_icon_class' => 'size-6 shrink-0 text-faded-olive',
                      ])
                      {{ __('Facebook', 'culvers') }}
                    </a>
                  @endif
                </div>
              @endif
            </div>
          @endif
        </aside>
      @endif

      <form
        class="contact__form grid grid-cols-1 gap-x-6 gap-y-6 md:grid-cols-2"
        x-on:submit.prevent="submit()"
        novalidate>
          <div class="contact__field flex flex-col gap-2">
            {{-- Figma 51:9574: form field labels are Halyard Medium 20 / lh 24 title-case (no
                 uppercase / no tracking). Single source for every contact field below. --}}
            <label
              for="{{ esc_attr($instanceId) }}-first"
              class="contact__label font-sans text-xl font-medium leading-6 text-deep-moss">
              {{ esc_html($firstNameLabel) }}
            </label>
            <input
              id="{{ esc_attr($instanceId) }}-first"
              type="text"
              name="first_name"
              autocomplete="given-name"
              maxlength="100"
              required
              class="contact__input h-[46px] w-full rounded-full border border-deep-moss/30 bg-white px-5 font-sans text-[15px] font-light leading-[1.32] text-deep-moss placeholder:text-dustleaf focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf disabled:opacity-60"
              placeholder="{{ esc_attr($firstNamePlaceholder) }}"
              x-model.trim="firstName"
              x-on:input="onFieldInput()"
              x-bind:disabled="loading" />
          </div>

          <div class="contact__field flex flex-col gap-2">
            <label
              for="{{ esc_attr($instanceId) }}-last"
              class="contact__label font-sans text-xl font-medium leading-6 text-deep-moss">
              {{ esc_html($lastNameLabel) }}
            </label>
            <input
              id="{{ esc_attr($instanceId) }}-last"
              type="text"
              name="last_name"
              autocomplete="family-name"
              maxlength="100"
              required
              class="contact__input h-[46px] w-full rounded-full border border-deep-moss/30 bg-white px-5 font-sans text-[15px] font-light leading-[1.32] text-deep-moss placeholder:text-dustleaf focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf disabled:opacity-60"
              placeholder="{{ esc_attr($lastNamePlaceholder) }}"
              x-model.trim="lastName"
              x-on:input="onFieldInput()"
              x-bind:disabled="loading" />
          </div>

          <div class="contact__field flex flex-col gap-2">
            <label
              for="{{ esc_attr($instanceId) }}-email"
              class="contact__label font-sans text-xl font-medium leading-6 text-deep-moss">
              {{ esc_html($emailLabel) }}
            </label>
            <input
              id="{{ esc_attr($instanceId) }}-email"
              type="email"
              name="email"
              autocomplete="email"
              maxlength="200"
              required
              inputmode="email"
              class="contact__input h-[46px] w-full rounded-full border border-deep-moss/30 bg-white px-5 font-sans text-[15px] font-light leading-[1.32] text-deep-moss placeholder:text-dustleaf focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf disabled:opacity-60"
              placeholder="{{ esc_attr($emailPlaceholder) }}"
              x-model.trim="email"
              x-on:input="onFieldInput()"
              x-bind:disabled="loading" />
          </div>

          <div class="contact__field flex flex-col gap-2">
            <label
              for="{{ esc_attr($instanceId) }}-reason"
              class="contact__label font-sans text-xl font-medium leading-6 text-deep-moss">
              {{ esc_html($reasonLabel) }}
            </label>
            @if($hasReasonChoices)
              <div class="relative">
                <select
                  id="{{ esc_attr($instanceId) }}-reason"
                  name="reason"
                  class="contact__select h-[46px] w-full appearance-none rounded-full border border-deep-moss/30 bg-white px-5 pr-10 font-sans text-[15px] font-light leading-[1.32] text-deep-moss focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf disabled:opacity-60"
                  x-model="reason"
                  x-on:change="onFieldInput()"
                  x-bind:disabled="loading">
                  <option value="">{{ esc_html($reasonPlaceholder) }}</option>
                  @foreach($reasons as $reasonChoice)
                    <option value="{{ esc_attr($reasonChoice) }}">{{ esc_html($reasonChoice) }}</option>
                  @endforeach
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-5 flex items-center text-deep-moss" aria-hidden="true">
                  <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </span>
              </div>
            @else
              <input
                id="{{ esc_attr($instanceId) }}-reason"
                type="text"
                name="reason"
                maxlength="100"
                class="contact__input h-[46px] w-full rounded-full border border-deep-moss/30 bg-white px-5 font-sans text-[15px] font-light leading-[1.32] text-deep-moss placeholder:text-dustleaf focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf disabled:opacity-60"
                placeholder="{{ esc_attr($reasonPlaceholder) }}"
                x-model.trim="reason"
                x-on:input="onFieldInput()"
                x-bind:disabled="loading" />
            @endif
          </div>

          <div class="contact__field md:col-span-2 flex flex-col gap-2">
            <label
              for="{{ esc_attr($instanceId) }}-message"
              class="contact__label font-sans text-xl font-medium leading-6 text-deep-moss">
              {{ esc_html($messageLabel) }}
            </label>
            <textarea
              id="{{ esc_attr($instanceId) }}-message"
              name="message"
              rows="6"
              maxlength="5000"
              required
              class="contact__textarea min-h-[156px] w-full resize-y rounded-[18px] border border-deep-moss/30 bg-white px-5 py-4 font-sans text-[15px] font-light leading-[1.32] text-deep-moss placeholder:text-dustleaf focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf disabled:opacity-60"
              placeholder="{{ esc_attr($messagePlaceholder) }}"
              x-model.trim="message"
              x-on:input="onFieldInput()"
              x-bind:disabled="loading"></textarea>
          </div>

          {{-- Honeypot — visually hidden, ignored by humans, picked up by bots. --}}
          <div class="contact__honeypot absolute h-px w-px overflow-hidden border-0 p-0" aria-hidden="true" style="left:-10000px; clip:rect(0,0,0,0);">
            <label for="{{ esc_attr($instanceId) }}-website">{{ __('Website (leave empty)', 'culvers') }}</label>
            <input
              id="{{ esc_attr($instanceId) }}-website"
              type="text"
              name="website"
              tabindex="-1"
              autocomplete="off"
              x-model="website" />
          </div>

          <div class="contact__actions md:col-span-2 mt-2 flex flex-wrap items-center gap-x-6 gap-y-4">
            {{-- Hand-rolled button (not the partial) because the label swaps with Alpine
                 between idle / loading states. Class spine matches the partial — `btn
                 btn-primary btn-form` — so hover stays consistent with every other CTA. --}}
            <button
              type="submit"
              class="btn btn-primary btn-form"
              x-bind:disabled="loading">
              <span x-show="!loading">{{ esc_html($submitLabel) }}</span>
              <span x-show="loading" x-cloak>{{ esc_html__('Sending…', 'culvers') }}</span>
            </button>

            <p
              id="{{ esc_attr($statusId) }}"
              class="contact__status m-0 font-sans text-sm"
              role="status"
              aria-live="polite">
              <span x-show="status === 'error'" class="text-red-700" x-text="statusMessage" x-cloak></span>
              <span x-show="status === 'success'" class="text-faded-olive" x-text="statusMessage" x-cloak></span>
            </p>
          </div>
        </form>
    </div>
  </div>

  @if($showMap)
    <div class="contact__map mt-12 overflow-hidden bg-light-cream md:mt-16">
      @if($apiConfigured)
        <iframe
          class="block h-[420px] w-full md:h-[570px]"
          src="{{ esc_url($embedSrc) }}"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          allowfullscreen
          title="{{ esc_attr(sprintf(/* translators: %s: destination short label */ __('Map of %s', 'culvers'), $destLabel !== '' ? $destLabel : __('the centre', 'culvers'))) }}"></iframe>
      @elseif(current_user_can('edit_posts'))
        <div class="flex h-[420px] w-full items-center justify-center bg-light-cream text-center font-sans text-sm text-deep-moss/70">
          {{ __('Configure a Google Maps API key at Appearance → Customize → Google Maps to render the embedded map.', 'culvers') }}
        </div>
      @endif
    </div>
  @endif
</section>
