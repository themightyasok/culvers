{{-- Newsletter submit arrow — path from Figma Developer Release (`get_design_context` Vector / node 2:934). File KoBl6rTY98YnvusBgKLx4A. --}}
@php
  $arrowClass = isset($arrowClass) && is_string($arrowClass) && $arrowClass !== ''
      ? $arrowClass
      : 'block size-4 shrink-0 text-current';
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" aria-hidden="true" class="{{ esc_attr($arrowClass) }}">
  <path fill="currentColor" d="M12.175 9H0V7H12.175L6.575 1.4L8 0L16 8L8 16L6.575 14.6L12.175 9Z" />
</svg>
