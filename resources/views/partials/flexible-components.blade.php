{{-- Flexible rows renderer — mirrors PHP flexible layout keys to Blade components.
     Template paths: App\Services\TemplateResolver::getInstance() (single entry; ComponentRegistry
     does not expose this). --}}

@php
use App\Helpers\ComponentDefaults;
use App\Helpers\ComponentLayoutChrome;
use App\Helpers\Grid;
use App\Helpers\Background;
use App\Helpers\LayoutShell;
use App\Helpers\Rhythm;
use App\Helpers\Sanitizer;
use App\Helpers\TailwindColors;
use App\Services\TemplateResolver;

$fieldName = $field_name ?? 'components';
$rawComponents = isset($raw_components_override) && is_array($raw_components_override)
    ? $raw_components_override
    : (get_field($fieldName) ?: []);
if (is_array($rawComponents)) {
    $rawComponents = array_values(array_filter($rawComponents, static function ($row): bool {
        return is_array($row) && empty($row['acf_fc_layout_disabled']);
    }));
}

$components = array_map(static fn ($row) => Sanitizer::component($row), is_array($rawComponents) ? $rawComponents : []);

$templateResolver = TemplateResolver::getInstance();
@endphp

@if($components)
  <div class="flexible-components {{ Grid::getMainGridContainerClasses() }}">
    @php
      $previousLayout = null;
      $previousComponent = [];
    @endphp
    @foreach($components as $component)
      @php
        /*
         * BladeOne merges `runChild()` / `@include` array keys into one long-lived PHP scope for the whole
         * view tree. Earlier flexible rows occasionally bind generic names like `class` / `href` / `label`
         * (icon partials, etc.); clearing them between rows guarantees the next layout cannot accidentally
         * reuse stray values — e.g. `size-*` Tailwind slipping onto unrelated CTAs in a later band.
         */
        unset($class, $href, $label, $button_extra_class, $attributes);

        $layout = $component['acf_fc_layout'] ?? '';
        $component = $component + ComponentDefaults::get($layout);
        $component = ComponentLayoutChrome::apply($component, $layout);
        $canonicalTone = TailwindColors::defaultBodyTextToneForLayout($layout);
        $component['body_text_tone'] = in_array($layout, [
            'shop_intro_block', 'shop_store_details', 'leasing_agent_grid', 'opening_hours',
        ], true)
            ? TailwindColors::bodyToneForWhiteBackground($canonicalTone)
            : TailwindColors::sanitizeBodyTextTone($canonicalTone);

        $rawWidth = $component['component_width'] ?? Grid::getDefaultComponentWidth($layout);
        $componentWidth = Grid::validateComponentWidth($rawWidth);
        $isFullWidthComponent = Grid::isFullWidth($layout);
        $gridClasses = Grid::getClasses($componentWidth, $isFullWidthComponent);
        $component['_grid_classes'] = $gridClasses['padding']
            ? trim($gridClasses['column'] . ' ' . $gridClasses['padding'])
            : $gridClasses['column'];

        // Inter-section rhythm: uniform `gap-y-24` on this grid; optional negative
        // `mt-*` on the current row only for flush / breathed / hugged exceptions.
        $spaceAboveClass = Rhythm::spaceAboveClass($previousLayout, $previousComponent, $layout);
        if ($spaceAboveClass !== '') {
            $component['_grid_classes'] = trim($spaceAboveClass . ' ' . $component['_grid_classes']);
        }

        $backgroundData = Background::process($component);

        $hasBackground = ($backgroundData['type'] !== 'none')
            && (
                ! empty($backgroundData['classes'])
                || ! empty($backgroundData['styles'])
                || ! empty($backgroundData['image'])
                || ! empty($backgroundData['video'])
                || ! empty($backgroundData['video_embed_url'])
                || ! empty($backgroundData['overlay_styles'])
            );

        $templatePath = $templateResolver->resolve($layout);
        $templateName = 'components.' . str_replace('_', '-', $layout);
        $parallaxAxis = apply_filters('culvers_background_parallax_axis', 'y', $layout);
      @endphp

      @if($templatePath)
        @if($hasBackground)
          {{-- ACF-set background wrapper. The wrapper paints the bg / image / video
               edge-to-edge across the viewport (full-bleed). Inter-section spacing
               is only the parent grid `gap-y-24`; optional negative `mt-*` from
               {@see App\Helpers\Rhythm} adjusts flush/breathed rows. Intra-band
               padding lives inside each component template, not on this wrapper. --}}
          @php
            // Inner component sits in a nested grid; the outer wrapper carries
            // the inter-section `mt-*`. Strip it from the inner classes so the
            // gap is not painted twice.
            $innerGridClasses = trim((string) preg_replace(
                '/\bmt-\S+/',
                '',
                (string) ($component['_grid_classes'] ?? '')
            ));
          @endphp
          <div class="relative col-span-full w-full min-w-0 {{ esc_attr(trim($spaceAboveClass . ' ' . ($backgroundData['classes'] ?? ''))) }}"
               data-component-background-wrapper="1"
               data-component-layout="{{ esc_attr($layout) }}"
               data-background-parallax="{{ ! empty($backgroundData['parallax']) ? '1' : '0' }}"
               @if($backgroundData['styles'] ?? '') style="{{ esc_attr($backgroundData['styles']) }}" @endif>
            @include('partials.component-background-media', [
                'backgroundData' => $backgroundData,
                'backgroundParallaxAxis' => $parallaxAxis,
            ])
            <div class="{{ Grid::getMainGridContainerClasses() }} relative z-20">
              @include($templateName, ['component' => array_merge($component, ['_background_handled' => true, '_grid_classes' => $innerGridClasses])])
            </div>
          </div>
        @else
          @include($templateName, ['component' => array_merge($component, ['_background_handled' => false])])
        @endif
      @else
        <div class="col-span-12 {{ LayoutShell::GUTTER_X }} {{ esc_attr($spaceAboveClass) }}">
          <div class="my-4 rounded border border-amber-400 bg-amber-50 px-4 py-3 text-amber-900 dark:bg-amber-950 dark:text-amber-100">
            <strong>{{ __('Missing component template', 'culvers') }}</strong>
            {{ sprintf(/* translators: %s layout key */ __('Layout "%s" has no matching Blade file.', 'culvers'), esc_html($layout)) }}
          </div>
        </div>
      @endif

      @php
        $previousLayout = $layout;
        $previousComponent = $component;
      @endphp
    @endforeach
  </div>
@endif
