{{--
  Empty-state notice for logged-in editors — consistent across flexible layouts.
  Expects: $wrapperClasses (string, typically trim(grid + padding)), $message (string).
--}}
@php
  $wrapperClasses = isset($wrapperClasses) && is_string($wrapperClasses) ? trim($wrapperClasses) : '';
  $message = isset($message) && is_string($message) ? $message : '';
@endphp

@if(current_user_can('edit_posts') && $message !== '')
  <div class="{{ esc_attr($wrapperClasses) }} rounded border border-amber-400 bg-amber-50 px-4 py-3 text-amber-950">
    {{ $message }}
  </div>
@endif
