@php
  use App\Helpers\Component;
  use App\Helpers\EventMeta;
  use App\Helpers\LayoutShell;

  /**
   * Event meta — compact "When / Where / Tickets" panel for single events.
   * Three label/value rows with hairline separators (matches the Career
   * detail meta sidebar pattern), an optional accessibility note, and an
   * optional primary CTA. Sits below the event hero.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $rows = [];
  foreach (['date', 'time', 'location'] as $key) {
      $value = trim((string) ($c['event_meta_' . $key . '_value'] ?? ''));
      if ($value === '') {
          continue;
      }
      $rows[] = ['label' => EventMeta::rowLabel($key, $c), 'value' => $value];
  }

  $accessibilityRaw = trim((string) ($c['event_meta_accessibility_note'] ?? ''));
  $accessibilityWithBreaks = preg_replace('/<br\s*\/?>/i', "\n", $accessibilityRaw);
  $accessibilityLines = [];
  if ($accessibilityWithBreaks !== '') {
      $accessibilityLines = array_values(array_filter(array_map(static function (string $line): string {
          return trim($line);
      }, preg_split('/\r\n|\r|\n/', wp_strip_all_tags($accessibilityWithBreaks)))));
  }

  $ctaLabel = trim((string) ($c['event_meta_cta_label'] ?? ''));
  $ctaUrl = trim((string) ($c['event_meta_cta_url'] ?? ''));
  $hasCta = $ctaLabel !== '' && $ctaUrl !== '';
  $ctaIsExternal = $hasCta && str_starts_with($ctaUrl, 'http');

  $hasContent = $rows !== [] || $accessibilityLines !== [] || $hasCta;
@endphp

@if(! $hasContent)
  @if(current_user_can('edit_posts'))
    @include('partials.component-editor-placeholder', [
        'wrapperClasses' => $root,
        'message' => __('Event meta — add a date, time or location to display this band.', 'culvers'),
    ])
  @endif
@else
  <section
    class="event-meta {{ esc_attr($root) }} text-deep-moss"
    data-component-root
    data-event-meta>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      <div class="event-meta__panel mx-auto max-w-3xl rounded-2xl bg-white p-8 md:p-10">
        @if($rows !== [])
          <dl class="event-meta__rows flex flex-col">
            @foreach($rows as $row)
              <div class="event-meta__row grid grid-cols-1 gap-1 border-t border-deep-moss/15 py-5 first:border-t-0 first:pt-0 md:grid-cols-[minmax(0,160px)_minmax(0,1fr)] md:gap-6 md:items-baseline">
                @if($row['label'] !== '')
                  <dt class="event-meta__label font-sans text-xs font-semibold uppercase tracking-widest text-faded-olive">
                    {{ esc_html($row['label']) }}
                  </dt>
                @endif
                <dd class="event-meta__value m-0 font-sans text-base text-deep-moss md:text-lg">
                  {{ esc_html($row['value']) }}
                </dd>
              </div>
            @endforeach
          </dl>
        @endif

        @if($accessibilityLines !== [])
          <p class="event-meta__accessibility @if($rows !== []) mt-6 border-t border-deep-moss/15 pt-6 @endif font-sans text-sm font-light leading-6 text-deep-moss/85">
            @foreach($accessibilityLines as $i => $line)
              @if($i > 0)<br />@endif
              {{ esc_html(trim($line)) }}
            @endforeach
          </p>
        @endif

        @if($hasCta)
          {{-- Hand-rolled link (not the partial) because external-link CTAs need an
               inline `sr-only` span the partial doesn't model. Class spine matches
               the partial — `btn btn-primary` — so hover stays consistent with every
               other CTA on the site. --}}
          <div class="event-meta__actions @if($rows !== [] || $accessibilityLines !== []) mt-8 border-t border-deep-moss/15 pt-8 @endif flex">
            <a
              class="btn btn-primary"
              href="{{ esc_url($ctaUrl) }}"
              @if($ctaIsExternal) target="_blank" rel="noopener noreferrer" @endif>
              {{ esc_html($ctaLabel) }}
              @if($ctaIsExternal)
                <span class="sr-only">{{ __('(opens in new tab)', 'culvers') }}</span>
              @endif
            </a>
          </div>
        @endif
      </div>
    </div>
  </section>
@endif
