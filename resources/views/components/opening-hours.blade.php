@php
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;

  /**
   * Opening hours — heading + intro copy + day rows with “today” highlight,
   * optional left/right line illustrations, optional footnote.
   * Renders inside a narrow readable shell; site timezone drives the highlight.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $tone = Component::bodyTextTone($c);
  $headingTag = Component::headingTag($c['hours_heading_level'] ?? null);

  $heading = trim((string) ($c['hours_heading'] ?? ''));
  $subheading = trim((string) ($c['hours_subheading'] ?? ''));
  $body = (string) ($c['hours_body'] ?? '');
  $footnote = trim((string) ($c['hours_footnote'] ?? ''));

  $graphicLeft = isset($c['hours_graphic_left']) && is_array($c['hours_graphic_left']) ? $c['hours_graphic_left'] : [];
  $graphicRight = isset($c['hours_graphic_right']) && is_array($c['hours_graphic_right']) ? $c['hours_graphic_right'] : [];
  $leftUrl = isset($graphicLeft['url']) ? trim((string) $graphicLeft['url']) : '';
  $rightUrl = isset($graphicRight['url']) ? trim((string) $graphicRight['url']) : '';
  $leftAlt = isset($graphicLeft['alt']) ? trim((string) $graphicLeft['alt']) : '';
  $rightAlt = isset($graphicRight['alt']) ? trim((string) $graphicRight['alt']) : '';

  $rowsRaw = $c['hours_rows'] ?? [];
  $rows = is_array($rowsRaw) ? $rowsRaw : [];

  $weekdayToPhp = [
      'sun' => 0,
      'mon' => 1,
      'tue' => 2,
      'wed' => 3,
      'thu' => 4,
      'fri' => 5,
      'sat' => 6,
  ];

  $todayNum = (int) wp_date('w', (int) current_time('timestamp'));

  $normalizedRows = [];
  foreach ($rows as $row) {
      if (! is_array($row)) {
          continue;
      }
      $label = trim((string) ($row['day_label'] ?? ''));
      $times = trim((string) ($row['time_range'] ?? ''));
      $wk = (string) ($row['weekday_highlight'] ?? 'none');
      if (! isset($weekdayToPhp[$wk])) {
          $wk = 'none';
      }
      if ($label === '' || $times === '') {
          continue;
      }
      $matchNum = $wk === 'none' ? null : $weekdayToPhp[$wk];
      $normalizedRows[] = [
          'label' => $label,
          'times' => $times,
          'is_today' => $matchNum !== null && $matchNum === $todayNum,
      ];
  }

  $hasIntro = $heading !== '' || $subheading !== '' || trim(strip_tags($body)) !== '';
  $hasRows = $normalizedRows !== [];

  $isShopSingle = get_post_type() === 'culvers_shop';
/** Default: canonical section H2 (Component::sectionHeadingClasses → 58px desktop / 48px mobile). */
/** Shop-single: deliberately a touch smaller — sub-section heading inside the single page layout. */
$hoursHeadingClass = $isShopSingle
    ? 'font-heading text-5xl text-faded-olive'
    : Component::sectionHeadingClasses('text-faded-olive');
  $hoursSubClass = $isShopSingle
      ? 'mt-4 font-sans text-xl font-light text-faded-olive'
      : 'mt-4 font-sans text-base leading-relaxed text-deep-moss/85 md:text-xl';
  $hoursIntroBodyBase = $isShopSingle
      ? 'opening-hours__body mt-6 max-w-none text-center font-sans text-xl font-light text-faded-olive [&_p+p]:mt-4 [&_strong]:font-medium rt-link-olive-surface'
      : 'opening-hours__body prose prose-lg mt-6 max-w-none text-left md:text-center text-deep-moss prose-headings:text-deep-moss prose-p:text-deep-moss prose-li:text-deep-moss prose-strong:text-deep-moss rt-link-prose';
  $hoursListTopBorder = $isShopSingle ? 'border-faded-olive/45' : 'border-deep-moss/20';
  /** Row shell — bottom divider applied per row so “today” can sit flush under previous row (Figma: no line above pill). */
  $hoursRowShellShop = 'flex items-center justify-between gap-6 px-1 py-3.5 font-sans text-xl font-light text-faded-olive sm:px-2';
  $hoursRowShellDefault = 'flex items-center justify-between gap-6 px-1 py-3.5 font-sans text-base text-deep-moss sm:px-2 sm:text-xl';
  /** Pill highlight: no top/side borders; keep bottom rule below pill unless last row. */
  $hoursRowTodayShop = '!mx-0 !rounded-full bg-brand-500 !border-t-0 !border-x-0 border-b border-faded-olive/40 !px-4 !py-3 text-faded-olive last:border-b-0 sm:!py-3.5';
  $hoursRowTodayDefault = '!mx-0 !rounded-full bg-brand-500 !border-t-0 !border-x-0 border-b border-deep-moss/15 !px-4 !py-3 last:border-b-0 sm:!py-3.5';
  $hoursFootClass = $isShopSingle
      ? 'mx-auto mt-8 max-w-[40rem] text-center font-sans text-xl font-light text-faded-olive'
      : 'mx-auto mt-8 max-w-[40rem] text-center font-sans text-xs leading-6 text-deep-moss/80';

  $hoursFirstRowToday = isset($normalizedRows[0]) && ($normalizedRows[0]['is_today'] ?? false);
