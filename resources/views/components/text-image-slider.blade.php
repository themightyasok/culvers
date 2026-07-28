@php
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;
  use App\Support\PageSectionAnchor;

  /**
   * Text-image slider — vertical stack of large Canela headlines that expand
   * in place to reveal a body paragraph plus polaroid-style images. Headlines
   * stay readable (solid foreground) regardless of open state.
   *
   * Figma refs
   *  - 51:8129 / 51:8144 / 51:8145 — desktop open state. TWO angled polaroids:
   *    LEFT smaller (~247×290) tilted +7.24° clockwise; RIGHT larger (~327×384)
   *    tilted -5.99° counter-clockwise.
   *  - 51:9225 — mobile closed state (vertical accordion list with +/- icons).
   *  - 51:9312 / 51:9352 — mobile open state. ONE flat landscape image
   *    (~382×274, rounded-[10px], no tilt, no polaroid framing) under the body.
   *
   * Markup notes
   *  - The body panel is height-animated via the CSS grid-rows-[0fr→1fr] trick
   *    inside an `overflow-hidden` shell so opening doesn't jump the page.
   *  - Desktop images live OUTSIDE that shell as siblings of the panel inside
   *    an `lg:relative` anchor; `lg:contents` collapses the wrapper so each
   *    absolute child anchors directly to the anchor and can overflow the
   *    central column without being clipped.
   *  - Mobile renders a single image in normal flow under the body (Figma
   *    deliberately drops the polaroid framing + the second image at narrow
   *    widths). We prefer the right/larger asset and fall back to the left so
   *    rows with only one image slot filled still render.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $headingTag = Component::headingTagFromComponent($c, 'tis_heading_level', 2);

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
          $ctaLabel = trim((string) ($row['item_cta_label'] ?? ''));
          $ctaUrl = trim((string) ($row['item_cta_url'] ?? ''));
          $ctaNewTab = ! empty($row['item_cta_new_tab']);
          $items[] = [
              'label' => $label,
              'anchor_id' => PageSectionAnchor::fromHeading($label),
              'body_html' => $bodyHtml,
              'cta_label' => $ctaLabel,
              'cta_url' => $ctaUrl,
              'cta_new_tab' => $ctaNewTab,
              'image_left' => $left,
              'image_right' => $right,
              // Figma 51:8144 (right polaroid, -5.99°) and 51:8145 (left polaroid, +7.24°).
              // Defaults mirror Figma: left tilts clockwise, right tilts counter-clockwise.
              'tilt_left' => isset($row['item_image_left_tilt']) ? (int) $row['item_image_left_tilt'] : 7,
              'tilt_right' => isset($row['item_image_right_tilt']) ? (int) $row['item_image_right_tilt'] : -6,
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
    class="text-image-slider {{ esc_attr($root) }} relative text-deep-moss"
    data-component-root
    data-text-image-slider
    x-data='textImageSlider({{ $alpineConfig }})'>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      @if($heading !== '')
        {{-- Section H2: 64px desktop / 48px mobile (Component::sectionHeadingClasses). --}}
        <{{ $headingTag }} class="text-image-slider__heading {{ Component::sectionIntroHeadingClasses('text-deep-moss', Component::sectionHeadingToFollowContentGapClasses() . ' text-center') }}">
          {{ esc_html($heading) }}
        </{{ $headingTag }}>
      @endif

      <ul class="text-image-slider__list mx-auto flex w-full max-w-[382px] flex-col gap-0 lg:max-w-[634px]" role="list">
        @foreach($items as $i => $item)
          @php
              $labelId = $instanceId . '-l-' . $i;
              $panelId = $instanceId . '-p-' . $i;
              $isOpen = in_array($i, $defaultOpen, true);
              $hasItemCta = $item['cta_label'] !== '' && $item['cta_url'] !== '';
              $hasLeftImage = $item['image_left'] !== null
                  && trim((string) ($item['image_left']['url'] ?? '')) !== '';
              $hasRightImage = $item['image_right'] !== null
                  && trim((string) ($item['image_right']['url'] ?? '')) !== '';
          @endphp
          <li
            id="{{ esc_attr($item['anchor_id']) }}"
            class="text-image-slider__item relative w-full {{ PageSectionAnchor::scrollMarginClass() }}"
            data-tis-item="{{ $i }}">
            <button
              id="{{ esc_attr($labelId) }}"
              type="button"
              class="text-image-slider__label group flex w-full cursor-pointer items-center justify-between gap-4 py-6 text-left font-heading text-3xl leading-[1.1] text-faded-olive transition-colors duration-300 ease-out culvers-focus-ring lg:justify-center lg:py-3 lg:text-center lg:text-5xl lg:text-black md:text-6xl lg:text-7xl"
              data-tis-label
              aria-controls="{{ esc_attr($panelId) }}"
              aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
              x-on:click="toggle({{ $i }})"
              x-on:keydown.down.prevent="focusSibling({{ $i }}, 1)"
              x-on:keydown.up.prevent="focusSibling({{ $i }}, -1)">
              <span class="min-w-0 flex-1 lg:flex-none">{{ esc_html($item['label']) }}</span>
              {{-- Figma 51:9225 / 51:9312 — 16px +/- on mobile only. --}}
              <span
                class="relative flex size-4 shrink-0 items-center justify-center text-faded-olive lg:hidden"
                aria-hidden="true">
                <span class="absolute inset-x-0 top-1/2 h-[2px] -translate-y-1/2 bg-current"></span>
                <span
                  class="absolute inset-y-0 left-1/2 w-[2px] -translate-x-1/2 bg-current transition-transform duration-200 ease-out"
                  x-bind:class="isOpen({{ $i }}) ? 'scale-y-0' : 'scale-y-100'"></span>
              </span>
            </button>

            {{-- Wrapper that owns the vertical anchor for the desktop side images.
                 The grid-rows height animation lives on `.text-image-slider__panel`
                 and its `panel-inner` overflow-hidden child clips the body when
                 collapsed. The desktop images sit OUTSIDE that clipping shell —
                 as siblings of the panel inside this `lg:relative` wrapper — so
                 their negative `left`/`right` offsets can overflow the central
                 column without being clipped. Vertical centering is anchored to
                 this wrapper, which matches the panel's height while open. --}}
            <div class="text-image-slider__anchor lg:relative">
              <div
                id="{{ esc_attr($panelId) }}"
                role="region"
                aria-labelledby="{{ esc_attr($labelId) }}"
                class="text-image-slider__panel grid transition-[grid-template-rows] duration-300 ease-out motion-reduce:transition-none data-[open=true]:grid-rows-[1fr] grid-rows-[0fr]"
                data-open="{{ $isOpen ? 'true' : 'false' }}"
                @if (! $isOpen) inert @endif
                x-bind:inert="!isOpen({{ $i }})">
                <div class="text-image-slider__panel-inner overflow-hidden">
                  <div
                    class="text-image-slider__body mx-auto max-w-[44rem] px-2 text-left font-sans text-base font-light leading-7 text-deep-moss/90 lg:text-center lg:text-lg rt-link-faded [&_p+p]:mt-3 [&_strong]:font-medium {{ $hasItemCta ? 'pt-6 lg:pt-10' : 'py-6 lg:py-10' }}"
                    data-tis-body>
                    {!! $item['body_html'] !!}
                  </div>
                  @if($hasItemCta)
                    <div class="text-image-slider__cta mx-auto mt-6 flex max-w-[44rem] justify-start px-2 pb-6 lg:justify-center lg:pb-10">
                      @include('components.button', [
                          'label' => $item['cta_label'],
                          'href' => $item['cta_url'],
                          'attributes' => ! empty($item['cta_new_tab'])
                              ? ['target' => '_blank', 'rel' => 'noopener noreferrer']
                              : [],
                      ])
                    </div>
                  @endif
                </div>
              </div>

              @if($hasLeftImage || $hasRightImage)
                {{-- Desktop-only positioned images. `lg:contents` collapses the
                     wrapper so each absolute child anchors directly to the
                     `lg:relative` parent above (the anchor) — no clipping.
                     Visibility is driven by GSAP on `[data-tis-media-motion]`, not
                     x-show, so polaroids can bounce in/out when rows switch. --}}
                <div class="hidden lg:contents">
                  @if($hasLeftImage)
                    {{-- Figma 51:8145 — LEFT polaroid is the smaller of the pair
                         (~247×290 in the 1500px frame), tilted +7.24° clockwise. --}}
                    <div
                      class="text-image-slider__media text-image-slider__media--left pointer-events-none absolute{{ $isOpen ? ' is-active' : '' }}"
                      data-tis-media="left">
                      <div
                        class="text-image-slider__media-motion"
                        data-tis-media-motion
                        @if($isOpen) style="opacity:1;visibility:visible" @endif>
                        <div
                          class="text-image-slider__polaroid relative aspect-[4/5] w-full overflow-hidden rounded-[6px]"
                          data-tis-polaroid
                          style="transform: rotate({{ (int) $item['tilt_left'] }}deg)">
                          {!! Image::render($item['image_left'], [
                              'class' => 'absolute inset-0 size-full object-cover',
                              'alt' => '',
                              'role' => 'presentation',
                          ]) !!}
                        </div>
                      </div>
                    </div>
                  @endif

                  @if($hasRightImage)
                    {{-- Figma 51:8144 — RIGHT polaroid is the larger of the pair
                         (~327×384 in the 1500px frame), tilted -5.99° CCW. --}}
                    <div
                      class="text-image-slider__media text-image-slider__media--right pointer-events-none absolute{{ $isOpen ? ' is-active' : '' }}"
                      data-tis-media="right">
                      <div
                        class="text-image-slider__media-motion"
                        data-tis-media-motion
                        @if($isOpen) style="opacity:1;visibility:visible" @endif>
                        <div
                          class="text-image-slider__polaroid relative aspect-[4/5] w-full overflow-hidden rounded-[6px]"
                          data-tis-polaroid
                          style="transform: rotate({{ (int) $item['tilt_right'] }}deg)">
                          {!! Image::render($item['image_right'], [
                              'class' => 'absolute inset-0 size-full object-cover',
                              'alt' => '',
                              'role' => 'presentation',
                          ]) !!}
                        </div>
                      </div>
                    </div>
                  @endif
                </div>
              @endif
            </div>

            @if($hasLeftImage || $hasRightImage)
              {{-- Mobile-only image: Figma 51:9352 places a single landscape photo
                   (~382×274, 10px corner radius, no tilt, no polaroid framing)
                   under the body + CTA. We prefer the right/larger asset, falling
                   back to the left, so authors who only fill one slot still see it. --}}
              @php
                  $mobileImage = $hasRightImage ? $item['image_right'] : $item['image_left'];
                  $mobileTisHandle = $hasRightImage ? 'right-mobile' : 'left-mobile';
              @endphp
              <div
                class="text-image-slider__media text-image-slider__media--mobile mt-2 mb-8 w-full lg:hidden{{ $isOpen ? ' is-active' : '' }}"
                data-tis-media="{{ $mobileTisHandle }}">
                <div
                  class="text-image-slider__media-motion"
                  data-tis-media-motion
                  @if($isOpen) style="opacity:1;visibility:visible" @endif>
                  <div
                    class="text-image-slider__polaroid relative aspect-[7/5] w-full overflow-hidden rounded-[10px]"
                    data-tis-polaroid>
                    {!! Image::render($mobileImage, [
                        'class' => 'absolute inset-0 size-full object-cover',
                        'alt' => '',
                        'role' => 'presentation',
                    ]) !!}
                  </div>
                </div>
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
