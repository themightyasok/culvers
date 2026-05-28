@php
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;
  use App\Support\PageSectionAnchor;

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

  $sectionAnchorId = $heading !== '' ? PageSectionAnchor::fromHeading($heading) : '';
  $sectionAnchorAttr = $sectionAnchorId !== '' ? ' id="' . esc_attr($sectionAnchorId) . '"' : '';
  $sectionScrollMargin = $sectionAnchorId !== '' ? PageSectionAnchor::scrollMarginClass() : '';
  $introBodyClasses = 'info-block__body mx-auto w-full max-w-[36.75rem] text-center '
      . Component::sectionIntroBodyClasses('text-deep-moss', 'max-sm:text-sm max-sm:leading-5 [&_p+p]:mt-4 [&_strong]:font-medium rt-link-prose ' . esc_attr($tone));
@endphp

@if($hasIntro || $hasGrid)
  <section
    {!! $sectionAnchorAttr !!}
    class="info-block {{ esc_attr(trim($root . ' ' . $sectionScrollMargin)) }} relative text-deep-moss"
    data-component-root
    data-info-block>
    <div class="relative z-10 {{ LayoutShell::INNER_MAX_GUTTERED }}">
      @if($hasIntro)
        @include('partials.section-intro-stack', [
            'headingTag' => $headingTag,
            'heading' => $heading,
            'headingClasses' => Component::sectionIntroHeadingClasses('text-faded-olive'),
            'subheading' => $subheading,
            'bodyHtml' => $body,
            'bodyClasses' => $introBodyClasses,
            'introStackIncludeCta' => true,
            'ctaLabel' => $ctaLabel,
            'ctaUrl' => $ctaUrl,
            'wrapperClasses' => 'info-block__intro mx-auto max-w-[52rem] text-center',
        ])
      @endif

      @if($hasGrid)
        <div class="info-block__grid mx-auto w-full">
          @foreach($cells as $cell)
            {{--
              Divider geometry still follows Figma `51:5066` metadata (`--info-block-rule-h-inset: 17px`, `--info-block-rule-v-end: 7%`).
              Mobile stack `51:8283`: 50px copy→rule; 50px rule→next icon (grid row-gap); 16px icon→copy; 14px title→label (flex gap).
            --}}
            <article
              class="info-block__cell flex flex-col items-center px-4 pt-0 text-center max-md:pb-[50px] sm:px-6 md:box-border md:h-[248px] md:max-h-[248px] md:min-h-[248px] md:justify-start md:px-8 md:pb-4 md:pt-5 lg:h-[252px] lg:max-h-[252px] lg:min-h-[252px]">
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
              <div class="mt-4 flex w-full max-w-[19rem] flex-col items-center gap-3.5 md:gap-1.5">
                {{-- Figma 51:8283 / 51:8295: Canela 42 tile title (not mobilePanelSubheadClasses — that is Halyard 20). --}}
                <h3 class="m-0 w-full {{ Component::infoBlockTileTitleClasses('text-faded-olive', 'text-center lg:whitespace-nowrap') }}">
                  {{ esc_html($cell['title']) }}
                </h3>
                @if($cell['description'] !== '')
                  <p
                    class="m-0 w-full font-label text-sm font-semibold uppercase leading-5 tracking-[1px] text-faded-olive md:text-xs md:leading-snug [&_br]:block [&_br]:leading-snug">
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
