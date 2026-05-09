{{-- Culver Square wordmark from Figma Menu component (~178×22, Glowleaf via currentColor). Override via Appearance → Custom Logo. --}}
@php
  /** @var string|null $class */
  $wordmark = get_template_directory() . '/resources/images/brand/culver-square-wordmark.svg';
  $svg = is_readable($wordmark) ? file_get_contents($wordmark) : '';
  if ($svg !== '') {
      $svg = preg_replace('/<svg\b/', '<svg class="h-full w-full"', $svg, 1);
  }
@endphp
@if($svg !== '')
  <span
    class="{{ $class ?? 'block h-[22px] w-[178px] max-w-full shrink-0 text-glowleaf [&_svg]:max-h-full [&_svg]:max-w-full' }}"
    aria-hidden="true">
    {!! $svg !!}
  </span>
@else
  <svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 178 22"
    class="{{ $class ?? 'block h-[22px] w-[178px] max-w-full shrink-0 text-glowleaf' }}"
    aria-hidden="true"
    focusable="false">
    <text
      x="0"
      y="17"
      fill="none"
      stroke="currentColor"
      stroke-width="0.9"
      font-family="Canela, Georgia, ui-serif, serif"
      font-size="17"
      font-weight="400"
      letter-spacing="0.12em">{{ __('CULVER', 'culvers') }}</text>
    <text
      x="112"
      y="17"
      fill="currentColor"
      font-family='commuters-sans, ui-sans-serif, system-ui, sans-serif'
      font-size="13"
      font-weight="700"
      letter-spacing="0.06em">{{ __('SQ.', 'culvers') }}</text>
  </svg>
@endif
