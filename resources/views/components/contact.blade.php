@php
  use App\Contact\ContactFormCopy;
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

  $headingTag = Component::headingTagFromComponent($c, 'contact_heading_level', 2);
  $heading = trim((string) ($c['contact_heading'] ?? ''));

  $showPanel = ! isset($c['contact_show_panel']) || ! empty($c['contact_show_panel']);
  $showMap = ! isset($c['contact_show_map']) || ! empty($c['contact_show_map']);

  $firstNameLabel = ContactFormCopy::firstNameLabel($c);
  $firstNamePlaceholder = ContactFormCopy::firstNamePlaceholder($c);
  $lastNameLabel = ContactFormCopy::lastNameLabel($c);
  $lastNamePlaceholder = ContactFormCopy::lastNamePlaceholder($c);
  $emailLabel = ContactFormCopy::emailLabel($c);
  $emailPlaceholder = ContactFormCopy::emailPlaceholder($c);
  $reasonLabel = ContactFormCopy::reasonLabel($c);
  $reasonPlaceholder = ContactFormCopy::reasonPlaceholder($c);
  $messageLabel = ContactFormCopy::messageLabel($c);
  $messagePlaceholder = ContactFormCopy::messagePlaceholder($c);
  $submitLabel = ContactFormCopy::submitLabel($c);
  $successMessage = ContactFormCopy::successMessage($c);

  $reasons = ContactFormCopy::enquiryReasonChoicesFromComponent($c);
  $hasReasonChoices = ContactFormCopy::useReasonSelect($c);

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
  $mapAlpineConfig = wp_json_encode([
      'apiKey' => $apiKey,
      'embedQuery' => $embedQuery,
      'initialZoom' => 14,
  ]);
  if (! is_string($mapAlpineConfig)) {
      $mapAlpineConfig = '{}';
  }
@endphp

