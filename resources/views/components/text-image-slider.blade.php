@php
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;

  /**
   * Text-image slider — vertical stack of large Canela headlines that expand
   * in place to reveal a body paragraph plus two polaroid-style images that
   * pop in (left + right) with a staggered scale/rotate animation. Inactive
   * headlines fade to a muted tone while one is open. Figma ref: 51:8074
   * (closed) / 51:8114 (open with images).
   *
   * Markup notes
   *  - The body panel is height-animated via the CSS grid-rows-[0fr→1fr] trick
   *    inside an `overflow-hidden` shell so opening doesn't jump the page.
   *  - Side images live OUTSIDE that shell as siblings of the panel: on mobile
   *    they stack in normal flow under the body; on lg+ the wrapper turns into
   *    `display:contents`, allowing each image to be positioned absolutely
   *    relative to the `<li>` (so they can overflow the central column without
   *    being clipped by the height-animation shell).
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $headingTag = Component::headingTag($c['tis_heading_level'] ?? null);

  $heading = trim((string) ($c['tis_heading'] ?? ''));
  $openMode = ($c['tis_open_mode'] ?? 'single') === 'multi' ? 'multi' : 'single';

  $itemsRaw = $c['tis_items'] ?? [];
  $items = [];
  if (is_array($itemsRaw)) {
      foreach ($itemsRaw as $row) {
          if (! is_array($row)) {
              continue;
          }
          $label = trim((string) ($row['item_label'] ?? ''));
          if ($label === '') {
              continue;
          }
          $bodyHtml = trim((string) ($row['item_body'] ?? ''));
          $left = isset($row['item_image_left']) && is_array($row['item_image_left']) ? $row['item_image_left'] : null;
          $right = isset($row['item_image_right']) && is_array($row['item_image_right']) ? $row['item_image_right'] : null;
          $items[] = [
              'label' => $label,
              'body_html' => $bodyHtml,
              'image_left' => $left,
              'image_right' => $right,
              'tilt_left' => isset($row['item_image_left_tilt']) ? (int) $row['item_image_left_tilt'] : -8,
              'tilt_right' => isset($row['item_image_right_tilt']) ? (int) $row['item_image_right_tilt'] : 6,
          ];
      }
  }

  $initialIndex = isset($c['tis_initial_open_index']) ? (int) $c['tis_initial_open_index'] : -1;
  $defaultOpen = ($initialIndex >= 0 && $initialIndex < count($items)) ? [$initialIndex] : [];

  $instanceId = 'tis-' . uniqid();
  $alpineConfig = wp_json_encode([
      'mode' => $openMode,
      'defaultOpen' => $defaultOpen,
  ]);
  if (! is_string($alpineConfig)) {
      $alpineConfig = '{}';
  }
@endphp

@if($items !== [])
  <section
    class="text-image-slider {{ esc_attr($root) }} relative bg-off-white text-deep-moss"
    data-component-root
    data-text-image-slider
    x-data='textImageSlider({{ $alpineConfig }})'>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      @if($heading !== '')
        <{{ $headingTag }} class="text-image-slider__heading mb-12 text-center font-heading text-5xl text-deep-moss md:text-6xl">
          {{ esc_html($heading) }}
        </{{ $headingTag }}>
      @endif

      <ul class="text-image-slider__list mx-auto flex w-full max-w-[634px] flex-col items-center gap-0" role="list">
        @foreach($items as $i => $item)
          @php
              $labelId = $instanceId . '-l-' . $i;
              $panelId = $instanceId . '-p-' . $i;
              $isOpen = in_array($i, $defaultOpen, true);
              $hasLeftImage = $item['image_left'] !== null
                  && trim((string) ($item['image_left']['url'] ?? '')) !== '';
              $hasRightImage = $item['image_right'] !== null
                  && trim((string) ($item['image_right']['url'] ?? '')) !== '';
          @endphp
          <li
            class="text-image-slider__item relative w-full"
            data-tis-item="{{ $i }}">
            <button
              id="{{ esc_attr($labelId) }}"
              type="button"
              class="text-image-slider__label group block w-full cursor-pointer py-3 text-center font-heading text-5xl leading-[1.1] tracking-tight transition-colors duration-300 ease-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-glowleaf md:text-6xl lg:text-7xl"
              data-tis-label
              aria-controls="{{ esc_attr($panelId) }}"
              aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
              x-on:click="toggle({{ $i }})"
              x-on:keydown.down.prevent="focusSibling({{ $i }}, 1)"
              x-on:keydown.up.prevent="focusSibling({{ $i }}, -1)"
              x-bind:class="isMuted({{ $i }}) ? 'text-deep-moss/30' : 'text-deep-moss'">
              {{ esc_html($item['label']) }}
            </button>

            {{-- Height-animated panel: body text only. Side images live below as siblings. --}}
            <div
              id="{{ esc_attr($panelId) }}"
              role="region"
              aria-labelledby="{{ esc_attr($labelId) }}"
              class="text-image-slider__panel grid transition-[grid-template-rows] duration-300 ease-out data-[open=true]:grid-rows-[1fr] grid-rows-[0fr]"
              data-open="{{ $isOpen ? 'true' : 'false' }}"
              @if(! $isOpen) hidden @endif>
              <div class="text-image-slider__panel-inner overflow-hidden">
                <div
                  class="text-image-slider__body mx-auto max-w-[44rem] px-2 py-6 text-center font-sans text-base font-light leading-7 text-deep-moss/90 opacity-0 lg:py-10 lg:text-lg [&_a]:text-faded-olive [&_a]:underline [&_a]:underline-offset-4 [&_p+p]:mt-3 [&_strong]:font-medium"
                  data-tis-body>
                  {!! $item['body_html'] !!}
                </div>
              </div>
            </div>

            @if($hasLeftImage || $hasRightImage)
              {{--
                Side images. On mobile the wrapper is a normal flex column under the body;
                on lg+ the wrapper becomes `display:contents` so each image positions
                absolutely against the `<li>` (which is `position: relative`).
              --}}
              <div
                class="text-image-slider__media-stack mt-2 mb-8 flex flex-col items-center gap-6 lg:mt-0 lg:mb-0 lg:contents"
                x-show="isOpen({{ $i }})"
                x-cloak>
                @if($hasLeftImage)
                  <div
                    class="text-image-slider__media text-image-slider__media--left w-[14rem] flex-none opacity-0 sm:w-[16rem] lg:absolute lg:left-[-22rem] lg:top-1/2 lg:w-[18rem] lg:-translate-y-1/2 xl:left-[-24rem] xl:w-[20rem]"
                    data-tis-media="left"
                    data-tilt="{{ esc_attr((string) $item['tilt_left']) }}">
                    <div class="text-image-slider__polaroid relative aspect-[4/5] w-full overflow-hidden rounded-[6px] shadow-2xl shadow-deep-moss/30 ring-1 ring-deep-moss/10">
                      {!! Image::render($item['image_left'], [
                          'class' => 'absolute inset-0 size-full object-cover',
                          'alt' => '',
                          'role' => 'presentation',
                      ]) !!}
                    </div>
                  </div>
                @endif

                @if($hasRightImage)
                  <div
                    class="text-image-slider__media text-image-slider__media--right w-[12rem] flex-none opacity-0 sm:w-[14rem] lg:absolute lg:right-[-22rem] lg:top-1/2 lg:w-[16rem] lg:-translate-y-1/2 xl:right-[-24rem] xl:w-[18rem]"
                    data-tis-media="right"
                    data-tilt="{{ esc_attr((string) $item['tilt_right']) }}">
                    <div class="text-image-slider__polaroid relative aspect-[4/5] w-full overflow-hidden rounded-[6px] shadow-2xl shadow-deep-moss/30 ring-1 ring-deep-moss/10">
                      {!! Image::render($item['image_right'], [
                          'class' => 'absolute inset-0 size-full object-cover',
                          'alt' => '',
                          'role' => 'presentation',
                      ]) !!}
                    </div>
                  </div>
                @endif
              </div>
            @endif
          </li>
        @endforeach
      </ul>
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => $root,
      'message' => __('Add at least one row with a headline.', 'culvers'),
  ])
@endif
