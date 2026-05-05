@php
  use App\Helpers\Padding;
  use App\Helpers\ThreeCardBlock;

  $c = ThreeCardBlock::applyEditorFallback($component ?? []);
  $padding = Padding::getClasses($c);
  $grid = $c['_grid_classes'] ?? '';

  $level = isset($c['block_heading_level']) ? (int) $c['block_heading_level'] : 2;
  if ($level < 1 || $level > 6) {
      $level = 2;
  }
  $headingTag = 'h' . $level;

  $tabs = ThreeCardBlock::buildTabPanels($c);
  $showTabs = count($tabs) > 1;

  $heading = trim((string) ($c['block_heading'] ?? ''));
  $sub = trim((string) ($c['block_subheading'] ?? ''));
  $body = (string) ($c['block_body'] ?? '');

  $source = (string) ($c['cards_source'] ?? 'manual');
  $viewAllUrl = trim((string) ($c['blog_view_all_url'] ?? ''));
  $viewAllLabel = trim((string) ($c['blog_view_all_label'] ?? ''));
  if ($viewAllLabel === '') {
      $viewAllLabel = __('View all', 'culvers');
  }
@endphp

<section
  class="{{ esc_attr(trim($grid . ' ' . $padding)) }} relative z-[20] text-deep-moss"
  data-component-root
  data-three-card-block
  x-data="threeCardBlock()">
  <div class="mx-auto w-full max-w-[1440px] px-0">
    @if($heading !== '' || $sub !== '' || $body !== '')
      <header class="mx-auto max-w-[52rem] px-1 text-center md:px-4">
        @if($heading !== '')
          <{{ $headingTag }} class="font-heading text-4xl leading-[1.12] tracking-tight text-deep-moss md:text-5xl lg:text-[3.25rem]">
            {{ esc_html($heading) }}
          </{{ $headingTag }}>
        @endif

        @if($sub !== '')
          <p class="mt-4 font-sans text-micro uppercase tracking-label text-deep-moss md:text-xs">
            {{ esc_html($sub) }}
          </p>
        @endif

        @if($body !== '')
          <div
            class="three-card-block__intro prose prose-neutral prose-lg mx-auto mt-6 max-w-[46rem] text-left md:text-center text-deep-moss prose-headings:text-deep-moss prose-p:text-deep-moss prose-li:text-deep-moss prose-strong:text-deep-moss [&_a]:text-deep-moss [&_a]:underline [&_a]:decoration-glowleaf [&_a]:underline-offset-4 hover:[&_a]:decoration-deep-moss">
            {!! $body !!}
          </div>
        @endif
      </header>
    @endif

    @if($showTabs)
      <div
        class="mt-10 flex flex-wrap items-center justify-center gap-3 md:mt-12 md:gap-4"
        role="tablist"
        aria-label="{{ esc_attr__('Filter stories', 'culvers') }}">
        @foreach($tabs as $index => $tab)
          @php $tid = 'three-card-tab-' . $index; @endphp
          <button
            type="button"
            class="three-card-block__tab rounded-full border border-deep-moss px-5 py-2 font-sans text-micro font-semibold uppercase tracking-label text-deep-moss transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-deep-moss md:px-7 md:py-2.5 md:text-xs"
            id="{{ esc_attr($tid) }}"
            role="tab"
            aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
            tabindex="{{ $index === 0 ? '0' : '-1' }}"
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
        role="tabpanel"
        @if($showTabs) aria-labelledby="{{ 'three-card-tab-' . $index }}" @endif>
        @php $cards = $tab['cards'] ?? []; @endphp
        @if($cards !== [])
          {{--
            Figma (three-up strip): ~28px gutters, ~14–16px card radius, portrait tiles slightly taller than 3:4 (use 2:3).
            Row width aligns with main 1440 inner (~1272px content) so proportions match design, not isolated tokens on each card.
          --}}
          <div
            class="three-card-block__grid mx-auto grid w-full max-w-[1272px] grid-cols-1 gap-7 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($cards as $card)
              @php
                $href = trim((string) ($card['url'] ?? ''));
                $title = trim((string) ($card['title'] ?? ''));
                $mediaType = (string) ($card['media_type'] ?? 'image');
                $video = isset($card['video']) && is_array($card['video']) ? $card['video'] : [];
                $videoUrl = isset($video['url']) ? (string) $video['url'] : '';
                $mime = isset($video['mime_type']) ? (string) $video['mime_type'] : 'video/mp4';
                $poster = isset($card['poster']) && is_array($card['poster']) ? $card['poster'] : [];
                $posterUrl = isset($poster['url']) ? (string) $poster['url'] : '';
                $image = isset($card['image']) && is_array($card['image']) ? $card['image'] : [];
                $imageUrl = isset($image['url']) ? (string) $image['url'] : '';
                $alt = trim((string) ($card['alt'] ?? $title));
              @endphp
              @if($href !== '' && $title !== '')
                <a
                  href="{{ esc_url($href) }}"
                  class="three-card-block__card group/card relative flex aspect-[2/3] w-full origin-center overflow-hidden rounded-[16px] outline-none motion-safe:transition-transform motion-safe:duration-300 motion-safe:ease-out motion-safe:hover:scale-[1.03] motion-safe:focus-visible:scale-[1.03] motion-reduce:hover:scale-100 motion-reduce:focus-visible:scale-100 focus-visible:ring-2 focus-visible:ring-glowleaf focus-visible:ring-offset-2 focus-visible:ring-offset-light-cream">
                  <span
                    class="pointer-events-none absolute inset-0 z-0 overflow-hidden rounded-[inherit]"
                    data-background-parallax-trigger
                    aria-hidden="true">
                    @if($mediaType === 'video' && $videoUrl !== '')
                      <span class="relative z-0 block h-full min-h-0 w-full" data-background-parallax-image="1">
                        <video
                          class="three-card-block__media absolute inset-0 h-full w-full object-cover motion-safe:transition-transform motion-safe:duration-700 motion-safe:ease-out motion-safe:group-hover/card:scale-[1.08] motion-safe:group-focus-within/card:scale-[1.08] motion-reduce:group-hover/card:scale-100 motion-reduce:group-focus-within/card:scale-100"
                          data-three-card-video
                          data-needs-frame-poster="{{ $posterUrl === '' ? '1' : '0' }}"
                          data-gsap-autoplay="off"
                          muted
                          playsinline
                          preload="{{ $posterUrl !== '' ? 'none' : 'auto' }}"
                          @if($posterUrl !== '') poster="{{ esc_url($posterUrl) }}" @endif>
                          <source src="{{ esc_url($videoUrl) }}" type="{{ esc_attr($mime) }}" />
                        </video>
                      </span>
                    @elseif($imageUrl !== '')
                      <span class="relative z-0 block h-full min-h-0 w-full" data-background-parallax-image="1">
                        <img
                          class="three-card-block__media absolute inset-0 h-full w-full object-cover motion-safe:transition-transform motion-safe:duration-700 motion-safe:ease-out motion-safe:group-hover/card:scale-[1.08] motion-safe:group-focus-within/card:scale-[1.08] motion-reduce:group-hover/card:scale-100 motion-reduce:group-focus-within/card:scale-100"
                          src="{{ esc_url($imageUrl) }}"
                          alt="{{ esc_attr($alt) }}"
                          loading="lazy"
                          decoding="async"
                          width="800"
                          height="1200" />
                      </span>
                    @else
                      <span class="block h-full w-full bg-gradient-to-br from-dustleaf/40 via-deep-moss/25 to-faded-olive/35"></span>
                    @endif

                    <span
                      class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-t from-black/72 via-black/25 to-transparent"></span>
                  </span>

                  <span
                    class="relative z-10 flex w-full flex-1 items-center justify-center px-6 py-10 text-center font-heading text-2xl leading-snug text-white md:text-3xl lg:text-[2rem]">
                    {{ esc_html($title) }}
                  </span>
                </a>
              @endif
            @endforeach
          </div>
        @endif
      </div>
    @endforeach

    @if($source === 'blog' && $viewAllUrl !== '')
      <div class="mt-12 flex justify-center md:mt-14">
        <a class="btn btn-primary" href="{{ esc_url($viewAllUrl) }}">{{ esc_html($viewAllLabel) }}</a>
      </div>
    @endif
  </div>
</section>