@endphp

@if($hasIntro || $hasRows || $footnote !== '' || $leftUrl !== '' || $rightUrl !== '')
  <section class="opening-hours {{ esc_attr($root) }} text-deep-moss" id="opening-hours" data-component-root data-opening-hours>
    <div class="{{ LayoutShell::INNER_READABLE_960 }}">
      @if($hasIntro)
        <header class="mx-auto mb-10 max-w-[40rem] text-center md:mb-12">
          @if($heading !== '')
            <{{ $headingTag }} class="{{ esc_attr(trim($hoursHeadingClass)) }}">
              {{ esc_html($heading) }}
            </{{ $headingTag }}>
          @endif
          @if($subheading !== '')
            <p class="{{ esc_attr(trim($hoursSubClass)) }}">
              {!! nl2br(e($subheading)) !!}
            </p>
          @endif
          @if(trim(strip_tags($body)) !== '')
            <div
              class="{{ esc_attr(trim($hoursIntroBodyBase . ($isShopSingle ? '' : ' ' . $tone))) }}">
              {!! $body !!}
            </div>
          @endif
        </header>
      @endif

      @if($hasRows)
        <div class="flex flex-col items-stretch gap-10 lg:flex-row lg:items-center lg:gap-8 xl:gap-12">
          @if($leftUrl !== '')
            <div class="order-2 hidden shrink-0 justify-center lg:order-1 lg:flex lg:w-[min(28vw,9rem)] xl:w-[min(28vw,10.5rem)]">
              {!! Image::render($graphicLeft, [
                  'class' => 'max-h-[200px] w-auto max-w-full object-contain opacity-95 lg:max-h-[260px]',
                  'alt' => $leftAlt,
              ]) !!}
            </div>
          @endif

          <div class="order-1 min-w-0 flex-1 lg:order-2">
            <ul
              class="{{ esc_attr(trim('border-t ' . $hoursListTopBorder . ($hoursFirstRowToday ? ' border-t-0' : ''))) }}"
              role="list"
              aria-label="{{ esc_attr__('Opening hours by day', 'culvers') }}">
              @foreach($normalizedRows as $index => $row)
                @php
                  $nextIsToday = isset($normalizedRows[$index + 1]) && ($normalizedRows[$index + 1]['is_today'] ?? false);
                  $isLastRow = $index === count($normalizedRows) - 1;
                  if ($isShopSingle) {
                      $hoursRowBase = $hoursRowShellShop;
                      if ($row['is_today']) {
                          $hoursRowBase .= ' ' . $hoursRowTodayShop;
                      } elseif ($nextIsToday || $isLastRow) {
                          $hoursRowBase .= ' border-b-0';
                      } else {
                          $hoursRowBase .= ' border-b border-faded-olive/40';
                      }
                  } else {
                      $hoursRowBase = $hoursRowShellDefault;
                      if ($row['is_today']) {
                          $hoursRowBase .= ' ' . $hoursRowTodayDefault;
                      } elseif ($nextIsToday || $isLastRow) {
                          $hoursRowBase .= ' border-b-0';
                      } else {
                          $hoursRowBase .= ' border-b border-deep-moss/15';
                      }
                  }
                @endphp
                <li
                  class="{{ esc_attr(trim($hoursRowBase)) }}"
                  @if($row['is_today'])
                    aria-current="true"
                  @endif>
                  <span class="min-w-0 {{ $isShopSingle ? ($row['is_today'] ? 'font-normal leading-[26px]' : 'font-light') : 'font-medium leading-snug' }}">{{ esc_html($row['label']) }}</span>
                  <span class="shrink-0 tabular-nums text-right {{ $isShopSingle ? ($row['is_today'] ? 'font-normal leading-[26px] text-faded-olive' : 'leading-snug text-faded-olive') : 'leading-snug text-deep-moss/95' }}">{{ esc_html($row['times']) }}</span>
                </li>
              @endforeach
            </ul>
          </div>

          @if($rightUrl !== '')
            <div class="order-3 hidden shrink-0 justify-center lg:flex lg:w-[min(28vw,9rem)] xl:w-[min(28vw,10.5rem)]">
              {!! Image::render($graphicRight, [
                  'class' => 'max-h-[200px] w-auto max-w-full object-contain opacity-95 lg:max-h-[260px]',
                  'alt' => $rightAlt,
              ]) !!}
            </div>
          @endif
        </div>
      @endif

      @if($footnote !== '')
        <p class="{{ esc_attr(trim($hoursFootClass)) }}">
          {!! nl2br(e($footnote)) !!}
        </p>
      @endif
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => $root,
      'message' => __('Add heading or hours rows to this block.', 'culvers'),
  ])
@endif
