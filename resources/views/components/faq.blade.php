@php
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;

  /**
   * FAQ — centred Canela heading + glowleaf keyline + accordion of disclosure
   * rows. Optional decorative line-art images flank the column on lg+ screens.
   * Implements the WAI ARIA disclosure pattern (one button per question with
   * `aria-expanded` controlling the answer region). Figma ref: 51:7998.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $headingTag = Component::headingTag($c['faq_heading_level'] ?? null);

  $heading = trim((string) ($c['faq_heading'] ?? ''));
  $showKeyline = ! empty($c['faq_show_keyline']);
  $openMode = ($c['faq_open_mode'] ?? 'single') === 'multi' ? 'multi' : 'single';

  $itemsRaw = $c['faq_items'] ?? [];
  $items = [];
  $defaultOpen = [];
  if (is_array($itemsRaw)) {
      foreach ($itemsRaw as $row) {
          if (! is_array($row)) {
              continue;
          }
          $question = trim((string) ($row['item_question'] ?? ''));
          $answerHtml = trim((string) ($row['item_answer'] ?? ''));
          if ($question === '' || trim(wp_strip_all_tags($answerHtml)) === '') {
              continue;
          }
          $items[] = [
              'question' => $question,
              'answer_html' => $answerHtml,
              'open' => ! empty($row['item_open_default']),
          ];
      }
  }
  foreach ($items as $i => $item) {
      if ($item['open']) {
          $defaultOpen[] = $i;
          if ($openMode === 'single') {
              break;
          }
      }
  }

  $leftDecorationsRaw = $c['faq_decorations_left'] ?? [];
  $rightDecorationsRaw = $c['faq_decorations_right'] ?? [];
  $leftDecorations = [];
  $rightDecorations = [];
  foreach ((array) $leftDecorationsRaw as $row) {
      if (is_array($row) && isset($row['item_image']) && is_array($row['item_image'])
          && trim((string) ($row['item_image']['url'] ?? '')) !== '') {
          $leftDecorations[] = $row['item_image'];
      }
  }
  foreach ((array) $rightDecorationsRaw as $row) {
      if (is_array($row) && isset($row['item_image']) && is_array($row['item_image'])
          && trim((string) ($row['item_image']['url'] ?? '')) !== '') {
          $rightDecorations[] = $row['item_image'];
      }
  }

  $instanceId = 'faq-' . uniqid();
  $alpineConfig = wp_json_encode([
      'mode' => $openMode,
      'defaultOpen' => $defaultOpen,
  ]);
  if (! is_string($alpineConfig)) {
      $alpineConfig = '{}';
  }
@endphp

@if($items !== [])
  <section
    class="faq {{ esc_attr($root) }} relative bg-lighter-cream text-deep-moss"
    data-component-root
    data-faq
    x-data='faq({{ $alpineConfig }})'>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }} relative">
      @if($leftDecorations !== [])
        <div
          class="faq__decorations faq__decorations--left pointer-events-none absolute left-0 top-0 hidden h-full w-32 lg:block xl:w-40"
          aria-hidden="true">
          <div class="flex h-full flex-col items-start justify-around gap-8 py-8">
            @foreach($leftDecorations as $img)
              {!! Image::render($img, [
                  'class' => 'h-auto w-full max-w-[10rem] object-contain',
                  'alt' => '',
                  'role' => 'presentation',
              ]) !!}
            @endforeach
          </div>
        </div>
      @endif

      @if($rightDecorations !== [])
        <div
          class="faq__decorations faq__decorations--right pointer-events-none absolute right-0 top-0 hidden h-full w-32 lg:block xl:w-40"
          aria-hidden="true">
          <div class="flex h-full flex-col items-end justify-around gap-8 py-8">
            @foreach($rightDecorations as $img)
              {!! Image::render($img, [
                  'class' => 'h-auto w-full max-w-[10rem] object-contain',
                  'alt' => '',
                  'role' => 'presentation',
              ]) !!}
            @endforeach
          </div>
        </div>
      @endif

      <div class="relative z-10 mx-auto flex w-full max-w-[774px] flex-col items-center">
        @if($heading !== '')
          {{-- Section H2: 64px desktop / 48px mobile (Component::sectionHeadingClasses). --}}
          <{{ $headingTag }} class="{{ Component::sectionHeadingClasses('text-deep-moss', 'text-center') }}">
            {{ esc_html($heading) }}
          </{{ $headingTag }}>
          @if($showKeyline)
            <span class="faq__keyline mt-6 block h-[2px] w-4 bg-glowleaf" aria-hidden="true"></span>
          @endif
        @endif

        <div class="faq__list mt-10 w-full md:mt-12">
          @foreach($items as $i => $item)
            @php
                $questionId = $instanceId . '-q-' . $i;
                $panelId = $instanceId . '-p-' . $i;
                $isOpen = in_array($i, $defaultOpen, true);
            @endphp
            <div class="faq__item border-b border-deep-moss/45">
              <{{ $headingTag === 'h2' ? 'h3' : 'h4' }} class="faq__heading m-0">
                <button
                  id="{{ esc_attr($questionId) }}"
                  type="button"
                  class="faq__question group flex w-full cursor-pointer items-center justify-between gap-4 py-5 text-left font-sans text-base text-deep-moss transition-colors culvers-focus-ring md:text-lg"
                  data-faq-question
                  aria-controls="{{ esc_attr($panelId) }}"
                  aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                  x-on:click="toggle({{ $i }})"
                  x-on:keydown.down.prevent="focusSibling({{ $i }}, 1)"
                  x-on:keydown.up.prevent="focusSibling({{ $i }}, -1)"
                  x-on:keydown.home.prevent="focusEdge({{ $i }}, 'first')"
                  x-on:keydown.end.prevent="focusEdge({{ $i }}, 'last')">
                  <span class="faq__question-text">{{ esc_html($item['question']) }}</span>
                  <span
                    class="faq__icon relative flex size-4 shrink-0 items-center justify-center text-deep-moss"
                    aria-hidden="true">
                    <span class="absolute inset-x-0 top-1/2 h-[2px] -translate-y-1/2 bg-current"></span>
                    <span
                      class="absolute inset-y-0 left-1/2 w-[2px] -translate-x-1/2 bg-current transition-transform duration-200 ease-out"
                      x-bind:class="isOpen({{ $i }}) ? 'scale-y-0' : 'scale-y-100'"></span>
                  </span>
                </button>
              </{{ $headingTag === 'h2' ? 'h3' : 'h4' }}>

              <div
                id="{{ esc_attr($panelId) }}"
                role="region"
                aria-labelledby="{{ esc_attr($questionId) }}"
                class="faq__panel grid transition-[grid-template-rows] duration-300 ease-out motion-reduce:transition-none data-[open=true]:grid-rows-[1fr] grid-rows-[0fr]"
                data-open="{{ $isOpen ? 'true' : 'false' }}"
                @if (! $isOpen) inert @endif
                x-bind:inert="!isOpen({{ $i }})">
                <div class="faq__panel-inner overflow-hidden">
                  <div
                    class="faq__answer pb-6 pr-10 font-sans text-sm leading-6 text-deep-moss/80 rt-link-faded [&_p+p]:mt-3 [&_strong]:font-medium [&_strong]:text-deep-moss [&_ul]:my-3 [&_ul]:list-disc [&_ul]:pl-6">
                    {!! $item['answer_html'] !!}
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => $root,
      'message' => __('Add at least one FAQ row with a question and answer.', 'culvers'),
  ])
@endif
