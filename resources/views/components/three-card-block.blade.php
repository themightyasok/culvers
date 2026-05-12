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
          {{-- Figma: short intro copy = Halyard Display Light 20px / lh 26 (text-xl).
               Prose plugin defaults force 18px so we render the body with explicit
               utilities — keeps prose for rich text elsewhere intact. --}}
          <div
            class="three-card-block__intro mx-auto mt-6 max-w-[36.75rem] text-left font-sans text-xl font-light leading-[1.3] text-deep-moss md:text-center [&_p+p]:mt-4 [&_strong]:font-medium rt-link-olive-surface">
            {!! $body !!}
          </div>
        @endif
      </header>
    @endif

    @if($showTabs)
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
          <button
            type="button"
            class="three-card-block__tab rounded-full border border-deep-moss px-5 py-2 font-sans text-xs font-semibold uppercase tracking-widest text-deep-moss transition-colors duration-150 culvers-focus-ring-deep-moss md:px-7 md:py-2.5 md:text-xs"
            id="{{ esc_attr($tid) }}"
            role="tab"
            aria-controls="{{ esc_attr($pid) }}"
            x-bind:aria-selected="activeTab === {{ $index }} ? 'true' : 'false'"
            x-bind:tabindex="activeTab === {{ $index }} ? 0 : -1"
            x-bind:class="{ 'border-transparent bg-glowleaf text-deep-moss': activeTab === {{ $index }}, 'bg-transparent hover:bg-light-cream/60': activeTab !== {{ $index }} }"
            x-on:click="selectTab({{ $index }})">
            {{ esc_html($tab['label']) }}
          </button>
        @endforeach
      </div>
    @endif

    @foreach($tabs as $index => $tab)
      <div
        class="mt-10 md:mt-14"
        x-show="activeTab === {{ $index }}"
        x-cloak
        id="three-card-panel-{{ $index }}"
        role="tabpanel"
        tabindex="0"
        @if($showTabs) aria-labelledby="{{ 'three-card-tab-' . $index }}" @endif>
        @php $cards = $tab['cards'] ?? []; @endphp
        @if($cards !== [])
          {{--
            Figma (three-up strip): 1198px row, 16px column gutter, 11.43px corner radius,
            390×585 portrait card (3:2 aspect). Width sits ~1px tighter than the
            8xl shell so the row never wraps before lg.
          --}}
          <div
            class="three-card-block__grid mx-auto grid w-full max-w-[1198px] grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
              @if($href !== '' && $title !== '')
                <a
                  href="{{ esc_url($href) }}"
                  class="three-card-block__card group/card relative flex aspect-[2/3] w-full origin-center overflow-hidden rounded-[11px] outline-none motion-safe:transition-transform motion-safe:duration-300 motion-safe:ease-out motion-safe:hover:scale-[1.03] motion-safe:focus-visible:scale-[1.03] motion-reduce:hover:scale-100 motion-reduce:focus-visible:scale-100 focus-visible:ring-2 focus-visible:ring-glowleaf focus-visible:ring-offset-2 focus-visible:ring-offset-light-cream">
                  <span
                    class="pointer-events-none absolute inset-0 z-0 overflow-hidden rounded-[inherit]"
                    data-background-parallax-trigger
                    aria-hidden="true">
                    @if($mediaType === 'video' && $videoUrl !== '')
                      <span class="relative z-0 block h-full min-h-0 w-full" data-background-parallax-image="1">
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
                      <span class="relative z-0 block h-full min-h-0 w-full" data-background-parallax-image="1">
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

                  {{-- Figma: title is Canela 45.7px regardless of media type. --}}
                  <span
                    class="relative z-10 flex w-full flex-1 items-center justify-center px-6 py-10 text-center font-heading text-[46px] leading-none text-white">
                    {{ esc_html($title) }}
                  </span>
                </a>
              @endif
            @endforeach
          </div>
        @endif
      </div>
    @endforeach

    @if($viewAllUrl !== '')
      <div class="mt-12 flex justify-center md:mt-14">
        @include('components.button', ['label' => $viewAllLabel, 'href' => $viewAllUrl])
      </div>
    @endif
  </div>
</section>
@endif
