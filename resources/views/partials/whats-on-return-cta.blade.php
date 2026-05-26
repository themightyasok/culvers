@php
  use App\Helpers\Component;
@endphp
<div class="{{ Component::sectionBodyToCtaGapClasses('flex justify-center') }}">
  @include('components.button', [
      'label' => __("Return to what's on", 'culvers'),
      'href' => home_url('/whats-on/'),
  ])
</div>
