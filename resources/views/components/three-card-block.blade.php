@php
  use App\Helpers\Component;
  use App\Helpers\LayoutShell;
  use App\Helpers\ThreeCardBlock;
  use App\Support\PageSectionAnchor;

  /**
   * Three card block — manual cards (image / video) or directory CPT / blog category tabs.
   */

  $c = is_array($component ?? null) ? $component : [];
  $c = ThreeCardBlock::applyEditorFallback($c);
  $root = Component::rootClasses($c);
  $headingTag = Component::headingTagFromComponent($c, 'cards_heading_level', 2);
  $isManualMode = ThreeCardBlock::isManualSource($c);

  $tabs = ThreeCardBlock::buildTabPanels($c);
  $showTabs = count($tabs) > 1;
  $defaultTabIndex = ThreeCardBlock::defaultTabIndex($tabs, $c);

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

  /* R8 — opt-in Shop / Eat & Drink CTAs (homepage "What are you looking for today?" strip).
     Per-instance toggle so other three_card_block rows are unaffected. */
  $showDirectoryButtons = ! empty($c['cards_show_directory_buttons']);

  $hasIntro = $heading !== '' || $sub !== '' || trim(strip_tags($body)) !== '';
  $sectionAnchorId = $heading !== '' ? PageSectionAnchor::fromHeading($heading) : '';
  $sectionAnchorAttr = $sectionAnchorId !== '' ? ' id="' . esc_attr($sectionAnchorId) . '"' : '';
  $sectionScrollMargin = $sectionAnchorId !== '' ? PageSectionAnchor::scrollMarginClass() : '';
  $hasIntroBody = trim(strip_tags($body)) !== '' || $sub !== '';
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

  $introStackTailGap = '';
  if ($hasIntro && ($showTabs || $hasCards)) {
      $introStackTailGap = $showTabs
          // Page Ruler measures ink→tab (~32px at md+), not the header box margin;
          // Canela line-height extends ~13px past the trimmed box → mb-[45px] at md+.
          ? 'mb-[37px] md:mb-[45px]'
          : ($hasIntroBody
              ? Component::sectionBodyToFollowContentGapClasses()
              : Component::sectionHeadingToFollowContentGapClasses());
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
  {!! $sectionAnchorAttr !!}
  class="three-card-block {{ esc_attr(trim($root . ' ' . $sectionScrollMargin)) }} relative z-20 text-deep-moss"
  data-component-root
  data-three-card-block
  data-default-tab="{{ (int) $defaultTabIndex }}"
  x-data="threeCardBlock()">
  <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
    @if($heading !== '' || $sub !== '' || $body !== '')
      @include('partials.section-intro-stack', [
          'headingTag' => $headingTag,
          'heading' => $heading,
          'headingClasses' => Component::sectionIntroHeadingClasses('text-faded-olive'),
          'subheading' => $sub,
          'subheadingClasses' => 'font-label text-xs uppercase tracking-widest text-faded-olive md:text-xs',
          'bodyHtml' => $body,
          'bodyClasses' => 'three-card-block__intro mx-auto max-w-[36.75rem] text-center font-sans text-xl font-light leading-[1.3] text-deep-moss max-sm:text-sm max-sm:leading-5 [&_p+p]:mt-4 [&_strong]:font-medium rt-link-olive-surface',
          'introStackIncludeCta' => false,
          'ctaLabel' => '',
          'ctaUrl' => '',
          'introStackTailGap' => $introStackTailGap,
          'wrapperClasses' => 'mx-auto max-w-[52rem] text-center',
      ])
    @endif

    @if($showTabs)
      {{-- Filter chips — Figma 51:5133/5134/5135. Typography matches `.btn` (13px label pill). --}}
      <div
        class="{{ esc_attr(trim('flex flex-nowrap items-center justify-center gap-1.5 max-sm:overflow-x-auto max-sm:pb-1 md:flex-wrap md:gap-4' . ($hasCards ? ' ' . Component::sectionControlsToFollowContentGapClasses() : ''))) }}"
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
          {{-- Hover treatment: `.btn-outline:hover` paints the pill glowleaf with deep-moss text;
               `.btn-filter-tab:hover` (resources/styles/app.css) re-pins padding so siblings don't
               shift and swaps the border to glowleaf. The widen effect is `hover:scale-105` (a
               transform, no layout impact). Active state is driven by `aria-[selected=true]:*`
               utilities (specificity 0,0,3,0) which beat the hover rule. --}}
          <button
            type="button"
            class="btn btn-filter-tab btn-outline shrink-0 cursor-pointer border-dustleaf bg-transparent text-dustleaf transition-[padding,background-color,color,border-color,transform] hover:scale-105 aria-[selected=true]:border-glowleaf aria-[selected=true]:bg-glowleaf aria-[selected=true]:text-deep-moss aria-[selected=true]:hover:border-glowleaf aria-[selected=true]:hover:bg-glowleaf aria-[selected=true]:hover:text-deep-moss"
            id="{{ esc_attr($tid) }}"
            role="tab"
            aria-controls="{{ esc_attr($pid) }}"
            aria-selected="{{ $index === $defaultTabIndex ? 'true' : 'false' }}"
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
      Spacing above cards: intro stack / tab row `mb-*` — never `mt-*` on this wrapper.
    --}}
    <div
      class="three-card-block__panels relative isolate grid grid-cols-1 overflow-visible [&>.three-card-block__panel]:col-start-1 [&>.three-card-block__panel]:row-start-1 [&>.three-card-block__panel]:w-full [&>.three-card-block__panel]:min-w-0">
      @foreach($tabs as $index => $tab)
      {{-- Do NOT use the HTML `hidden` attribute here. Alpine x-show toggles
           display via style, but [hidden]{display:none!important} in the UA
           stylesheet wins forever — so News/Events stayed blank after the
           Offers default tab painted. x-cloak covers non-default panels until
           Alpine boots; x-show owns visibility after that. --}}
      <div
        class="three-card-block__panel"
        x-show="activeTab === {{ $index }}"
        x-transition:enter="transition ease-out duration-300 motion-reduce:transition-none motion-reduce:duration-0"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-0"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @if($index !== $defaultTabIndex) x-cloak @endif
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
                  'isManualMode' => $isManualMode,
                  'showMobileArrow' => true,
                  'mobileArrowLayout' => 'inline',
                  'mediaOverlayOpacity' => $mobilePromoOverlay,
                  'videoAutoplay' => true,
                  'videoPreload' => 'auto',
                  'videoVariant' => 'mobile',
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
                        'isManualMode' => $isManualMode,
                        'showMobileArrow' => true,
                        'mobileArrowLayout' => 'stack',
                        'mediaOverlayOpacity' => $mediaOverlayOpacity,
                        'videoAutoplay' => true,
                        'videoPreload' => 'auto',
                        'videoVariant' => 'mobile',
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
                'isManualMode' => $isManualMode,
                'showMobileArrow' => false,
                'mediaOverlayOpacity' => $mediaOverlayOpacity,
                'videoAutoplay' => false,
                'videoVariant' => 'desktop',
                'videoPreload' => 'metadata',
              ])
            @endforeach
          </div>
        @endif
      </div>
    @endforeach
    </div>

    @if($showDirectoryButtons)
      <div class="{{ Component::sectionBodyToCtaGapClasses('flex flex-wrap justify-center gap-4') }}">
        @include('components.button', ['label' => __('Shop', 'culvers'), 'href' => home_url('/shops/')])
        @include('components.button', ['label' => __('Eat & Drink', 'culvers'), 'href' => home_url('/eat-drink/')])
      </div>
    @elseif($viewAllUrl !== '')
      <div class="{{ Component::sectionBodyToCtaGapClasses('flex justify-center') }}">
        @include('components.button', ['label' => $viewAllLabel, 'href' => $viewAllUrl])
      </div>
    @endif
  </div>
</section>
@endif
