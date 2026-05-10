@php
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;

  /**
   * Shop — split highlight. Faded Olive copy column + flush lifestyle image
   * (10px-radius card). The copy column may render as static content or as a
   * tabbed deck whose panels cross-fade in place. Column ratio is editor-
   * selectable (60/40 default, 50/50). Figma ref: 51:6191–51:6194.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $ratio = ($c['split_ratio'] ?? '60-40') === '50-50' ? '50-50' : '60-40';

  $useTabs = ! empty($c['split_use_tabs']);

  $img = isset($c['split_image']) && is_array($c['split_image']) ? $c['split_image'] : [];
  $imgUrl = isset($img['url']) ? trim((string) $img['url']) : '';
  $imgAlt = isset($img['alt']) ? trim((string) $img['alt']) : '';

  $rawTabs = isset($c['split_tabs']) && is_array($c['split_tabs']) ? $c['split_tabs'] : [];
  $tabs = [];
  if ($useTabs) {
      foreach ($rawTabs as $row) {
          if (! is_array($row)) {
              continue;
          }
          $label = trim((string) ($row['tab_label'] ?? ''));
          $headline = trim((string) ($row['tab_headline'] ?? ''));
          $kicker = trim((string) ($row['tab_kicker'] ?? ''));
          $bodyHtml = trim((string) ($row['tab_body'] ?? ''));
          $bodyPlain = trim(wp_strip_all_tags($bodyHtml));
          $ctaLabel = trim((string) ($row['tab_cta_label'] ?? ''));
          $ctaUrl = trim((string) ($row['tab_cta_url'] ?? ''));

          if ($label === '' && $headline === '' && $kicker === '' && $bodyPlain === '') {
              continue;
          }

          $tabOwn = isset($row['tab_image']) && is_array($row['tab_image']) ? $row['tab_image'] : [];
          $tabOwnUrl = isset($tabOwn['url']) ? trim((string) $tabOwn['url']) : '';
          $effectiveMedia = $tabOwnUrl !== '' ? $tabOwn : $img;
          $effectiveUrl = isset($effectiveMedia['url']) ? trim((string) $effectiveMedia['url']) : '';
          $effectiveAlt = trim((string) ($effectiveMedia['alt'] ?? ''));
          if ($effectiveAlt === '') {
              $effectiveAlt = $headline !== '' ? $headline : ($label !== '' ? $label : __('Feature image', 'culvers'));
          }

          $tabs[] = [
              'label' => $label,
              'kicker' => $kicker,
              'headline' => $headline,
              'body_html' => $bodyHtml,
              'body_plain' => $bodyPlain,
              'cta_label' => $ctaLabel,
              'cta_url' => $ctaUrl,
              'show_cta' => $ctaLabel !== '' && $ctaUrl !== '',
              'media' => $effectiveMedia,
              'media_url' => $effectiveUrl,
              'media_alt' => $effectiveAlt,
          ];
      }
  }
  $hasTabs = $useTabs && ! empty($tabs);

  $hasRightMedia = $imgUrl !== '';
  $tabsMediaUniform = true;
  if ($hasTabs) {
      $hasRightMedia = false;
      $distinctUrls = [];
      foreach ($tabs as $tab) {
          $u = $tab['media_url'];
          if ($u !== '') {
              $hasRightMedia = true;
              $distinctUrls[$u] = true;
          }
      }
      $tabsMediaUniform = count($distinctUrls) <= 1;
  }

  $kicker = trim((string) ($c['split_kicker'] ?? ''));
  $headline = trim((string) ($c['split_headline'] ?? ''));
  $bodyHtml = trim((string) ($c['split_body'] ?? ''));
  $bodyPlain = trim(wp_strip_all_tags($bodyHtml));
  $ctaLabel = trim((string) ($c['split_cta_label'] ?? ''));
  $ctaUrl = trim((string) ($c['split_cta_url'] ?? ''));
  $showCta = $ctaLabel !== '' && $ctaUrl !== '';

  $hasStaticSerifLines = $kicker !== '' || $headline !== '';
  $hasStaticCopy = $hasStaticSerifLines || $bodyPlain !== '';
  $hasCopy = $hasTabs || $hasStaticCopy;

  $bodyClasses = 'shop-split-highlight__body max-w-[34.625rem] font-sans text-xl font-light text-white rt-link-brand'
      . ' [&_li]:marker:text-brand-500 [&_p+p]:mt-4 [&_strong]:font-medium [&_strong]:text-white'
      . ' [&_ul]:my-4 [&_ul]:inline-block [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:text-left';

  $tablistId = 'split-highlight-tabs-' . uniqid();
@endphp

@if($hasCopy && $hasRightMedia)
  <section
    class="shop-split-highlight {{ esc_attr($root) }} text-deep-moss"
    data-component-root
    data-shop-split-highlight
    @if($hasTabs) x-data="splitHighlight" @endif>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      {{-- Grid (not flex row) so the image column matches the copy column height even when the
           <img> is absolutely positioned (out of flow). Flex would leave the media cell at min-height only. --}}
      <div
        class="grid overflow-hidden rounded-[10px] bg-faded-olive shadow-sm lg:min-h-[597px] lg:grid-cols-12 lg:items-stretch">
        {{-- Copy column --}}
        <div
          class="flex flex-col gap-6 px-8 py-12 max-lg:order-1 lg:order-none lg:gap-8 lg:px-10 xl:px-14 xl:py-12 {{ $ratio === '50-50' ? 'lg:col-span-6' : 'lg:col-span-7' }} {{ $hasTabs ? 'items-stretch text-left' : 'items-center justify-center text-center' }}">
          @if($hasTabs)
            {{-- Tab list (Figma parity: thin divider rule beneath the pill row). --}}
            <div
              id="{{ esc_attr($tablistId) }}"
              role="tablist"
              aria-label="{{ esc_attr__('Highlight sections', 'culvers') }}"
              class="shop-split-highlight__tabs flex flex-wrap items-center gap-2 border-b border-light-cream/30 pb-6 lg:pb-8">
              @foreach($tabs as $i => $tab)
                @php
                    $tabId = $tablistId . '-tab-' . $i;
                    $panelId = $tablistId . '-panel-' . $i;
                @endphp
                <button
                  id="{{ esc_attr($tabId) }}"
                  type="button"
                  role="tab"
                  aria-controls="{{ esc_attr($panelId) }}"
                  aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                  tabindex="{{ $i === 0 ? '0' : '-1' }}"
                  class="shop-split-highlight__tab cursor-pointer rounded-full px-5 py-2 font-sans text-xs font-semibold uppercase tracking-wider transition-colors duration-150 culvers-focus-ring"
                  x-on:click="selectTab({{ $i }})"
                  x-on:keydown.right.prevent="selectTab(({{ $i }} + 1) % {{ count($tabs) }}, true)"
                  x-on:keydown.left.prevent="selectTab(({{ $i }} - 1 + {{ count($tabs) }}) % {{ count($tabs) }}, true)"
                  x-on:keydown.home.prevent="selectTab(0, true)"
                  x-on:keydown.end.prevent="selectTab({{ count($tabs) - 1 }}, true)"
                  x-bind:class="activeTab === {{ $i }} ? 'bg-glowleaf text-deep-moss' : 'bg-transparent text-light-cream hover:text-glowleaf'">
                  {{ esc_html($tab['label'] !== '' ? $tab['label'] : ($tab['headline'] !== '' ? $tab['headline'] : __('Tab', 'culvers'))) }}
                </button>
              @endforeach
            </div>

            {{-- Panel deck (all panels share one grid cell so the column sizes to the tallest). --}}
            <div class="shop-split-highlight__panels relative grid">
              @foreach($tabs as $i => $tab)
                @php
                    $tabId = $tablistId . '-tab-' . $i;
                    $panelId = $tablistId . '-panel-' . $i;
                @endphp
                <div
                  id="{{ esc_attr($panelId) }}"
                  role="tabpanel"
                  aria-labelledby="{{ esc_attr($tabId) }}"
                  class="shop-split-highlight__panel col-start-1 row-start-1 flex max-w-[34.625rem] flex-col gap-6 transition-opacity duration-300 ease-out lg:gap-8"
                  x-bind:class="activeTab === {{ $i }} ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                  x-bind:aria-hidden="activeTab === {{ $i }} ? 'false' : 'true'">
                  @if($tab['kicker'] !== '' || $tab['headline'] !== '')
                    <div class="flex flex-col gap-0">
                      @if($tab['kicker'] !== '')
                        <p class="font-heading text-7xl text-brand-500">
                          {{ esc_html($tab['kicker']) }}
                        </p>
                      @endif
                      @if($tab['headline'] !== '')
                        <p class="font-heading text-7xl text-brand-500 {{ $tab['kicker'] !== '' ? '-mt-1' : '' }}">
                          {{ esc_html($tab['headline']) }}
                        </p>
                      @endif
                    </div>
                  @endif

                  @if($tab['body_plain'] !== '')
                    <div class="{{ $bodyClasses }}">
                      {!! $tab['body_html'] !!}
                    </div>
                  @endif

                  @if($tab['show_cta'])
                    <div class="pt-2">
                      @include('components.button', [
                          'label' => $tab['cta_label'],
                          'href' => $tab['cta_url'],
                          'class' => 'self-start',
                      ])
                    </div>
                  @endif
                </div>
              @endforeach
            </div>
          @else
            @if($hasStaticSerifLines)
              <div class="flex max-w-[34.625rem] flex-col gap-0">
                {{-- Canela 64px / lh 1.2 → text-7xl (paired line-height in theme). --}}
                @if($kicker !== '')
                  <p class="font-heading text-7xl text-brand-500">
                    {{ esc_html($kicker) }}
                  </p>
                @endif
                @if($headline !== '')
                  <p class="font-heading text-7xl text-brand-500 {{ $kicker !== '' ? '-mt-1' : '' }}">
                    {{ esc_html($headline) }}
                  </p>
                @endif
              </div>
            @endif

            @if($bodyPlain !== '')
              <div class="{{ $bodyClasses }}">
                {!! $bodyHtml !!}
              </div>
            @endif

            @if($showCta)
              <div class="pt-2">
                @include('components.button', ['label' => $ctaLabel, 'href' => $ctaUrl])
              </div>
            @endif
          @endif
        </div>

        {{-- Image column: fills grid row height; inner layers are absolute + object-cover. --}}
        <div
          class="shop-split-highlight__media relative min-h-[280px] w-full overflow-hidden max-lg:order-2 {{ $ratio === '50-50' ? 'lg:col-span-6' : 'lg:col-span-5' }} lg:min-h-0">
          @if($hasTabs && ! $tabsMediaUniform)
            @foreach($tabs as $i => $tab)
              @php $mUrl = $tab['media_url']; @endphp
              @if($mUrl !== '')
                <div
                  class="shop-split-highlight__media-layer absolute inset-0 transition-opacity duration-300 ease-out {{ $i === 0 ? 'opacity-100 z-[1]' : 'opacity-0 pointer-events-none z-0' }}"
                  x-bind:class="activeTab === {{ $i }} ? 'opacity-100 z-[1]' : 'opacity-0 pointer-events-none z-0'"
                  x-bind:aria-hidden="activeTab === {{ $i }} ? 'false' : 'true'">
                  {!! Image::render($tab['media'], [
                      'class' => 'absolute inset-0 size-full object-cover',
                      'alt' => $tab['media_alt'],
                      'data' => ['background-parallax-image' => '1'],
                  ]) !!}
                </div>
              @endif
            @endforeach
          @else
            @php
              $soloImg = $img;
              $soloAlt = $imgAlt !== '' ? $imgAlt : ($headline !== '' ? $headline : __('Feature image', 'culvers'));
              if ($hasTabs && isset($tabs[0]) && is_array($tabs[0]['media'] ?? null)) {
                  $soloImg = $tabs[0]['media'];
                  $soloAlt = $tabs[0]['media_alt'];
              }
            @endphp
            {!! Image::render($soloImg, [
                'class' => 'absolute inset-0 size-full object-cover',
                'alt' => $soloAlt,
                'data' => ['background-parallax-image' => '1'],
            ]) !!}
          @endif
        </div>
      </div>
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @php
      if (! $hasRightMedia && ! $hasCopy) {
          $editorHint = __('Add split copy (or tabs) and a right-column image.', 'culvers');
      } elseif (! $hasRightMedia) {
          $editorHint = __('Add a right-column image (or a panel image on each tab).', 'culvers');
      } elseif ($useTabs) {
          $editorHint = __('Add at least one tab with content.', 'culvers');
      } else {
          $editorHint = __('Add a kicker, headline, or body copy.', 'culvers');
      }
  @endphp
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => $root,
      'message' => $editorHint,
  ])
@endif