<section
  class="contact {{ esc_attr($root) }} text-deep-moss"
  data-component-root
  data-contact
  x-data='contactForm({{ $alpineConfig }})'
  @if($heading !== '') aria-labelledby="{{ esc_attr($headingId) }}" @endif>
  <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
    @if($heading !== '')
      {{-- Optional band title (page often uses `image_hero` for H1). Figma section H2: Canela 58 / faded olive when used. --}}
      <{{ $headingTag }}
        id="{{ esc_attr($headingId) }}"
        class="contact__heading {{ Component::sectionIntroHeadingClasses('text-faded-olive', Component::sectionHeadingToFollowContentGapClasses() . ' text-center') }}">
        {{ esc_html($heading) }}
      </{{ $headingTag }}>
    @endif

    <div class="contact__band grid gap-10 md:gap-12 @if($showPanel) lg:grid-cols-[minmax(0,30%)_minmax(0,1fr)] lg:gap-16 @endif">
      @if($showPanel)
        <aside class="contact__panel flex flex-col text-faded-olive" aria-label="{{ esc_attr__('Getting here and contact details', 'culvers') }}">
          <div class="contact__panel-block">
            {{-- `font-sans!` forces the mobile Halyard 20 (Figma 51:9546) past the base
                 `h1-h6 { @apply font-heading }` rule + the `.font-heading` utility that
                 still compile above the `font-sans` rule in Tailwind 4's source order.
                 `sm:font-heading` (no `!`) restores Canela 32 / lh 1.1 from tablet up. --}}
            <h3 class="contact__panel-heading {{ Component::mobilePanelSubheadClasses('text-faded-olive', 'font-sans! sm:font-heading!') }}">
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
                @include('partials.footer-external-arrow-figma')
                @if(str_starts_with($mapUrl, 'http'))
                  <span class="sr-only">{{ __('(opens in new tab)', 'culvers') }}</span>
                @endif
              </a>
            @endif
          </div>

          @if($phone !== '' || $contactEmail !== '' || $hasSocial)
            <div class="contact__panel-block mt-12">
              {{-- Matches Getting Here subhead above: `font-sans!` wins mobile, `sm:font-heading!`
                   restores Canela 32 / lh 1.1 at tablet+. --}}
              <h3 class="contact__panel-heading {{ Component::mobilePanelSubheadClasses('text-faded-olive', 'font-sans! sm:font-heading!') }}">
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
            {{-- Desktop label spec preserved (Commuters SemiBold 12 caps tracking-widest).
                 `max-sm:` overrides land Figma 51:9574 mobile (Halyard Medium 20 / lh 24,
                 title-case, no tracking) so tablet + desktop forms read identically to
                 the pre-mobile-audit build. Single source for every contact field below. --}}
            <label
              for="{{ esc_attr($instanceId) }}-first"
              class="contact__label font-label text-xs font-semibold uppercase tracking-widest text-deep-moss max-sm:font-sans max-sm:text-xl max-sm:font-medium max-sm:normal-case max-sm:tracking-normal max-sm:leading-6">
              {{ esc_html($firstNameLabel) }}
            </label>
            <input
              id="{{ esc_attr($instanceId) }}-first"
              type="text"
              name="first_name"
              autocomplete="given-name"
              maxlength="100"
              required
              class="contact__input h-[46px] w-full rounded-full border-[1.5px] border-faded-olive bg-white px-5 font-sans text-[15px] font-light leading-[1.32] text-deep-moss placeholder:text-dustleaf focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf disabled:opacity-60"
              placeholder="{{ esc_attr($firstNamePlaceholder) }}"
              x-model.trim="firstName"
              x-on:input="onFieldInput()"
              x-bind:disabled="loading" />
          </div>

          <div class="contact__field flex flex-col gap-2">
            <label
              for="{{ esc_attr($instanceId) }}-last"
              class="contact__label font-label text-xs font-semibold uppercase tracking-widest text-deep-moss max-sm:font-sans max-sm:text-xl max-sm:font-medium max-sm:normal-case max-sm:tracking-normal max-sm:leading-6">
              {{ esc_html($lastNameLabel) }}
            </label>
            <input
              id="{{ esc_attr($instanceId) }}-last"
              type="text"
              name="last_name"
              autocomplete="family-name"
              maxlength="100"
              required
              class="contact__input h-[46px] w-full rounded-full border-[1.5px] border-faded-olive bg-white px-5 font-sans text-[15px] font-light leading-[1.32] text-deep-moss placeholder:text-dustleaf focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf disabled:opacity-60"
              placeholder="{{ esc_attr($lastNamePlaceholder) }}"
              x-model.trim="lastName"
              x-on:input="onFieldInput()"
              x-bind:disabled="loading" />
          </div>

          <div class="contact__field flex flex-col gap-2">
            <label
              for="{{ esc_attr($instanceId) }}-email"
              class="contact__label font-label text-xs font-semibold uppercase tracking-widest text-deep-moss max-sm:font-sans max-sm:text-xl max-sm:font-medium max-sm:normal-case max-sm:tracking-normal max-sm:leading-6">
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
              class="contact__input h-[46px] w-full rounded-full border-[1.5px] border-faded-olive bg-white px-5 font-sans text-[15px] font-light leading-[1.32] text-deep-moss placeholder:text-dustleaf focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf disabled:opacity-60"
              placeholder="{{ esc_attr($emailPlaceholder) }}"
              x-model.trim="email"
              x-on:input="onFieldInput()"
              x-bind:disabled="loading" />
          </div>

          <div class="contact__field flex flex-col gap-2">
            <label
              for="{{ esc_attr($instanceId) }}-reason"
              class="contact__label font-label text-xs font-semibold uppercase tracking-widest text-deep-moss max-sm:font-sans max-sm:text-xl max-sm:font-medium max-sm:normal-case max-sm:tracking-normal max-sm:leading-6">
              {{ esc_html($reasonLabel) }}
            </label>
            @if($hasReasonChoices)
              <div class="relative">
                <select
                  id="{{ esc_attr($instanceId) }}-reason"
                  name="reason"
                  class="contact__select h-[46px] w-full appearance-none rounded-full border-[1.5px] border-faded-olive bg-white px-5 pr-10 font-sans text-[15px] font-light leading-[1.32] text-deep-moss focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf disabled:opacity-60"
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
                class="contact__input h-[46px] w-full rounded-full border-[1.5px] border-faded-olive bg-white px-5 font-sans text-[15px] font-light leading-[1.32] text-deep-moss placeholder:text-dustleaf focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf disabled:opacity-60"
                placeholder="{{ esc_attr($reasonPlaceholder) }}"
                x-model.trim="reason"
                x-on:input="onFieldInput()"
                x-bind:disabled="loading" />
            @endif
          </div>

          <div class="contact__field md:col-span-2 flex flex-col gap-2">
            <label
              for="{{ esc_attr($instanceId) }}-message"
              class="contact__label font-label text-xs font-semibold uppercase tracking-widest text-deep-moss max-sm:font-sans max-sm:text-xl max-sm:font-medium max-sm:normal-case max-sm:tracking-normal max-sm:leading-6">
              {{ esc_html($messageLabel) }}
            </label>
            <textarea
              id="{{ esc_attr($instanceId) }}-message"
              name="message"
              rows="6"
              maxlength="5000"
              required
              class="contact__textarea min-h-[156px] w-full resize-y rounded-[20px] border-[1.5px] border-faded-olive bg-white px-5 py-4 font-sans text-[15px] font-light leading-[1.32] text-deep-moss placeholder:text-dustleaf focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf disabled:opacity-60"
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
    {{-- Same width shell as footer newsletter (`mx-auto max-w-8xl` in `footer.blade.php`). --}}
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }} contact__map mt-12 md:mt-16">
      @if($apiConfigured)
        <div
          class="contact__map-frame relative w-full overflow-hidden rounded-[10px] bg-light-cream aspect-[400/600] max-sm:min-h-[600px] sm:aspect-[1401/570]"
          data-contact-map
          x-data='contactMapEmbed({{ $mapAlpineConfig }})'>
          <iframe
            class="contact__map-iframe"
            x-bind:src="embedSrc"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen
            title="{{ esc_attr(sprintf(/* translators: %s: destination short label */ __('Map of %s', 'culvers'), $destLabel !== '' ? $destLabel : __('the centre', 'culvers'))) }}"></iframe>
          @include('partials.map-embed-zoom-controls')
        </div>
      @endif
    </div>
  @endif
</section>
