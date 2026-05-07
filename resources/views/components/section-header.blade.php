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
  $headingTag = Component::headingTag($c['header_heading_level'] ?? 2);

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
  $alignClass = $isCenter ? 'text-center mx-auto' : 'text-left';

  $maxRaw = is_string($c['header_max_width'] ?? null) ? (string) $c['header_max_width'] : 'narrow';
  $maxWidthClass = match ($maxRaw) {
      'medium' => 'max-w-4xl',
      'full' => 'max-w-none',
      default => 'max-w-3xl',
  };

  $hasContent = $eyebrow !== '' || $heading !== '' || $bodyLines !== [];
@endphp

@if(! $hasContent)
  @if(current_user_can('edit_posts'))
    @include('partials.component-editor-placeholder', [
        'wrapperClasses' => $root,
        'message' => __('Section header — add an eyebrow, heading or body to display this band.', 'culvers'),
    ])
  @endif
@else
  <section
    class="section-header {{ esc_attr($root) }} text-deep-moss"
    data-component-root
    data-section-header>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      <div class="section-header__inner {{ $alignClass }} {{ $maxWidthClass }} flex flex-col">
        @if($eyebrow !== '')
          <p class="section-header__eyebrow font-sans text-xs font-semibold uppercase tracking-widest text-faded-olive">
            {{ esc_html($eyebrow) }}
          </p>
        @endif

        @if($heading !== '')
          <{{ $headingTag }}
            class="section-header__heading font-heading text-4xl leading-tight md:text-5xl lg:text-6xl @if($eyebrow !== '') mt-3 @endif">
            {{ esc_html($heading) }}
          </{{ $headingTag }}>
        @endif

        @if($bodyLines !== [])
          <div class="section-header__body font-sans text-base font-light leading-7 text-deep-moss/85 md:text-lg @if($heading !== '' || $eyebrow !== '') mt-5 md:mt-6 @endif">
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
