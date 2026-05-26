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
  $originLabel = __('Starting point', 'culvers');
  $originPlaceholder = __('Type your starting point here', 'culvers');
  $modeLabel = __('travel by', 'culvers');
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
      'showMap' => $showMap && ($apiConfigured || $mockActive || $hasPlaceholder),
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
    <div
      class="travel-calculator__band mx-auto w-full max-w-[78rem] bg-light-green"
      :class="hasMapPanel
        ? 'rounded-[10px] p-4 sm:p-6'
        : 'rounded-[10px] px-4 pt-14 pb-20 sm:px-10 sm:py-12 md:px-16 md:py-16 lg:px-24 xl:px-[120px]'">
      {{-- Inner content area is 1008px wide per Figma (1248 - 120·2). --}}
      <div class="mx-auto w-full max-w-[63rem]">
        @if($hasIntro)
          <header class="text-center">
            @if($heading !== '')
              <h2 class="travel-calculator__heading {{ Component::sectionHeadingClasses('text-deep-moss') }}">
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
              class="travel-calculator__input h-[46px] w-full rounded-full border-[1.5px] border-faded-olive bg-transparent px-5 font-sans text-[15px] leading-[1.32] text-deep-moss placeholder:text-dustleaf focus:border-deep-moss focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-deep-moss"
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
                class="travel-calculator__select h-[46px] w-full appearance-none rounded-full border-[1.5px] border-faded-olive bg-transparent px-5 pr-10 font-sans text-[15px] leading-[1.32] text-deep-moss focus:border-deep-moss focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-deep-moss"
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
                 between idle / loading states. `btn-dark btn-form` — fixed padding on
                 hover via `.travel-calculator__submit` in travel-calculator.css. --}}
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
               is `max-sm:`-scoped only. Collapsed until a search runs so idle card padding
               is not inflated by an empty status row. --}}
          class="travel-calculator__result text-center font-label text-xs font-semibold uppercase leading-5 tracking-[1px] text-deep-moss max-sm:max-w-[19rem] max-sm:mx-auto"
          x-show="loading || error !== '' || result !== null"
          x-bind:class="(loading || error !== '' || result !== null) ? 'mt-10' : ''"
          x-cloak
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

        @if($showMap)
          {{-- Figma expanded: 24px inset on all sides of the map within the pale-sage card
               (`51:7995`–`7997`). Map lives inside the same band so padding stays uniform. --}}
          <div
            class="travel-calculator__map-panel mt-8 w-full sm:mt-10"
            x-show="hasMapPanel"
            x-cloak
            x-transition.opacity.duration.200ms>
            <div
              class="travel-calculator__map-frame relative min-h-[346px] w-full overflow-hidden rounded-[10px] bg-light-cream/40 md:min-h-[528px]"
              role="region"
              aria-label="{{ esc_attr__('Route preview map', 'culvers') }}"
              aria-busy="true"
              x-bind:aria-busy="mapLoading ? 'true' : 'false'">
              <div
                class="travel-calculator__map-loading absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 rounded-[10px] bg-light-green/90"
                x-show="mapLoading && ! mapError"
                x-cloak>
                <div
                  class="size-10 animate-spin rounded-full border-2 border-faded-olive/30 border-t-deep-moss"
                  aria-hidden="true"></div>
                <p class="font-label text-xs font-semibold uppercase tracking-[1px] text-deep-moss">
                  {{ esc_html__('Loading route map…', 'culvers') }}
                </p>
              </div>

              <p
                class="absolute inset-0 z-20 flex items-center justify-center px-6 text-center font-sans text-sm text-deep-moss md:text-base"
                x-show="mapError !== ''"
                x-text="mapError"
                x-cloak></p>

              @if($apiConfigured)
                <iframe
                  class="travel-calculator__map-iframe"
                  x-bind:src="embedSrc"
                  x-bind:key="embedSrc"
                  x-on:load="onMapLoad()"
                  x-on:error="onMapError()"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  allowfullscreen
                  title="{{ esc_attr__('Route preview map', 'culvers') }}"></iframe>
              @elseif($hasPlaceholder)
                <div x-init="mapLoading = false">
                  {!! Image::render($placeholderImage, [
                      'class' => 'block h-[346px] w-full object-cover md:h-[528px]',
                      'alt' => __('Route preview map', 'culvers'),
                  ]) !!}
                </div>
              @elseif($mockActive)
                <div
                  class="flex h-[346px] w-full flex-col items-center justify-center gap-4 text-center text-deep-moss md:h-[528px]"
                  x-init="mapLoading = false">
                  <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M20 6c-7.18 0-13 5.82-13 13 0 9.75 13 27 13 27s13-17.25 13-27c0-7.18-5.82-13-13-13Zm0 17.5a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9Z" fill="currentColor" opacity="0.85" />
                    <path d="M44 18c-7.18 0-13 5.82-13 13 0 9.75 13 27 13 27s13-17.25 13-27c0-7.18-5.82-13-13-13Zm0 17.5a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9Z" fill="currentColor" />
                  </svg>
                  <p class="font-sans text-sm text-deep-moss/80 md:text-base">
                    {{ __('Route preview map (dev mock).', 'culvers') }}
                  </p>
                </div>
              @endif
            </div>
          </div>
        @endif
      </div>

    </div>
  </div>
</section>
