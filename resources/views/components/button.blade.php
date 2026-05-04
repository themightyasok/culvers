{{--
  Culver Square CTAs — classes from `@layer components` in `resources/styles/app.css` (`.btn`, `.btn-primary`, `.btn-outline`).

  @include('components.button', ['label' => __('Explore', 'culvers'), 'href' => '#main', 'variant' => 'primary'])
  @include('components.button', ['label' => 'Send', 'variant' => 'primary', 'type' => 'submit'])
  Optional: `'class' => 'min-w-[140px]'`, `'attributes' => ['aria-expanded' => 'false']]`
--}}
@php
  /** @var string|null $href */
  /** @var string|null $label */
  /** @var string|null $variant */
  /** @var string|null $type */
  /** @var string|null $class */
  /** @var array<string, scalar|null>|null $attributes */

  $allowedVariants = ['primary', 'outline'];
  $variant = in_array($variant ?? 'primary', $allowedVariants, true) ? ($variant ?? 'primary') : 'primary';

  $baseClass = 'btn btn-' . $variant;
  $extraClass = trim($class ?? '');
  $classes = trim($baseClass . ($extraClass !== '' ? ' ' . $extraClass : ''));

  $attrHtml = '';
  foreach (($attributes ?? []) as $name => $value) {
      if ($value === null || $value === '') {
          continue;
      }
      $attrHtml .= sprintf(' %s="%s"', esc_attr((string) $name), esc_attr((string) $value));
  }
@endphp

@if(! empty($href))
  <a href="{{ esc_url($href) }}" class="{{ esc_attr($classes) }}"{!! $attrHtml !!}>
    {{ esc_html($label ?? '') }}
  </a>
@else
  <button type="{{ esc_attr($type ?? 'button') }}" class="{{ esc_attr($classes) }}"{!! $attrHtml !!}>
    {{ esc_html($label ?? '') }}
  </button>
@endif
