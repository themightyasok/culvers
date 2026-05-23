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
  $headingTag = Component::headingTagFromComponent($c, 'hours_heading_level', 2);

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
  $hoursHeadingClass = Component::sectionHeadingClasses('text-faded-olive');
  $hoursHeaderShellClass = $isShopSingle
      ? 'mb-10 text-center md:mb-12'
      : 'mx-auto mb-10 max-w-[40rem] text-center md:mb-12';
  // Plan / landing (Figma 51:4918): sub + body = Halyard Book 20px / lh 1.3; shop single unchanged.
  $hoursSubClass = $isShopSingle
      ? 'mt-4 font-sans text-xl font-light leading-[1.3] text-faded-olive'
      : 'mt-4 font-sans text-xl font-light leading-[1.3] text-deep-moss';
  $hoursIntroBodyBase = $isShopSingle
      ? 'opening-hours__body mt-6 max-w-none text-center font-sans text-xl font-light leading-[1.3] text-faded-olive [&_p+p]:mt-4 [&_strong]:font-medium rt-link-olive-surface'
      : 'opening-hours__body mt-6 max-w-none text-center font-sans [&_p]:text-xl [&_p]:font-light [&_p]:leading-[1.3] [&_p+p]:mt-4 [&_strong]:font-medium rt-link-prose';
  $hoursListTopBorder = $isShopSingle ? 'border-faded-olive/45' : 'border-deep-moss/20';
  /** Rows: Book 300 + lh 1.3; “today” uses same px as peers — pill is a pseudo-element bleed. */
  $hoursRowShellShop = 'flex items-center justify-between gap-6 px-3 py-3.5 font-sans text-xl font-light leading-[1.3] text-faded-olive sm:px-2';
  $hoursRowShellDefault = 'flex items-center justify-between gap-6 px-3 py-3.5 font-sans text-xl font-light leading-[1.3] text-deep-moss sm:px-2';
  /** Figma (~740px pill vs ~692px rules): widen with before:-inset-x-* on sm+ only.
   *  On mobile the section gutter is px-4 (16px); -inset-x-5 bleeds past it and the
   *  Glowleaf pill touches the viewport — keep inset-x-0 until sm. */
  $hoursTodayBleedShop =
      'relative isolate overflow-visible font-normal leading-[26px] before:pointer-events-none before:absolute before:inset-y-[-3px] before:z-0 before:inset-x-0 before:rounded-full before:bg-brand-500 sm:before:-inset-x-[1.875rem]';
  $hoursTodayBleedDefault =
      'relative isolate overflow-visible font-normal leading-[26px] before:pointer-events-none before:absolute before:inset-y-[-3px] before:z-0 before:inset-x-0 before:rounded-full before:bg-brand-500 sm:before:-inset-x-[1.875rem]';
  $hoursFootClass = $isShopSingle
      ? 'mx-auto mt-8 max-w-[22.5rem] text-center font-sans text-xl font-light leading-[1.3] text-faded-olive'
      : 'mx-auto mt-8 max-w-[22.5rem] text-center font-sans text-xl font-light leading-[1.3] text-deep-moss';

  $hoursFirstRowToday = isset($normalizedRows[0]) && ($normalizedRows[0]['is_today'] ?? false);
@endphp

@if($hasIntro || $hasRows || $footnote !== '' || $leftUrl !== '' || $rightUrl !== '')
  {{-- Same inner cap + horizontal gutters as three-card strip (max-w-7xl + LayoutShell gutters). --}}
  <section class="opening-hours {{ esc_attr($root) }} text-deep-moss" id="opening-hours" data-component-root data-opening-hours>
    <div class="{{ LayoutShell::INNER_SECTION_7XL }}">
      @if($hasIntro)
        <header class="{{ esc_attr($hoursHeaderShellClass) }}">
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
        {{-- Side illustrations: cap column + max-height slightly under prior pass so glyphs don’t
             overpower the hours table while staying clear of the list. --}}
        <div class="flex flex-col items-stretch gap-10 lg:flex-row lg:items-center lg:gap-14 xl:gap-20">
          @if($leftUrl !== '')
            <div class="order-2 hidden shrink-0 items-center justify-center lg:order-1 lg:flex lg:w-[min(26vw,10rem)] xl:w-[min(26vw,11.25rem)]">
              {!! Image::render($graphicLeft, [
                  'class' => 'h-auto max-h-[200px] w-full max-w-full object-contain opacity-95 lg:max-h-[252px]',
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
                  $isLastRow = $index === count($normalizedRows) - 1;
                  $nextRowIsToday = isset($normalizedRows[$index + 1])
                      && ($normalizedRows[$index + 1]['is_today'] ?? false);

                  if ($isShopSingle) {
                      $hoursRowBase = $hoursRowShellShop;
                      if ($row['is_today']) {
                          $hoursRowBase .= ' ' . $hoursTodayBleedShop;
                          if ($isLastRow) {
                              $hoursRowBase .= ' border-b border-faded-olive/40';
                          }
                      } elseif ($nextRowIsToday) {
                          /** No divider between previous day and Glowleaf pill (Figma: no stroke above pill). */
                          $hoursRowBase .= ' border-b-0';
                      } else {
                          $hoursRowBase .= ' border-b border-faded-olive/40';
                      }
                  } else {
                      $hoursRowBase = $hoursRowShellDefault;
                      if ($row['is_today']) {
                          $hoursRowBase .= ' ' . $hoursTodayBleedDefault;
                          if ($isLastRow) {
                              $hoursRowBase .= ' border-b border-deep-moss/15';
                          }
                      } elseif ($nextRowIsToday) {
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
                  <span class="min-w-0 {{ $row['is_today'] ? 'relative z-[1]' : '' }}">{{ esc_html($row['label']) }}</span>
                  <span class="shrink-0 tabular-nums text-right {{ $row['is_today'] ? 'relative z-[1]' : '' }}">{{ esc_html($row['times']) }}</span>
                </li>
              @endforeach
            </ul>
          </div>

          @if($rightUrl !== '')
            <div class="order-3 hidden shrink-0 items-center justify-center lg:flex lg:w-[min(26vw,10rem)] xl:w-[min(26vw,11.25rem)]">
              {!! Image::render($graphicRight, [
                  'class' => 'h-auto max-h-[200px] w-full max-w-full object-contain opacity-95 lg:max-h-[252px]',
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
