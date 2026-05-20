@php
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;

  /**
   * Info block — intro stack (heading, subheading, body, optional CTA) +
   * up-to-16 square cells in a 4-column grid (1-col on mobile).
   * Sits flush on the page white — no coloured band, no decorative motif.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $tone = Component::bodyTextTone($c, 'light-band');
  $headingTag = Component::headingTagFromComponent($c, 'info_heading_level', 2);

  $heading = trim((string) ($c['info_heading'] ?? ''));
  $subheading = trim((string) ($c['info_subheading'] ?? ''));
  $body = (string) ($c['info_body'] ?? '');
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
@endphp

@if($hasIntro || $hasGrid)
  <section
    class="info-block {{ esc_attr($root) }} relative bg-lighter-cream py-12 text-deep-moss lg:py-16"
    data-component-root
    data-info-block>
    <div class="relative z-10 {{ LayoutShell::INNER_MAX_GUTTERED }}">
      @if($hasIntro)
        <header class="mx-auto max-w-[52rem] text-center">
          @if($heading !== '')
            {{-- Section H2: 64px desktop / 48px mobile (Component::sectionHeadingClasses). --}}
            <{{ $headingTag }} class="{{ Component::sectionHeadingClasses('text-faded-olive') }}">
              {{ esc_html($heading) }}
            </{{ $headingTag }}>
          @endif
          @if($subheading !== '')
            <p class="mt-4 font-sans text-xl font-light text-deep-moss md:text-2xl">
              {!! nl2br(e($subheading)) !!}
            </p>
          @endif
          @if(trim(strip_tags($body)) !== '')
            <div
              class="info-block__body prose prose-lg mx-auto mt-4 max-w-[36.75rem] text-center font-light text-deep-moss prose-headings:text-deep-moss prose-p:font-sans prose-p:text-xl prose-p:font-light prose-li:text-deep-moss prose-strong:text-deep-moss rt-link-prose {{ esc_attr($tone) }}">
              {!! $body !!}
            </div>
          @endif
          @if($ctaLabel !== '' && $ctaUrl !== '')
            <div class="mt-7 flex justify-center md:mt-9">
              {{-- Banner-scale CTA: `size=large` keeps the canonical Figma hover-widen
                   (40px → 56px on hover) instead of being killed by an inline `px-*`. --}}
              @include('components.button', [
                  'label' => $ctaLabel,
                  'href' => $ctaUrl,
                  'size' => 'large',
              ])
            </div>
          @elseif($ctaLabel !== '' && $ctaUrl === '' && current_user_can('edit_posts'))
            <p class="mt-6 font-sans text-base text-deep-moss/60">
              {{ __('Add a CTA URL to show the button.', 'culvers') }}
            </p>
          @endif
        </header>
      @endif

      @if($hasGrid)
        <div class="{{ $hasIntro ? 'mt-12 md:mt-14' : '' }} info-block__grid mx-auto w-full bg-lighter-cream">
          @foreach($cells as $cell)
            {{--
              Divider geometry still follows Figma `51:5066` metadata (`--info-block-rule-h-inset: 17px`, `--info-block-rule-v-end: 7%`).
              Cell interior: fixed **icon band** (7.125 rem, icons `object-bottom`) lines up **h3** baselines per row; **`mt-4` + `gap-1.5`** tightens headline↔sub; **`md/lg` fixed tile heights** (248/252 px) budgets space so the bottom rule doesn’t sit far under the label.
            --}}
            <article
              class="info-block__cell flex min-h-[220px] flex-col items-center bg-lighter-cream px-4 pt-5 text-center max-md:pb-14 sm:px-6 md:box-border md:h-[248px] md:max-h-[248px] md:min-h-[248px] md:justify-start md:px-8 md:pb-4 md:pt-5 lg:h-[252px] lg:max-h-[252px] lg:min-h-[252px]">
              <div
                class="info-block__flex-icon-band flex w-full shrink-0 flex-col justify-end [min-block-size:7.125rem] [max-block-size:7.125rem]">
                @if($cell['image'] !== null)
                  <div class="flex w-full items-end justify-center [min-block-size:0]">
                    {!! Image::render($cell['image'], [
                        'class' =>
                            'max-h-[6.125rem] w-auto max-w-[min(100%,7rem)] object-contain object-bottom text-deep-moss',
                    ]) !!}
                  </div>
                @endif
              </div>
              <div class="mt-4 flex w-full max-w-[19rem] flex-col items-center gap-1.5">
                {{-- Figma 51:8283 tile heading: Canela 42 / Faded Olive. Tile uses fixed heights
                     (`md:h-[248px]`) so we keep `leading-none` for single-line titles — the airy
                     "lh 84" feel comes from the surrounding mt-4 + tile padding, not the lh value. --}}
                <h3 class="m-0 w-full font-heading text-4xl font-normal leading-none tracking-normal text-faded-olive lg:whitespace-nowrap">
                  {{ esc_html($cell['title']) }}
                </h3>
                @if($cell['description'] !== '')
                  <p
                    class="m-0 w-full font-label text-xs font-semibold uppercase leading-tight tracking-[1px] text-faded-olive md:leading-snug [&_br]:leading-snug [&_br]:block">
                    {!! nl2br(e($cell['description'])) !!}
                  </p>
                @endif
              </div>
            </article>
          @endforeach
        </div>
      @endif
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => $root,
      'message' => __('Add heading or info cells to this block.', 'culvers'),
  ])
@endif
