{{-- Figma Developer Release header glyphs — geometry only; colour + size via Tailwind on include.
     `nav-chevron`: raw asset is 4×7 (Figma `2:94`); wrap with `rotate-90` for ▼ — see `header.blade.php`. --}}
@php
  /** @var string $header_icon_variant */
  $header_icon_variant ??= '';
  $headerIconClass = trim((string) ($header_icon_class ?? ''));

  $basename = match ($header_icon_variant) {
      'nav-chevron' => 'figma-nav-chevron.svg',
      'centre-map-desktop' => 'figma-centre-map-desktop.svg',
      'getting-here-desktop' => 'figma-getting-here-desktop.svg',
      'search-magnifier-desktop' => 'figma-search-magnifier-desktop.svg',
      'explore-arrow' => 'figma-explore-arrow.svg',
      'centre-map-mobile' => 'figma-centre-map-mobile.svg',
      'getting-here-mobile' => 'figma-getting-here-mobile.svg',
      'mobile-drawer-chevron' => 'figma-mobile-drawer-chevron.svg',
      'mobile-back-arrow' => 'figma-mobile-back-arrow.svg',
      default => '',
  };

  $iconSvg = '';
  if ($basename !== '' && $headerIconClass !== '') {
      $absolute = get_template_directory() . '/resources/images/header/' . $basename;
      $markup = is_readable($absolute) ? (string) file_get_contents($absolute) : '';
      if ($markup !== '') {
          $iconSvg = (string) preg_replace_callback(
              '/<svg\b([^>]*)>/',
              static function (array $matches) use ($headerIconClass): string {
                  $attrs = (string) preg_replace(
                      '/\s(?:width|height|preserveAspectRatio|style)="[^"]*"/',
                      '',
                      $matches[1],
                  );

                  return '<svg class="' . htmlspecialchars($headerIconClass, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true" focusable="false"' . $attrs . '>';
              },
              $markup,
              1,
          );
      }
  }
@endphp
@if($iconSvg !== '')
  {!! $iconSvg !!}
@endif
