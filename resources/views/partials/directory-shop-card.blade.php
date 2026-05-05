@php
  /** @var \WP_Post $post */
  $post_id = get_the_ID();
  $logo = function_exists('get_field') ? get_field('shop_logo', $post_id) : null;
  $logo_url = is_array($logo) && ! empty($logo['url']) ? (string) $logo['url'] : '';

  $feat_thumb_raw = get_the_post_thumbnail_url($post_id, 'large');
  $feat_thumb_url = is_string($feat_thumb_raw) && $feat_thumb_raw !== '' ? $feat_thumb_raw : '';
  $has_hover_photo = $feat_thumb_url !== '';

  $hours_raw = function_exists('get_field') ? get_field('opening_hours_summary', $post_id) : '';
  $hours = is_string($hours_raw) && trim($hours_raw) !== '' ? trim($hours_raw) : __('Opening hours TBC', 'culvers');
  $cat_slugs = wp_get_post_terms($post_id, 'culvers_shop_category', ['fields' => 'slugs']);
  $type_slugs = wp_get_post_terms($post_id, 'culvers_shop_type', ['fields' => 'slugs']);
  $cat_slugs = is_array($cat_slugs) ? $cat_slugs : [];
  $type_slugs = is_array($type_slugs) ? $type_slugs : [];
  $sort_title = strtolower((string) get_the_title($post_id));

  $logoFadeClasses = $has_hover_photo
      ? 'transition-opacity duration-300 ease-out motion-reduce:transition-none group-hover:opacity-0 group-focus-within:opacity-0'
      : '';
@endphp
<article
  data-directory-card
  data-category-slugs="{{ esc_attr(implode(',', $cat_slugs)) }}"
  data-type-slugs="{{ esc_attr(implode(',', $type_slugs)) }}"
  data-sort-title="{{ esc_attr($sort_title) }}"
  class="directory-shop-card min-w-0 justify-self-center">
  <a
    href="{{ esc_url(get_permalink($post_id)) }}"
    class="group directory-shop-card__link relative block w-full max-w-[336px] translate-y-0 overflow-hidden rounded-[11px] outline-none transition-[transform,box-shadow] duration-300 ease-out hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-glowleaf motion-reduce:transition-none motion-reduce:hover:translate-y-0">
    <div class="relative h-[294px] w-full bg-dustleaf">
      @if ($has_hover_photo)
        <img
          src="{{ esc_url($feat_thumb_url) }}"
          alt=""
          class="directory-shop-card__hover-photo absolute inset-0 z-[1] size-full rounded-[11px] object-cover opacity-0 transition-opacity duration-300 ease-out motion-reduce:transition-none group-hover:opacity-100 group-focus-within:opacity-100"
          loading="lazy"
          decoding="async" />
        <div
          class="directory-shop-card__photo-overlay pointer-events-none absolute inset-0 z-[2] rounded-[11px] opacity-0 transition-opacity duration-300 ease-out motion-reduce:transition-none group-hover:opacity-100 group-focus-within:opacity-100"
          aria-hidden="true"></div>
      @endif

      @if ($logo_url !== '')
        <div
          class="directory-shop-card__logo-slot pointer-events-none absolute inset-x-0 top-0 z-[3] flex h-[213px] items-center justify-center px-8 {{ $logoFadeClasses }}">
          <img
            src="{{ esc_url($logo_url) }}"
            alt=""
            class="max-h-[120px] w-auto max-w-[85%] object-contain brightness-0 invert"
            loading="lazy"
            decoding="async" />
        </div>
      @else
        <div
          class="directory-shop-card__logo-slot pointer-events-none absolute inset-x-0 top-0 z-[3] flex h-[213px] items-center justify-center px-6 {{ $logoFadeClasses }}">
          <span class="text-center font-heading text-xl leading-snug text-white">{{ get_the_title($post_id) }}</span>
        </div>
      @endif

      <div class="pointer-events-none absolute left-0 right-0 top-[213px] z-[5] h-px bg-white" aria-hidden="true"></div>

      <h2 class="absolute left-[23px] top-[233px] z-[5] max-w-[calc(100%-46px)] font-sans text-[22px] font-medium leading-[26px] text-white">
        {{ get_the_title($post_id) }}
      </h2>
      <p class="absolute left-[23px] top-[263px] z-[5] max-w-[calc(100%-46px)] font-sans text-[14px] font-light leading-5 text-white">
        {{ esc_html($hours) }}
      </p>
    </div>
  </a>
</article>
