@php
  use App\Customizer\GoogleMapsCustomizer;
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;
  use App\Travel\GoogleDistanceMatrixClient;
  use App\Travel\TravelCalculatorEndpoint;

  // Dev-only: mock mode short-circuits the Distance Matrix call to return canned
  // data so designers can verify the result strip locally without a live key.
  // Always false on staging/live when a real key is set.
  $mockActive = GoogleDistanceMatrixClient::isMockEnabled();

  /**
   * Travel Calculator — pale-sage (Figma "Light Green" #DFE7BA) card with a
   * 64px Canela H2, a 20px Halyard subtitle, two pill inputs with 1.5px
   * faded-olive outlines on a transparent fill, and a deep-moss / glowleaf
   * search button (the inverse-primary `.btn-dark` CTA, matching Figma
   * Component 3 at 51:7993). Inline result strip below; optional Maps Embed
   * iframe below the card.
   *
   * Server side: `wp-json/culvers/v1/travel-calculator` (Distance Matrix).
   * Client side: Maps Embed API. Configure API key + destination at
   * Appearance → Customize → Google Maps.
   *
   * Figma ref: 51:7970 (card 51:7973 — 1248×441, 120/64 padding,
   * 1008px content, two 336/335 fields with 32px gap + ~115px button).
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $heading = trim((string) ($c['tc_heading'] ?? ''));
  $intro = trim((string) ($c['tc_intro'] ?? ''));
  $originLabel = __('Where are you travelling from', 'culvers');
  $originPlaceholder = __('Enter postcode or place', 'culvers');
  $destinationName = GoogleMapsCustomizer::destinationLabel();
  $modeLabel = __('Travel by', 'culvers');
  $modePlaceholder = __('Select', 'culvers');
  $buttonLabel = __('Search', 'culvers');

  $modesRaw = $c['tc_modes'] ?? [];
  $modes = [];
  if (is_array($modesRaw)) {
      foreach ($modesRaw as $row) {
          if (! is_array($row)) {
              continue;
          }
          $value = (string) ($row['item_mode'] ?? '');
          if (! in_array($value, GoogleDistanceMatrixClient::ALLOWED_MODES, true)) {
              continue;
          }
          $label = trim((string) ($row['item_label'] ?? ''));
          if ($label === '') {
              $label = match ($value) {
                  'driving' => __('Car', 'culvers'),
                  'transit' => __('Public transport', 'culvers'),
                  'walking' => __('Walking', 'culvers'),
                  'bicycling' => __('Cycling', 'culvers'),
                  default => ucfirst($value),
              };
          }
          $modes[] = ['value' => $value, 'label' => $label];
      }
  }
  if ($modes === []) {
      $modes = [
          ['value' => 'driving', 'label' => __('Car', 'culvers')],
          ['value' => 'transit', 'label' => __('Public transport', 'culvers')],
          ['value' => 'walking', 'label' => __('Walking', 'culvers')],
          ['value' => 'bicycling', 'label' => __('Cycling', 'culvers')],
      ];
  }
  $defaultMode = $modes[0]['value'];

  $showMap = ! empty($c['tc_show_map']);
  $placeholderImage = isset($c['tc_map_initial_image']) && is_array($c['tc_map_initial_image'])
      ? $c['tc_map_initial_image']
      : null;
  $hasPlaceholder = $placeholderImage !== null
      && trim((string) ($placeholderImage['url'] ?? '')) !== '';

  $apiKey = GoogleMapsCustomizer::apiKey();
  $apiConfigured = $apiKey !== '';
  $instanceId = 'travel-calc-' . uniqid();

  $alpineConfig = wp_json_encode([
      'endpoint' => esc_url_raw(rest_url(TravelCalculatorEndpoint::NAMESPACE . TravelCalculatorEndpoint::ROUTE)),
      'nonce' => wp_create_nonce('wp_rest'),
      'apiKey' => $apiKey,
      'destination' => [
          'address' => GoogleMapsCustomizer::destinationAddress(),
          'label' => GoogleMapsCustomizer::destinationLabel(),
          'placeId' => GoogleMapsCustomizer::destinationPlaceId(),
      ],
      'showMap' => $showMap && $apiConfigured,
      'defaultMode' => $defaultMode,
  ]);
  if (! is_string($alpineConfig)) {
      $alpineConfig = '{}';
  }

  $hasIntro = $heading !== '' || $intro !== '';
@endphp

<section
  class="travel-calculator {{ esc_attr($root) }} text-deep-moss"
  data-component-root
  data-travel-calculator
  x-data='travelCalculator({{ $alpineConfig }})'>
  <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
    {{-- Card matches Figma 51:7973 (1248 × 441, 64 / 120 padding desktop)
         + Figma 51:9028 mobile (16 / 58 / 91 padding inside a 399-wide card).
         Mobile uses asymmetric vertical padding (58 top / 91 bottom) so the
         title sits closer to the top edge while the search button has room
         below — sm and up reset to the symmetric desktop pattern. --}}
    <div class="travel-calculator__band mx-auto w-full max-w-[78rem] rounded-[10px] bg-light-green px-4 pt-14 pb-20 sm:px-10 sm:py-12 md:px-16 md:py-16 lg:px-24 xl:px-[120px]">
      {{-- Inner content area is 1008px wide per Figma (1248 - 120·2). --}}
      <div class="mx-auto w-full max-w-[63rem]">
        @if($hasIntro)
          <header class="text-center">
            @if($heading !== '')
              {{-- Figma `51:9035` Travel Calc Mobile: Canela 36 / lh 1.1 / Deep Moss (H2 Mobile);
                   Figma `51:7978` Travel Calc Desktop: Canela 64 / lh 1.2 / Deep Moss (H2 Title).
                   This is the one Section H2 in the design system that uses the larger 64 px
                   token rather than the generic 58 px, so we override `sectionHeadingClasses`
                   inline rather than route through it. --}}
              <h2 class="travel-calculator__heading font-heading text-4xl leading-[1.1] text-deep-moss md:text-7xl md:leading-[1.2]">
                {{ esc_html($heading) }}
              </h2>
            @endif
            @if($intro !== '')
              {{-- Figma `51:9036` Mobile: Halyard Book 16 / lh 1.32 (Small Body Mobile);
                   Figma `51:7979` Desktop: Halyard Book 20 / lh 1.3 (Large body copy). --}}
              <p class="travel-calculator__intro mt-6 font-sans text-base font-light leading-[1.32] text-deep-moss md:text-xl md:leading-[1.3]">
                {{ esc_html($intro) }}
              </p>
            @endif
          </header>
        @endif

        <p class="travel-calculator__destination @if($hasIntro) mt-4 @else mb-6 @endif text-center font-sans text-sm font-medium uppercase tracking-widest text-deep-moss/80 md:text-xs">
          {{ esc_html(sprintf(
              /* translators: %s: fixed destination name from Customizer */
              __('Destination: %s', 'culvers'),
              $destinationName !== '' ? $destinationName : __('Culver Square', 'culvers')
          )) }}
        </p>

        @if(! $apiConfigured && $mockActive && current_user_can('edit_posts'))
          {{-- Dev-only mock indicator: visible to editors on local so it's obvious
               the result strip is canned, not live. Suppressed on staging/live. --}}
          <div class="mt-8 rounded border border-deep-moss/30 bg-deep-moss/10 px-4 py-3 text-sm text-deep-moss">
            {{ __('Dev mock active — distances and durations are deterministic canned values. Set a Google Maps API key in Appearance → Customize → Google Maps for live lookups.', 'culvers') }}
          </div>
        @elseif(! $apiConfigured && current_user_can('edit_posts'))
          <div class="mt-8 rounded border border-amber-400 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            {{ __('Add a Google Maps API key at Appearance → Customize → Google Maps to enable live travel lookups.', 'culvers') }}
          </div>
        @endif

        {{-- Form row: two equal pill inputs (336/335px in Figma) and the Search
             button to the right. `auto` button column lets the dark CTA hug
             its label so the canonical `.btn` hover-widen still reads. --}}
        <form
          class="travel-calculator__form mx-auto mt-10 grid max-w-[850px] gap-6 md:mt-[38px] md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end md:gap-8"
          x-on:submit.prevent="submit()"
          novalidate>
          <div class="travel-calculator__field flex flex-col gap-[14px]">
            {{-- Figma 51:7982 field label: Commuters SemiBold 12 / lh 24 / 1 px tracking / uppercase. --}}
            <label
              for="{{ esc_attr($instanceId) }}-origin"
              class="font-label text-xs font-semibold uppercase leading-6 tracking-[0.0833em] text-deep-moss">
              {{ esc_html($originLabel) }}
            </label>
            <input
              id="{{ esc_attr($instanceId) }}-origin"
              type="text"
              class="travel-calculator__input h-[46px] w-full rounded-full border-[1.5px] border-faded-olive bg-transparent px-5 font-sans text-base leading-[1.32] text-deep-moss placeholder:text-dustleaf focus:border-deep-moss focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-deep-moss"
              placeholder="{{ esc_attr($originPlaceholder) }}"
              autocomplete="street-address"
              maxlength="200"
              required
              x-model.trim="origin"
              x-bind:disabled="loading"
              x-bind:aria-invalid="error !== '' ? 'true' : 'false'" />
          </div>

          <div class="travel-calculator__field flex flex-col gap-[14px]">
            {{-- Figma 51:7988 field label: Commuters SemiBold 12 / lh 24 / 1 px tracking / uppercase. --}}
            <label
              for="{{ esc_attr($instanceId) }}-mode"
              class="font-label text-xs font-semibold uppercase leading-6 tracking-[0.0833em] text-deep-moss">
              {{ esc_html($modeLabel) }}
            </label>
            <div class="relative">
              <select
                id="{{ esc_attr($instanceId) }}-mode"
                class="travel-calculator__select h-[46px] w-full appearance-none rounded-full border-[1.5px] border-faded-olive bg-transparent px-5 pr-10 font-sans text-base leading-[1.32] text-deep-moss focus:border-deep-moss focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-deep-moss"
                x-model="mode"
                x-bind:disabled="loading">
                @foreach($modes as $modeOption)
                  <option
                    value="{{ esc_attr($modeOption['value']) }}"
                    @if($modeOption['value'] === $defaultMode) selected @endif>
                    {{ esc_html($modeOption['label']) }}
                  </option>
                @endforeach
              </select>
              <span
                class="pointer-events-none absolute inset-y-0 right-5 flex items-center text-deep-moss"
                aria-hidden="true">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </span>
            </div>
          </div>

          <div class="travel-calculator__submit flex w-full justify-center md:w-auto md:justify-self-start">
            {{-- Hand-rolled button (not the partial) because the label swaps with Alpine
                 between idle / loading states. Class spine matches the partial — `btn
                 btn-dark btn-form` — so hover stays consistent with every other CTA.
                 `btn-dark` is the Figma travel-calculator search variant: deep-moss
                 fill, glowleaf text, padding-widen on hover (same as primary). --}}
            <button
              type="submit"
              class="btn btn-dark btn-form"
              x-bind:disabled="loading || origin.trim() === ''">
              <span x-show="!loading">{{ esc_html($buttonLabel) }}</span>
              <span x-show="loading" x-cloak>{{ esc_html__('Calculating…', 'culvers') }}</span>
            </button>
          </div>
        </form>

        <div
          {{-- Desktop result strip preserved (Commuters SemiBold 12 caps tracking-[1px] —
               shipped to match the original travel-calc skin).
               Figma 51:9221 mobile spec (Halyard Book 20 / lh 1.3 / Deep Moss, sentence case)
               is `max-sm:`-scoped only. --}}
          class="travel-calculator__result mt-10 min-h-[1.5rem] text-center font-sans text-xs font-semibold uppercase leading-6 tracking-[1px] text-deep-moss md:text-xs max-sm:text-xl max-sm:font-light max-sm:normal-case max-sm:tracking-normal max-sm:leading-[1.3]"
          role="status"
          aria-live="polite">
          <span x-show="error !== ''" class="text-red-700" x-text="error" x-cloak></span>
          <span
            x-show="error === '' && result !== null && !loading"
            x-text="result?.message ?? ''"
            x-cloak></span>
          <span x-show="loading" class="text-deep-moss/70" x-cloak>
            {{ esc_html__('Calculating your journey…', 'culvers') }}
          </span>
        </div>
      </div>
    </div>

    @if($showMap)
      {{-- Match the card width above (Figma 1248px / max-w-7xl) so the map
           and the band line up vertically rather than the map running wider. --}}
      <div class="travel-calculator__map mx-auto mt-6 w-full max-w-[78rem] overflow-hidden rounded-[10px] bg-light-cream">
        @if($apiConfigured)
          <iframe
            x-ref="map"
            class="block h-[420px] w-full md:h-[528px]"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen
            title="{{ esc_attr__('Route preview map', 'culvers') }}"></iframe>
        @elseif($hasPlaceholder)
          {!! Image::render($placeholderImage, [
              'class' => 'block h-auto w-full object-cover',
              'alt' => __('Map placeholder', 'culvers'),
          ]) !!}
        @elseif($mockActive)
          {{-- Dev-mock: shape-match the live iframe band (420 / 528) but render
               a token-tinted panel with a pin-pair illustration so the page
               reads visually like the Figma even without a real Maps key. --}}
          <div class="flex h-[420px] w-full flex-col items-center justify-center gap-4 bg-light-green/60 text-center text-deep-moss md:h-[528px]">
            <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M20 6c-7.18 0-13 5.82-13 13 0 9.75 13 27 13 27s13-17.25 13-27c0-7.18-5.82-13-13-13Zm0 17.5a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9Z" fill="currentColor" opacity="0.85" />
              <path d="M44 18c-7.18 0-13 5.82-13 13 0 9.75 13 27 13 27s13-17.25 13-27c0-7.18-5.82-13-13-13Zm0 17.5a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9Z" fill="currentColor" />
            </svg>
            <p class="font-sans text-sm text-deep-moss/80 md:text-base">
              {{ __('Live route preview disabled in dev mock mode.', 'culvers') }}
            </p>
          </div>
        @elseif(current_user_can('edit_posts'))
          <div class="flex h-[420px] w-full items-center justify-center bg-light-cream text-center font-sans text-sm text-deep-moss/70">
            {{ __('Configure a Google Maps API key to render the route preview.', 'culvers') }}
          </div>
        @endif
      </div>
    @endif
  </div>
</section>
