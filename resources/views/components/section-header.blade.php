@php
  use App\Helpers\Component;
  use App\Helpers\LayoutShell;

  /**
   * Section header — small text-only intro band: optional eyebrow + heading
   * + short body, alignment + body max-width configurable. Use for the
   * "Getting Here", "About Colchester", "Accessible Guide" intro patterns,
   * and any other section opener that isn't long-form (Content section)
   * or action-led (Info block).
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $eyebrow = trim((string) ($c['header_eyebrow'] ?? ''));
  $heading = trim((string) ($c['header_heading'] ?? ''));
  $headingTag = Component::headingTagFromComponent($c, 'header_heading_level', 2);

  $bodyRaw = trim((string) ($c['header_body'] ?? ''));
  $bodyWithBreaks = preg_replace('/<br\s*\/?>/i', "\n", $bodyRaw);
  $bodyLines = [];
  if ($bodyWithBreaks !== '') {
      $bodyLines = array_values(array_filter(array_map(static function (string $line): string {
          return trim($line);
      }, preg_split('/\r\n|\r|\n/', wp_strip_all_tags($bodyWithBreaks)))));
  }

  $alignRaw = is_string($c['header_align'] ?? null) ? (string) $c['header_align'] : 'center';
  $isCenter = $alignRaw !== 'left';
  $alignClass = $isCenter ? 'text-center mx-auto max-lg:px-4' : 'text-left max-lg:px-4';

  $maxRaw = is_string($c['header_max_width'] ?? null) ? (string) $c['header_max_width'] : 'narrow';
  $maxWidthClass = match ($maxRaw) {
      'medium' => 'max-w-4xl',
      'full' => 'max-w-none',
      default => 'max-w-3xl',
  };

  $hasContent = $eyebrow !== '' || $heading !== '' || $bodyLines !== [];
  $usesIntroStackGap = $heading !== '' && $bodyLines !== [] && $eyebrow === '';
  $bodyGapClass = ($heading !== '' || $eyebrow !== '') && ! $usesIntroStackGap
      ? Component::sectionHeadingToBodyGapClasses()
      : '';
  $headingTailGapClass = $heading !== '' && $bodyLines === []
      ? Component::sectionHeadingToFollowContentGapClasses()
      : '';
@endphp

@if(! $hasContent)
  @if(current_user_can('edit_posts'))
    @include('partials.component-editor-placeholder', [
        'wrapperClasses' => $root,
        'message' => __('Section header — add an eyebrow, heading or body to display this band.', 'culvers'),
    ])
  @endif
@else
  {{-- Default tone is Faded Olive (#4F5438) — Figma's section H2 token across the
       site (Home / Plan My Visit / Leasing / Guest Services / Contact). Deep Moss
       is reserved for callout bands (Travel Calculator) which set their own
       heading colour inline. --}}
  <section
    class="section-header {{ esc_attr($root) }} text-faded-olive"
    data-component-root
    data-section-header>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      <div
        class="section-header__inner {{ $alignClass }} {{ $maxWidthClass }} flex flex-col {{ $usesIntroStackGap ? 'section-intro-stack' : '' }}">
        @if($eyebrow !== '')
          <p class="section-header__eyebrow font-sans text-xs font-semibold uppercase tracking-widest text-faded-olive">
            {{ esc_html($eyebrow) }}
          </p>
        @endif

        @if($heading !== '')
          {{-- Section H2 (58 px desktop / 48 px mobile) in Faded Olive — matches Figma section
               H2 token; body retains Deep Moss for readability against the cream surface. --}}
          <{{ $headingTag }}
            class="section-header__heading {{ Component::sectionIntroHeadingClasses('text-faded-olive', trim(($eyebrow !== '' ? 'mt-3' : '') . ' ' . $headingTailGapClass)) }}">
            {{ esc_html($heading) }}
          </{{ $headingTag }}>
        @endif

        @if($bodyLines !== [])
          <div class="section-header__body {{ Component::sectionIntroBodyClasses('text-deep-moss/85', trim(($usesIntroStackGap ? Component::sectionIntroContentStackClasses($isCenter ? 'items-center' : 'items-start') : '') . ' ' . $bodyGapClass)) }}">
            @foreach($bodyLines as $i => $line)
              @if($i > 0)<br />@endif
              {{ esc_html(trim($line)) }}
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </section>
@endif
