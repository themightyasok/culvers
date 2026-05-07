@php
  use App\Customizer\GoogleMapsCustomizer;
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;
  use App\Travel\GoogleDistanceMatrixClient;
  use App\Travel\TravelCalculatorEndpoint;

  /**
   * Travel Calculator — faded-olive band with destination input + travel-by
   * select + search button, an inline result strip, and an optional Maps
   * Embed iframe below. Uses `wp-json/culvers/v1/travel-calculator` server-side
   * (Distance Matrix) and the Maps Embed API client-side. Configure the API
   * key + destination at Appearance → Customize → Google Maps. Figma ref:
   * 51:7970.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $heading = trim((string) ($c['tc_heading'] ?? ''));
  $intro = trim((string) ($c['tc_intro'] ?? ''));
  $destLabel = trim((string) ($c['tc_destination_label'] ?? '')) !== ''
      ? trim((string) $c['tc_destination_label'])
      : __('Your destination', 'culvers');
  $destPlaceholder = trim((string) ($c['tc_destination_placeholder'] ?? '')) !== ''
      ? trim((string) $c['tc_destination_placeholder'])
      : __('Type your destination here', 'culvers');
  $modeLabel = trim((string) ($c['tc_mode_label'] ?? '')) !== ''
      ? trim((string) $c['tc_mode_label'])
      : __('Travel by', 'culvers');
  $modePlaceholder = trim((string) ($c['tc_mode_placeholder'] ?? '')) !== ''
      ? trim((string) $c['tc_mode_placeholder'])
      : __('Select', 'culvers');
  $buttonLabel = trim((string) ($c['tc_button_label'] ?? '')) !== ''
      ? trim((string) $c['tc_button_label'])
      : __('Search', 'culvers');

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
    <div class="travel-calculator__band rounded-[10px] bg-faded-olive/30 px-6 py-12 md:px-12 md:py-16">
      @if($hasIntro)
        <header class="mx-auto max-w-[42rem] text-center">
          @if($heading !== '')
            <h2 class="travel-calculator__heading font-heading text-5xl leading-tight md:text-6xl">
              {{ esc_html($heading) }}
            </h2>
          @endif
          @if($intro !== '')
            <p class="travel-calculator__intro mt-4 font-sans text-base font-light text-deep-moss/80 md:text-lg">
              {{ esc_html($intro) }}
            </p>
          @endif
        </header>
      @endif

      @if(! $apiConfigured && current_user_can('edit_posts'))
        <div class="mt-8 rounded border border-amber-400 bg-amber-50 px-4 py-3 text-sm text-amber-950">
          {{ __('Add a Google Maps API key at Appearance → Customize → Google Maps to enable live travel lookups.', 'culvers') }}
        </div>
      @endif

      <form
        class="travel-calculator__form mt-8 grid gap-6 md:mt-10 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end"
        x-on:submit.prevent="submit()"
        novalidate>
        <div class="travel-calculator__field flex flex-col gap-2">
          <label
            for="{{ esc_attr($instanceId) }}-origin"
            class="font-sans text-xs font-semibold uppercase tracking-wider text-deep-moss">
            {{ esc_html($destLabel) }}
          </label>
          <input
            id="{{ esc_attr($instanceId) }}-origin"
            type="text"
            class="travel-calculator__input h-[46px] w-full rounded-full border border-deep-moss/30 bg-white px-5 font-sans text-sm text-deep-moss placeholder:text-deep-moss/50 focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf"
            placeholder="{{ esc_attr($destPlaceholder) }}"
            autocomplete="street-address"
            maxlength="200"
            required
            x-model.trim="origin"
            x-bind:disabled="loading"
            x-bind:aria-invalid="error !== '' ? 'true' : 'false'" />
        </div>

        <div class="travel-calculator__field flex flex-col gap-2">
          <label
            for="{{ esc_attr($instanceId) }}-mode"
            class="font-sans text-xs font-semibold uppercase tracking-wider text-deep-moss">
            {{ esc_html($modeLabel) }}
          </label>
          <div class="relative">
            <select
              id="{{ esc_attr($instanceId) }}-mode"
              class="travel-calculator__select h-[46px] w-full appearance-none rounded-full border border-deep-moss/30 bg-white px-5 pr-10 font-sans text-sm text-deep-moss focus:border-glowleaf focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-glowleaf"
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

        <div class="travel-calculator__submit md:justify-self-end">
          <button
            type="submit"
            class="btn btn-primary h-[46px] min-w-[120px]"
            x-bind:disabled="loading || origin.trim() === ''">
            <span x-show="!loading">{{ esc_html($buttonLabel) }}</span>
            <span x-show="loading" x-cloak>{{ esc_html__('Calculating…', 'culvers') }}</span>
          </button>
        </div>
      </form>

      <div
        class="travel-calculator__result mt-6 min-h-[1.5rem] text-center font-sans text-xs font-semibold uppercase tracking-wider md:text-sm"
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

    @if($showMap)
      <div class="travel-calculator__map mt-6 overflow-hidden rounded-[10px] bg-light-cream">
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
        @elseif(current_user_can('edit_posts'))
          <div class="flex h-[420px] w-full items-center justify-center bg-light-cream text-center font-sans text-sm text-deep-moss/70">
            {{ __('Configure a Google Maps API key to render the route preview.', 'culvers') }}
          </div>
        @endif
      </div>
    @endif
  </div>
</section>
