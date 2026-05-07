{{--
  Culver Square CTA — single source of truth for every button / link CTA on
  the site. Wraps the `.btn` component family from `@layer components` in
  `resources/styles/app.css` so any future hover or geometry change happens
  in CSS, not in template strings.

  Required:
    'label' — visible button text (string).
  Optional:
    'href'       — set to render an <a>; omit for a <button>.
    'variant'    — 'primary' (default) | 'outline' | 'dark' (deep-moss fill,
                   glowleaf text — for use on pale-sage / light surfaces where
                   the brand-yellow primary doesn't carry contrast).
    'size'       — 'default' (omit) | 'large' (hero/banner) | 'form' (46px row).
    'type'       — only for <button>: 'submit' | 'button' (default) | 'reset'.
    'class'      — extra utilities appended to the class string.
    'attributes' — assoc array merged onto the element (Alpine bindings,
                   data-* attributes, aria-*, x-on:*, etc.).

  Examples (Blade syntax):
    Inline link CTA — @include 'components.button' with label + href.
    Submit button   — same call but no href, type='submit'.
    Hero CTA        — same call with size='large'.
    Form-row submit — same call with size='form' and type='submit'.

  Anything else (Alpine-bound dynamic label, screen-reader span inside the
  link, icon-only button, …): hand-roll the markup but keep the same class
  spine — `btn btn-{variant} btn-{size?}` — so every CTA hovers identically.
--}}
@php
  /** @var string|null $href */
  /** @var string|null $label */
  /** @var string|null $variant */
  /** @var string|null $size */
  /** @var string|null $type */
  /** @var string|null $class */
  /** @var array<string, scalar|null>|null $attributes */

  $allowedVariants = ['primary', 'outline', 'dark'];
  $variant = in_array($variant ?? 'primary', $allowedVariants, true)
      ? ($variant ?? 'primary')
      : 'primary';

  $allowedSizes = ['default', 'large', 'form'];
  $size = in_array($size ?? 'default', $allowedSizes, true) ? ($size ?? 'default') : 'default';

  $baseClass = 'btn btn-' . $variant;
  if ($size !== 'default') {
      $baseClass .= ' btn-' . $size;
  }

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
