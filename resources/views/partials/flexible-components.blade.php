{{-- Flexible rows renderer — mirrors PHP flexible layout keys to Blade components. --}}

@php
use App\Helpers\ComponentDefaults;
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
$isFullScreenScrolling = ! empty(get_field('full_screen_scrolling'));

$components = array_map(static fn ($row) => Sanitizer::component($row), is_array($rawComponents) ? $rawComponents : []);

$templateResolver = TemplateResolver::getInstance();
@endphp

@if($components)
  <div class="flexible-components {{ Grid::getMainGridContainerClasses() }}"
       data-full-screen-scrolling="{{ $isFullScreenScrolling ? '1' : '0' }}">
    @php
      $previousLayout = null;
      $previousComponent = [];
    @endphp
    @foreach($components as $component)
      @php
        $layout = $component['acf_fc_layout'] ?? '';
        $component = $component + ComponentDefaults::get($layout);
        $rawTone = $component['body_text_tone'] ?? TailwindColors::defaultBodyTextToneForLayout($layout);
        $component['body_text_tone'] = in_array($layout, ['shop_intro_block', 'shop_store_details'], true)
            ? TailwindColors::bodyToneForWhiteBackground($rawTone)
            : TailwindColors::sanitizeBodyTextTone($rawTone);

        $rawWidth = $component['component_width'] ?? Grid::getDefaultComponentWidth($layout);
        $componentWidth = Grid::validateComponentWidth($rawWidth);
        $isFullWidthComponent = Grid::isFullWidth($layout);
        $gridClasses = Grid::getClasses($componentWidth, $isFullWidthComponent);
        $component['_grid_classes'] = $gridClasses['padding']
            ? trim($gridClasses['column'] . ' ' . $gridClasses['padding'])
            : $gridClasses['column'];

        $hideOnMobile = ($component['visibility_mobile'] ?? 'visible') === 'hidden';
        $visibilityClass = $hideOnMobile ? 'culvers-hide-below-md' : '';
        if ($visibilityClass !== '') {
            $component['_grid_classes'] = trim($visibilityClass . ' ' . $component['_grid_classes']);
        }

        // Inter-section rhythm: the *previous* component decides how much
        // space sits above the current one (Standard 96 / Hugged 60 / Flush 0).
        // First row gets no top margin — see App\Helpers\Rhythm for the model.
        $spaceAboveClass = Rhythm::spaceAboveClass($previousLayout, $previousComponent);
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
               edge-to-edge across the viewport (full-bleed), so it owns the visual
               surface and the internal breathing room above / below the content.
               `py-12 lg:py-16` (48 / 64 px) provides intra-section padding; the
               inter-section gap above is the `mt-*` rhythm utility from
               {@see App\Helpers\Rhythm}. The inner grid uses `gap-y-0` so the
               nested component placement does not introduce phantom rhythm. --}}
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
          <div class="relative col-span-full w-full min-w-0 py-12 lg:py-16 {{ esc_attr(trim($spaceAboveClass . ' ' . $visibilityClass . ' ' . ($backgroundData['classes'] ?? ''))) }}"
               data-component-background-wrapper="1"
               data-component-layout="{{ esc_attr($layout) }}"
               data-background-parallax="{{ ! empty($backgroundData['parallax']) ? '1' : '0' }}"
               @if($backgroundData['styles'] ?? '') style="{{ $backgroundData['styles'] }}" @endif>
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
