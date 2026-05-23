@php
  use App\Helpers\Component;
  use App\Helpers\ThreeCardBlock;

  /**
   * Three card block — directory CPT or blog category tabs (1–3 cells per tab).
   * Card title, image, and link always come from the selected posts.
   */

  $c = is_array($component ?? null) ? $component : [];
  $c = ThreeCardBlock::applyEditorFallback($c);
  $root = Component::rootClasses($c);
  $headingTag = Component::headingTagFromComponent($c, 'cards_heading_level', 2);

  $tabs = ThreeCardBlock::buildTabPanels($c);
  $showTabs = count($tabs) > 1;

  $heading = trim((string) ($c['cards_heading'] ?? ''));
  $sub = trim((string) ($c['cards_subheading'] ?? ''));
  $body = (string) ($c['cards_body'] ?? '');

  /* Resolve via helper so CPT mode auto-targets the chosen archive URL
     (e.g. Latest Events strip on /whats-on/ links to /latest-events/
     with no editor wiring). */
  $viewAllUrl = ThreeCardBlock::viewAllUrl($c);
  $mediaOverlayOpacity = ThreeCardBlock::mediaOverlayOpacity($c);
  $viewAllLabel = trim((string) ($c['cards_view_all_label'] ?? ''));
  if ($viewAllLabel === '') {
      $viewAllLabel = __('View all', 'culvers');
  }

  $hasIntro = $heading !== '' || $sub !== '' || trim(strip_tags($body)) !== '';
  $mobilePromoStack = ThreeCardBlock::usesMobilePromoStack($c);
  $mobilePromoOverlay = max($mediaOverlayOpacity, 30);
  $hasCards = false;
  foreach ($tabs as $tab) {
      $tabCards = $tab['cards'] ?? [];
      if (is_array($tabCards) && $tabCards !== []) {
          $hasCards = true;
          break;
      }
  }
@endphp

@if(! $hasIntro && ! $hasCards)
  @if(current_user_can('edit_posts'))
    @include('partials.component-editor-placeholder', [
        'wrapperClasses' => $root,
        'message' => __('Add cards or a heading to this block.', 'culvers'),
    ])
  @endif
