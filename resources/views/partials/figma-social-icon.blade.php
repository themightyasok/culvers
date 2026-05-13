{{-- Exported from Developer Release mega dropdown: IG `72:4997`, Facebook `72:5000` (`exportAsync({ format: 'SVG_STRING' })`). --}}
@php
  $variant ??= 'instagram';
  $class ??= '';

  /** @var string Tailwind sizing + inherit colour (expects `text-*` on link). */

  $basename = ($variant === 'facebook') ? 'figma-social-facebook.svg' : 'figma-social-instagram.svg';
  $absolute = get_template_directory() . '/resources/images/social/' . $basename;

  $iconSvg = '';
  if ($class !== '') {
      $markup = is_readable($absolute) ? (string) file_get_contents($absolute) : '';
      if ($markup !== '') {
          // Single-line replace: prepend classes + semantics (file keeps Figma geometry only).
          $iconSvg = (string) preg_replace(
              '/<svg\b/',
              '<svg class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true" focusable="false"',
              $markup,
              1,
          );
      }
  }
@endphp
@if($iconSvg !== '')
  {!! $iconSvg !!}
@endif
