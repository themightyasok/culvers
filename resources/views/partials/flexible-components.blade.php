{{-- Flexible rows renderer — mirrors PHP flexible layout keys to Blade components. --}}

@php
use App\Helpers\ComponentDefaults;
use App\Helpers\Grid;
use App\Helpers\Background;
use App\Helpers\Sanitizer;
use App\Helpers\TailwindColors;
use App\Services\TemplateResolver;

$fieldName = $field_name ?? 'components';
$rawComponents = get_field($fieldName) ?: [];
$isFullScreenScrolling = ! empty(get_field('full_screen_scrolling'));

$components = array_map(static fn ($row) => Sanitizer::component($row), is_array($rawComponents) ? $rawComponents : []);

$templateResolver = TemplateResolver::getInstance();
@endphp

@if($components)
  <div class="flexible-components {{ Grid::getMainGridContainerClasses() }}"
       data-full-screen-scrolling="{{ $isFullScreenScrolling ? '1' : '0' }}">
    @foreach($components as $component)
      @php
        $layout = $component['acf_fc_layout'] ?? '';
        $component = $component + ComponentDefaults::get($layout);
        $component['body_text_tone'] = TailwindColors::sanitizeBodyTextTone($component['body_text_tone'] ?? null);

        $rawWidth = $component['component_width'] ?? Grid::getDefaultComponentWidth($layout);
        $componentWidth = Grid::validateComponentWidth($rawWidth);
        $isFullWidthComponent = Grid::isFullWidth($layout);
        $gridClasses = Grid::getClasses($componentWidth, $isFullWidthComponent);
        $component['_grid_classes'] = $gridClasses['padding']
            ? trim($gridClasses['column'] . ' ' . $gridClasses['padding'])
            : $gridClasses['column'];

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
          <div class="relative col-span-full w-full min-w-0 {{ $backgroundData['classes'] ?? '' }}"
               data-component-background-wrapper="1"
               data-component-layout="{{ esc_attr($layout) }}"
               data-background-parallax="{{ ! empty($backgroundData['parallax']) ? '1' : '0' }}"
               @if($backgroundData['styles'] ?? '') style="{{ $backgroundData['styles'] }}" @endif>
            @include('partials.component-background-media', [
                'backgroundData' => $backgroundData,
                'backgroundParallaxAxis' => $parallaxAxis,
            ])
            <div class="{{ Grid::getMainGridContainerClasses() }} relative z-20">
              @include($templateName, ['component' => array_merge($component, ['_background_handled' => true])])
            </div>
          </div>
        @else
          @include($templateName, ['component' => array_merge($component, ['_background_handled' => false])])
        @endif
      @else
        <div class="col-span-12 lg:px-16">
          <div class="my-4 rounded border border-amber-400 bg-amber-50 px-4 py-3 text-amber-900 dark:bg-amber-950 dark:text-amber-100">
            <strong>{{ __('Missing component template', 'culvers') }}</strong>
            {{ sprintf(/* translators: %s layout key */ __('Layout "%s" has no matching Blade file.', 'culvers'), esc_html($layout)) }}
          </div>
        </div>
      @endif
    @endforeach
  </div>
@endif
