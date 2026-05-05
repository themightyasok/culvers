@php
  use App\Helpers\Padding;
  use App\Helpers\TailwindColors;

  $c = is_array($component ?? null) ? $component : [];
  $padding = Padding::getClasses($c);
  $grid = $c['_grid_classes'] ?? '';
  $tone = TailwindColors::sanitizeBodyTextTone($c['body_text_tone'] ?? null);

  $heading = trim((string) ($c['heading'] ?? ''));
  $level = isset($c['heading_semantic_level']) ? (int) $c['heading_semantic_level'] : 2;
  if ($level < 2 || $level > 4) {
      $level = 2;
  }
  $headingTag = 'h' . $level;

  $subheading = trim((string) ($c['subheading'] ?? ''));
  $body = (string) ($c['body'] ?? '');
  $footnote = trim((string) ($c['hours_footnote'] ?? ''));

  $graphicLeft = isset($c['graphic_left']) && is_array($c['graphic_left']) ? $c['graphic_left'] : [];
  $graphicRight = isset($c['graphic_right']) && is_array($c['graphic_right']) ? $c['graphic_right'] : [];
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
  $showPlaceholders = ! $hasRows && ! $hasIntro && $footnote === '' && current_user_can('edit_posts');
@endphp

@if($hasIntro || $hasRows || $footnote !== '' || $leftUrl !== '' || $rightUrl !== '')
  <section class="{{ esc_attr(trim($grid . ' ' . $padding)) }} text-deep-moss" id="opening-hours" data-component-root data-opening-hours>
    <div class="mx-auto max-w-[960px] px-5 sm:px-6 lg:px-8">
      @if($hasIntro)
        <header class="mx-auto mb-10 max-w-[40rem] text-center md:mb-12">
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
              class="opening-hours__body prose prose-neutral prose-lg mt-6 max-w-none text-left md:text-center [&_a]:text-deep-moss [&_a]:underline [&_a]:decoration-glowleaf [&_a]:underline-offset-4 {{ esc_attr($tone) }}">
              {!! $body !!}
            </div>
          @endif
        </header>
      @endif

      @if($hasRows)
        <div class="flex flex-col items-stretch gap-10 lg:flex-row lg:items-center lg:gap-8 xl:gap-12">
          @if($leftUrl !== '')
            <div class="order-2 hidden shrink-0 justify-center lg:order-1 lg:flex lg:w-[min(28vw,9rem)] xl:w-[min(28vw,10.5rem)]">
              <img
                src="{{ esc_url($leftUrl) }}"
                alt="{{ esc_attr($leftAlt) }}"
                class="max-h-[200px] w-auto max-w-full object-contain opacity-95 lg:max-h-[260px]"
                loading="lazy"
                decoding="async"
                @if(isset($graphicLeft['width'])) width="{{ (int) $graphicLeft['width'] }}" @endif
                @if(isset($graphicLeft['height'])) height="{{ (int) $graphicLeft['height'] }}" @endif />
            </div>
          @endif

          <div class="order-1 min-w-0 flex-1 lg:order-2">
            <ul class="border-t border-deep-moss/20" role="list">
              @foreach($normalizedRows as $row)
                <li
                  class="flex items-center justify-between gap-6 border-b border-deep-moss/15 px-1 py-3.5 font-sans text-sm text-deep-moss last:border-b-0 sm:px-2 sm:text-base {{ $row['is_today'] ? '!mx-0 !rounded-full !border-0 bg-brand-500 !px-4 !py-3 sm:!py-3.5' : '' }}"
                  @if($row['is_today'])
                    aria-current="true"
                  @endif>
                  <span class="min-w-0 font-medium leading-snug">{{ esc_html($row['label']) }}</span>
                  <span class="shrink-0 tabular-nums text-right leading-snug text-deep-moss/95">{{ esc_html($row['times']) }}</span>
                </li>
              @endforeach
            </ul>
          </div>

          @if($rightUrl !== '')
            <div class="order-3 hidden shrink-0 justify-center lg:flex lg:w-[min(28vw,9rem)] xl:w-[min(28vw,10.5rem)]">
              <img
                src="{{ esc_url($rightUrl) }}"
                alt="{{ esc_attr($rightAlt) }}"
                class="max-h-[200px] w-auto max-w-full object-contain opacity-95 lg:max-h-[260px]"
                loading="lazy"
                decoding="async"
                @if(isset($graphicRight['width'])) width="{{ (int) $graphicRight['width'] }}" @endif
                @if(isset($graphicRight['height'])) height="{{ (int) $graphicRight['height'] }}" @endif />
            </div>
          @endif
        </div>
      @endif

      @if($footnote !== '')
        <p class="mx-auto mt-8 max-w-[40rem] text-center font-sans text-xs leading-relaxed text-deep-moss/75 md:text-sm">
          {!! nl2br(e($footnote)) !!}
        </p>
      @endif
    </div>
  </section>
@elseif($showPlaceholders)
  <div class="{{ esc_attr(trim($grid . ' ' . $padding)) }} rounded border border-amber-400 bg-amber-50 px-4 py-3 text-amber-950">
    {{ __('Add heading or hours rows to this block.', 'culvers') }}
  </div>
@endif
