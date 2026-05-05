@php
  use App\Helpers\Padding;
  use App\Helpers\TailwindColors;

  $c = is_array($component ?? null) ? $component : [];
  $padding = Padding::getClasses($c);
  $grid = $c['_grid_classes'] ?? '';
  $tone = TailwindColors::sanitizeBodyTextTone(
      $c['body_text_tone'] ?? TailwindColors::DEFAULT_LIGHT_BAND_BODY_TEXT_TONE
  );

  $heading = trim((string) ($c['heading'] ?? ''));
  $level = isset($c['heading_semantic_level']) ? (int) $c['heading_semantic_level'] : 2;
  if ($level < 2 || $level > 4) {
      $level = 2;
  }
  $headingTag = 'h' . $level;

  $subheading = trim((string) ($c['subheading'] ?? ''));
  $body = (string) ($c['body'] ?? '');

  $ctaLabel = trim((string) ($c['info_cta_label'] ?? ''));
  $ctaUrl = trim((string) ($c['info_cta_url'] ?? ''));

  $rowsRaw = $c['info_items'] ?? [];
  $rows = is_array($rowsRaw) ? $rowsRaw : [];

  $cells = [];
  foreach ($rows as $row) {
      if (! is_array($row)) {
          continue;
      }
      $title = trim((string) ($row['item_heading'] ?? ''));
      $desc = trim((string) ($row['item_description'] ?? ''));
      $img = isset($row['item_image']) && is_array($row['item_image']) ? $row['item_image'] : null;
      $imgUrl = isset($img['url']) ? trim((string) $img['url']) : '';
      if ($title === '') {
          continue;
      }
      $cells[] = [
          'title' => $title,
          'description' => $desc,
          'image' => $imgUrl !== '' ? $img : null,
      ];
  }

  $hasIntro =
      $heading !== ''
      || $subheading !== ''
      || trim(strip_tags($body)) !== ''
      || ($ctaLabel !== '' && $ctaUrl !== '');
  $hasGrid = $cells !== [];
  $showPlaceholder =
      ! $hasIntro && ! $hasGrid && current_user_can('edit_posts');
@endphp

@if($hasIntro || $hasGrid)
  <section
    class="{{ esc_attr(trim($grid . ' ' . $padding)) }} relative bg-off-white text-deep-moss"
    data-component-root
    data-info-block>
    {{-- Large faint X motif (matches Figma hero texture feel without competing with cells). --}}
    <div
      class="pointer-events-none absolute inset-0 z-0 opacity-[0.04]"
      aria-hidden="true"
      style="
        background-image:
          linear-gradient(135deg, rgb(46 48 30 / 0.55) 12%, transparent 12.5%, transparent 50%, rgb(46 48 30 / 0.35) 50.5%, rgb(46 48 30 / 0.35) 62%, transparent 62.5%, transparent),
          linear-gradient(45deg, transparent 38%, rgb(46 48 30 / 0.25) 38.5%, rgb(46 48 30 / 0.25) 50%, transparent 50.5%);
        background-size: 56px 56px;
      "></div>

    {{-- Match three-card / horizontal-scroller: 1440 shell, ~1272px content row --}}
    <div class="relative z-[1] mx-auto w-full max-w-[1440px] px-0">
      @if($hasIntro)
        <header class="mx-auto max-w-[52rem] px-5 text-center sm:px-6 lg:px-8">
          @if($heading !== '')
            <{{ $headingTag }} class="font-heading text-4xl tracking-tight text-deep-moss md:text-5xl lg:text-[3rem] lg:leading-[1.15]">
              {{ esc_html($heading) }}
            </{{ $headingTag }}>
          @endif
          @if($subheading !== '')
            <p class="mt-4 font-sans text-sm leading-relaxed text-deep-moss/85 md:text-base">
              {!! nl2br(e($subheading)) !!}
            </p>
          @endif
          @if(trim(strip_tags($body)) !== '')
            <div
              class="info-block__body prose prose-lg mx-auto mt-6 max-w-none text-left md:text-center text-deep-moss prose-headings:text-deep-moss prose-p:text-deep-moss prose-li:text-deep-moss prose-strong:text-deep-moss [&_a]:text-deep-moss [&_a]:underline [&_a]:decoration-glowleaf [&_a]:underline-offset-4 hover:[&_a]:decoration-deep-moss {{ esc_attr($tone) }}">
              {!! $body !!}
            </div>
          @endif
          @if($ctaLabel !== '' && $ctaUrl !== '')
            <div class="mt-8 flex justify-center md:mt-10">
              <a class="btn btn-primary px-10 py-3 md:px-12" href="{{ esc_url($ctaUrl) }}">
                {{ esc_html($ctaLabel) }}
              </a>
            </div>
          @elseif($ctaLabel !== '' && $ctaUrl === '' && current_user_can('edit_posts'))
            <p class="mt-6 font-sans text-sm text-deep-moss/60">
              {{ __('Add a CTA URL to show the button.', 'culvers') }}
            </p>
          @endif
        </header>
      @endif

      @if($hasGrid)
        <div
          class="{{ $hasIntro ? 'mt-12 md:mt-16' : '' }} mx-auto grid w-full max-w-[1272px] grid-cols-1 gap-px bg-deep-moss/15 md:grid-cols-2 lg:grid-cols-4">
          @foreach($cells as $cell)
            <article
              class="flex aspect-square flex-col items-center justify-center gap-4 bg-off-white px-5 py-8 text-center sm:px-8 sm:py-10 lg:px-10">
              @if($cell['image'] !== null)
                @php $im = $cell['image']; @endphp
                <div class="flex h-[7rem] w-full shrink-0 items-center justify-center sm:h-[8rem]">
                  <img
                    src="{{ esc_url((string) ($im['url'] ?? '')) }}"
                    alt="{{ esc_attr(trim((string) ($im['alt'] ?? ''))) }}"
                    class="max-h-full max-w-[min(100%,12rem)] object-contain text-deep-moss sm:max-w-[min(100%,13rem)]"
                    loading="lazy"
                    decoding="async"
                    @if(isset($im['width'])) width="{{ (int) $im['width'] }}" @endif
                    @if(isset($im['height'])) height="{{ (int) $im['height'] }}" @endif />
                </div>
              @endif
              <h3 class="font-heading text-xl leading-snug text-deep-moss md:text-2xl">
                {{ esc_html($cell['title']) }}
              </h3>
              @if($cell['description'] !== '')
                <p
                  class="mt-2 max-w-[min(100%,18rem)] font-sans text-micro uppercase leading-relaxed tracking-label text-deep-moss/80 md:max-w-[min(100%,22rem)] md:text-xs">
                  {!! nl2br(e($cell['description'])) !!}
                </p>
              @endif
            </article>
          @endforeach
        </div>
      @endif
    </div>
  </section>
@elseif($showPlaceholder)
  <div class="{{ esc_attr(trim($grid . ' ' . $padding)) }} rounded border border-amber-400 bg-amber-50 px-4 py-3 text-amber-950">
    {{ __('Add heading or info cells to this block.', 'culvers') }}
  </div>
@endif
