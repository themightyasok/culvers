{{-- Exported from Developer Release mega dropdown: IG `72:4997`, Facebook `72:5000` (`exportAsync({ format: 'SVG_STRING' })`). --}}
{{-- IMPORTANT: Tailwind `@include`s share one PHP variable scope via BladeOne. Never use `$class`/`$variant` here —
     those collide with unrelated partials rendered later same request (see `components.button`). Pass `social_*` keys. --}}
@php
  /** @var 'instagram'|'facebook'|'whatsapp' $social_icon_variant */
  $social_icon_variant ??= 'instagram';
  /** @var string Tailwind sizing + inherit colour (expects `text-*` on link). */
  $socialIconClass = trim((string) ($social_icon_class ?? ''));

  $basename = match ($social_icon_variant) {
      'facebook' => 'figma-social-facebook.svg',
      'whatsapp' => 'figma-social-whatsapp.svg',
      default => 'figma-social-instagram.svg',
  };
  $absolute = get_template_directory() . '/resources/images/social/' . $basename;

  $iconSvg = '';
  if ($socialIconClass !== '') {
      $markup = is_readable($absolute) ? (string) file_get_contents($absolute) : '';
      if ($markup !== '') {
          // Single-line replace: prepend classes + semantics (file keeps Figma geometry only).
          $iconSvg = (string) preg_replace(
              '/<svg\b/',
              '<svg class="' . htmlspecialchars($socialIconClass, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true" focusable="false"',
              $markup,
              1,
          );
      }
  }
@endphp
@if($iconSvg !== '')
  {!! $iconSvg !!}
@endif
