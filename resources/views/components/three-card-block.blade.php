@php
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;
  use App\Helpers\ThreeCardBlock;

  /**
   * Three card block — manual cards or category-tabbed blog cards (1–3 cells).
   * First-frame video preview swaps to playback on hover; reduced-motion safe.
   * Heading defaults to H2 but the editor can promote to H1 when used as the
   * page heading on a long-form landing.
   */

  $c = is_array($component ?? null) ? $component : [];
  $c = ThreeCardBlock::applyEditorFallback($c);
  $root = Component::rootClasses($c);
  $headingTag = Component::headingTag($c['cards_heading_level'] ?? null);

  $tabs = ThreeCardBlock::buildTabPanels($c);
  $showTabs = count($tabs) > 1;

  $heading = trim((string) ($c['cards_heading'] ?? ''));
  $sub = trim((string) ($c['cards_subheading'] ?? ''));
  $body = (string) ($c['cards_body'] ?? '');

  /* Figma `51:8214 / 8220 / 8226` (homepage mobile manual cards): landscape
     ≈1.73:1 with title + glowleaf arrow rendered inline. Manual mode is the
     only variant that reflows on mobile — blog / CPT carousels keep the
     portrait card so the swipable strip still reads as a stack. */
  $isManualMode = (($c['cards_source'] ?? 'manual') === 'manual');

  /* Resolve via helper so CPT mode auto-targets the chosen archive URL
     (e.g. Latest Events strip on /whats-on/ links to /latest-events/
     with no editor wiring). Manual + blog modes still honour the explicit URL. */
  $viewAllUrl = ThreeCardBlock::viewAllUrl($c);
  $viewAllLabel = trim((string) ($c['cards_view_all_label'] ?? ''));
  if ($viewAllLabel === '') {
      $viewAllLabel = __('View all', 'culvers');
  }

  $hasIntro = $heading !== '' || $sub !== '' || trim(strip_tags($body)) !== '';
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
  <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
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
            class="three-card-block__intro mx-auto mt-6 max-w-[36.75rem] text-left font-sans text-xl font-light leading-[1.3] text-deep-moss md:text-center max-sm:text-sm max-sm:leading-5 [&_p+p]:mt-4 [&_strong]:font-medium rt-link-olive-surface">
            {!! $body !!}
          </div>
        @endif
      </header>
    @endif

    @if($showTabs)
      {{-- Filter chips — Figma 51:5133/5134/5135.
           - Font: Commuters Sans SemiBold 12.887px (font-label text-[13px] font-semibold).
           - Tracking: 0.6443px on 12.887px ≈ 0.05em (tracking-[0.05em]).
           - Padding: 25.773px × 7.732px (px-[26px] py-[8px]).
           - Radius: 64.433px → rounded-full.
           - Active (51:5133): bg-glowleaf, text-deep-moss, NO border.
           - Inactive (51:5134/5135): 1.5px Dustleaf border + Dustleaf text on
             transparent fill. (Token: --color-dustleaf #8B8C67.) --}}
      <div
        class="mt-10 flex flex-wrap items-center justify-center gap-3 md:mt-12 md:gap-4"
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
            class="three-card-block__tab cursor-pointer rounded-full border-[1.5px] border-dustleaf bg-transparent px-[26px] py-[8px] font-label text-[13px] font-semibold uppercase leading-[1.85] tracking-[0.05em] text-dustleaf transition-colors duration-150 hover:bg-light-cream/60 aria-[selected=true]:border-transparent aria-[selected=true]:bg-glowleaf aria-[selected=true]:text-deep-moss aria-[selected=true]:hover:bg-glowleaf culvers-focus-ring-deep-moss"
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
          {{--
            Figma (three-up strip) artboard ~1198px; layout uses max-w-7xl (1280px) for the stock
            width ladder + parity with opening-hours. 16px column gutter, ~11px corner radius,
            390×585 portrait cards (3:2 aspect).
          --}}
          <div
            class="three-card-block__grid mx-auto grid w-full max-w-7xl grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($cards as $card)
              @php
                $href = trim((string) ($card['url'] ?? ''));
                $title = trim((string) ($card['title'] ?? ''));
                $mediaType = (string) ($card['media_type'] ?? 'image');
                $video = isset($card['video']) && is_array($card['video']) ? $card['video'] : [];
                $videoUrl = isset($video['url']) ? (string) $video['url'] : '';
                $mime = isset($video['mime_type']) ? (string) $video['mime_type'] : 'video/mp4';
                $image = isset($card['image']) && is_array($card['image']) ? $card['image'] : [];
                $imageUrl = isset($image['url']) ? (string) $image['url'] : '';
                $alt = trim((string) ($card['alt'] ?? $title));
              @endphp
              @php
                /* Defensive mobile-only override (Figma 51:8214 mobile manual cards).
                   The base aspect stays `2/3` — desktop is untouched even if a stale CSS
                   build is loaded — and `max-sm:aspect-[173/100]` only swaps in landscape
                   below 640 px when manual mode is active. */
                $cardAspectClass = $isManualMode
                    ? 'aspect-[2/3] max-sm:aspect-[173/100]'
                    : 'aspect-[2/3]';
              @endphp
              @if($href !== '' && $title !== '')
                <a
                  href="{{ esc_url($href) }}"
                  class="three-card-block__card group/card relative flex {{ $cardAspectClass }} w-full origin-center rounded-[11px] outline-none motion-safe:transition-transform motion-safe:duration-300 motion-safe:ease-out motion-safe:hover:scale-[1.03] motion-safe:focus-visible:scale-[1.03] motion-reduce:hover:scale-100 motion-reduce:focus-visible:scale-100 focus-visible:ring-2 focus-visible:ring-glowleaf focus-visible:ring-offset-2 focus-visible:ring-offset-light-cream">
                  <span
                    class="pointer-events-none absolute inset-0 z-0 overflow-hidden rounded-[inherit]"
                    aria-hidden="true">
                    @if($mediaType === 'video' && $videoUrl !== '')
                      <span class="relative z-0 block h-full min-h-0 w-full">
                        {{--
                          Idle state must show decoded frame 0 of the file (not an uploaded poster image).
                          Hover/focus plays the clip; mouseleave snaps back to frame 0 (see three-card-block.js).
                        --}}
                        <video
                          class="three-card-block__media absolute inset-0 h-full w-full object-cover motion-safe:transition-transform motion-safe:duration-700 motion-safe:ease-out motion-safe:group-hover/card:scale-[1.08] motion-safe:group-focus-within/card:scale-[1.08] motion-reduce:group-hover/card:scale-100 motion-reduce:group-focus-within/card:scale-100"
                          data-three-card-video
                          data-gsap-autoplay="off"
                          muted
                          playsinline
                          loop
                          preload="auto">
                          <source src="{{ esc_url($videoUrl) }}" type="{{ esc_attr($mime) }}" />
                        </video>
                      </span>
                    @elseif($imageUrl !== '')
                      <span class="relative z-0 block h-full min-h-0 w-full">
                        {!! Image::render($image, [
                            'class' => 'three-card-block__media absolute inset-0 h-full w-full object-cover motion-safe:transition-transform motion-safe:duration-700 motion-safe:ease-out motion-safe:group-hover/card:scale-[1.08] motion-safe:group-focus-within/card:scale-[1.08] motion-reduce:group-hover/card:scale-100 motion-reduce:group-focus-within/card:scale-100',
                            'alt' => $alt,
                            'width' => 800,
                            'height' => 1200,
                        ]) !!}
                      </span>
                    @else
                      <span class="block h-full w-full bg-gradient-to-br from-dustleaf/40 via-deep-moss/25 to-faded-olive/35"></span>
                    @endif

                    {{-- Figma uses a single ~25% black scrim, not a 3-stop gradient. --}}
                    <span
                      class="pointer-events-none absolute inset-0 z-10 bg-black/25"></span>
                  </span>

                  {{-- Sheet feedback row 11: hover state. Title turns Glowleaf and an "Explore"
                       pill button reveals below it (motion-safe only). Stack lives in a flex column
                       so the centred resting title and the post-hover button form one centred block.
                       Manual mode adds a `max-sm:`-scoped landscape reflow + inline glowleaf arrow
                       (Figma 51:8214/8220/8226) — desktop is untouched. --}}
                  <span
                    class="relative z-10 flex w-full flex-1 flex-col items-center justify-center gap-5 px-6 py-10 text-center {{ $isManualMode ? 'max-sm:flex-row max-sm:justify-between max-sm:gap-4 max-sm:px-7 max-sm:py-6 max-sm:text-left' : '' }}">
                    <span
                      class="font-heading text-[36px] leading-[1.1] text-white transition-colors duration-300 ease-out motion-safe:group-hover/card:text-glowleaf motion-safe:group-focus-within/card:text-glowleaf md:text-[46px] md:leading-none">
                      {{ esc_html($title) }}
                    </span>
                    @if($isManualMode)
                      {{-- Inline glowleaf arrow — `hidden` by default so desktop is untouched even
                           with a stale CSS cache, only shown below 640 px via `max-sm:inline-flex`.
                           Figma 51:8217 etc. (43 × 43 pill on mobile manual cards). --}}
                      <span
                        aria-hidden="true"
                        class="hidden size-[43px] shrink-0 items-center justify-center rounded-full bg-glowleaf text-deep-moss transition-transform duration-300 ease-out motion-safe:group-hover/card:scale-[1.06] motion-safe:group-focus-within/card:scale-[1.06] max-sm:inline-flex">
                        <svg class="size-4" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                          <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                      </span>
                    @endif
                    <span
                      aria-hidden="true"
                      class="inline-flex items-center justify-center rounded-full border border-glowleaf bg-transparent px-7 py-2 font-label text-[13px] font-semibold uppercase leading-[28px] tracking-[0.05em] text-glowleaf opacity-0 transition-opacity duration-300 ease-out motion-safe:group-hover/card:opacity-100 motion-safe:group-focus-within/card:opacity-100 motion-reduce:hidden {{ $isManualMode ? 'max-sm:hidden' : '' }}">
                      {{ __('Explore', 'culvers') }}
                    </span>
                  </span>
                </a>
              @endif
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