@else
<section
  class="three-card-block {{ esc_attr($root) }} relative z-20 text-deep-moss"
  data-component-root
  data-three-card-block
  x-data="threeCardBlock()">
  <div class="mx-auto w-full max-w-8xl px-3 sm:px-4 md:px-5 lg:px-6">
    @if($heading !== '' || $sub !== '' || $body !== '')
      <header class="mx-auto max-w-[52rem] text-center">
        @if($heading !== '')
          {{-- Section H2: 58px desktop / 48px mobile (Component::sectionHeadingClasses). --}}
          <{{ $headingTag }} class="{{ Component::sectionHeadingClasses('text-faded-olive') }}">
            {{ esc_html($heading) }}
          </{{ $headingTag }}>
        @endif

        @if($sub !== '')
          <p class="mt-4 font-sans text-xs uppercase tracking-widest text-faded-olive md:text-xs">
            {{ esc_html($sub) }}
          </p>
        @endif

        @if($body !== '')
          {{-- Desktop spec preserved (Halyard Light 20 / lh 26 = text-xl leading-[1.3]).
               Figma 51:8212 mobile spec (Halyard Book 14 / lh 20) lands via `max-sm:`
               only — tablet 640-767 stays at text-xl, matching the pre-mobile-audit build.
               Prose plugin defaults force 18px so we render the body with explicit
               utilities — keeps prose for rich text elsewhere intact. --}}
          <div
            class="three-card-block__intro mx-auto mt-6 max-w-[36.75rem] text-center font-sans text-xl font-light leading-[1.3] text-deep-moss max-sm:text-sm max-sm:leading-5 [&_p+p]:mt-4 [&_strong]:font-medium rt-link-olive-surface">
            {!! $body !!}
          </div>
        @endif
      </header>
    @endif

    @if($showTabs)
      {{-- Filter chips — Figma 51:5133/5134/5135. Typography matches `.btn` (13px label pill). --}}
      <div
        class="mt-6 flex flex-nowrap items-center justify-center gap-1.5 max-sm:overflow-x-auto max-sm:pb-1 md:mt-8 md:flex-wrap md:gap-4"
        role="tablist"
        aria-label="{{ esc_attr__('Filter stories', 'culvers') }}"
        x-on:keydown.right.prevent="selectTab((activeTab + 1) % {{ count($tabs) }}, true)"
        x-on:keydown.left.prevent="selectTab((activeTab - 1 + {{ count($tabs) }}) % {{ count($tabs) }}, true)"
        x-on:keydown.home.prevent="selectTab(0, true)"
        x-on:keydown.end.prevent="selectTab({{ count($tabs) - 1 }}, true)">
        @foreach($tabs as $index => $tab)
          @php $tid = 'three-card-tab-' . $index; $pid = 'three-card-panel-' . $index; @endphp
          {{-- Visual state is driven entirely by `aria-selected` (bound below by
               Alpine). The `aria-[selected=true]:*` variants compile to selectors
               like `.foo[aria-selected="true"] { … }`, which beats the static
               inactive utilities on specificity — so we don't need a class-toggle
               and there's no glowleaf flash before Alpine hydrates. --}}
          <button
            type="button"
            class="btn btn-filter-tab btn-outline shrink-0 cursor-pointer border-dustleaf bg-transparent text-dustleaf hover:bg-light-cream/60 hover:text-dustleaf aria-[selected=true]:border-glowleaf aria-[selected=true]:bg-glowleaf aria-[selected=true]:text-deep-moss aria-[selected=true]:hover:border-glowleaf aria-[selected=true]:hover:bg-glowleaf aria-[selected=true]:hover:text-deep-moss"
            id="{{ esc_attr($tid) }}"
            role="tab"
            aria-controls="{{ esc_attr($pid) }}"
            aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
            x-bind:aria-selected="activeTab === {{ $index }} ? 'true' : 'false'"
            x-bind:tabindex="activeTab === {{ $index }} ? 0 : -1"
            x-on:click="selectTab({{ $index }})">
            {{ esc_html($tab['label']) }}
          </button>
        @endforeach
      </div>
    @endif

    {{--
      Panels share one grid cell. A dual opacity cross-fade stacks the incoming panel above the
      outgoing one while the new panel is still at opacity 0 → the old cards remain visible underneath.
      Use an instantaneous leave plus a eased enter-only fade; `isolate` separates stacking contexts.
      `overflow-visible`: card hover scales (`scale-[1.03]`) must not be clipped away from rounded
      corners (`overflow-hidden` here previously cut the zoom off inside the panels box).
      Top margin stays on this wrapper only.
    --}}
    <div
      class="three-card-block__panels relative isolate mt-10 grid grid-cols-1 overflow-visible md:mt-14 [&>.three-card-block__panel]:col-start-1 [&>.three-card-block__panel]:row-start-1 [&>.three-card-block__panel]:w-full [&>.three-card-block__panel]:min-w-0">
      @foreach($tabs as $index => $tab)
      <div
        class="three-card-block__panel"
        x-show="activeTab === {{ $index }}"
        x-transition:enter="transition ease-out duration-300 motion-reduce:transition-none motion-reduce:duration-0"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-0"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        id="three-card-panel-{{ $index }}"
        role="tabpanel"
        tabindex="0"
        @if($showTabs) aria-labelledby="{{ 'three-card-tab-' . $index }}" @endif>
        @php $cards = $tab['cards'] ?? []; @endphp
        @if($cards !== [])
          @if($mobilePromoStack)
            {{-- Figma `51:8214` — stacked landscape promo tiles (Fun for the whole family). --}}
            <div class="three-card-block__mobile-promo-stack flex flex-col gap-4 sm:hidden">
              @foreach($cards as $card)
                @include('partials.three-card-block-card', [
                  'card' => $card,
                  'cardAspectClass' => 'aspect-[398/230]',
                  'isManualMode' => false,
                  'showMobileArrow' => true,
                  'mobileArrowLayout' => 'inline',
                  'mediaOverlayOpacity' => $mobilePromoOverlay,
                ])
              @endforeach
            </div>
          @else
            {{-- Mobile: portrait Splide (`51:8345`). Tablet/desktop: static three-up grid. --}}
            <div
              class="three-card-block__splide splide culvers-splide-dots culvers-splide-dots--pagination-mt-5 sm:hidden"
              data-three-card-splide
              role="region"
              aria-label="{{ esc_attr__('Featured stories', 'culvers') }}">
              <div class="splide__track overflow-visible">
                <ul class="splide__list">
                  @foreach($cards as $card)
                    <li class="splide__slide">
                      @include('partials.three-card-block-card', [
                        'card' => $card,
                        'cardAspectClass' => 'aspect-[2/3]',
                        'isManualMode' => false,
                        'showMobileArrow' => true,
                        'mobileArrowLayout' => 'stack',
                        'mediaOverlayOpacity' => $mediaOverlayOpacity,
                      ])
                    </li>
                  @endforeach
                </ul>
              </div>
            </div>
          @endif

          <div
            class="three-card-block__grid three-card-block__grid--desktop mx-auto hidden w-full max-w-7xl grid-cols-2 gap-4 sm:grid lg:grid-cols-3">
            @foreach($cards as $card)
              @include('partials.three-card-block-card', [
                'card' => $card,
                'cardAspectClass' => 'aspect-[2/3]',
                'isManualMode' => false,
                'showMobileArrow' => false,
                'mediaOverlayOpacity' => $mediaOverlayOpacity,
              ])
            @endforeach
          </div>
        @endif
      </div>
    @endforeach
    </div>

    @if($viewAllUrl !== '')
      <div class="mt-12 flex justify-center md:mt-14">
        @include('components.button', ['label' => $viewAllLabel, 'href' => $viewAllUrl])
      </div>
    @endif
  </div>
</section>
@endif
