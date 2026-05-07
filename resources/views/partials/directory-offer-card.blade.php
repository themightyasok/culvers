@php
  /**
   * Offer card — used on /latest-offers/ and anywhere a
   * `culvers_offer` needs to render in the directory grid.
   *
   * Mirrors {@see directory-event-card.blade.php} structurally so the four
   * directory archives (shop / eat-drink / events / offers) all read as
   * siblings: 294px-tall moss block (logo slot ↔ feature photo on hover),
   * white separator, title + subtitle band.
   *
   * Offer mapping:
   *   • Logo slot      → primary category term name (events have no brand logo)
   *   • Hover photo    → post thumbnail
   *   • Bottom title   → offer title
   *   • Bottom subtitle→ "validity · venue" pair, falling back to either alone
   *
   * @var \WP_Post $post
   */
  $post_id = isset($directory_card_post_id) ? (int) $directory_card_post_id : get_the_ID();

  $feat_thumb_raw = get_the_post_thumbnail_url($post_id, 'large');
  $feat_thumb_url = is_string($feat_thumb_raw) && $feat_thumb_raw !== '' ? $feat_thumb_raw : '';
  $has_hover_photo = $feat_thumb_url !== '';

  $validity_raw = function_exists('get_field') ? get_field('offer_card_validity', $post_id) : '';
  $venue_raw = function_exists('get_field') ? get_field('offer_card_venue', $post_id) : '';

  $validity = is_string($validity_raw) ? trim($validity_raw) : '';
  $venue = is_string($venue_raw) ? trim($venue_raw) : '';

  if ($validity !== '' && $venue !== '') {
      $subtitle = $venue;
  } elseif ($venue !== '') {
      $subtitle = $venue;
  } else {
      $subtitle = $validity;
  }

  $cat_terms = wp_get_post_terms($post_id, 'culvers_offer_category');
  $primary_category_name = '';
  $cat_slugs = [];
  if (is_array($cat_terms)) {
      foreach ($cat_terms as $term) {
          if ($term instanceof \WP_Term) {
              $cat_slugs[] = $term->slug;
              if ($primary_category_name === '') {
                  $primary_category_name = (string) $term->name;
              }
          }
      }
  }

  $sort_title = strtolower((string) get_the_title($post_id));

  $logoFadeClasses = $has_hover_photo
      ? 'transition-opacity duration-300 ease-out motion-reduce:transition-none group-hover:opacity-0 group-focus-within:opacity-0'
      : '';
@endphp
<article
  data-directory-card
  data-category-slugs="{{ esc_attr(implode(',', $cat_slugs)) }}"
  data-sort-title="{{ esc_attr($sort_title) }}"
  class="directory-shop-card min-w-0 w-full">
  <a
    href="{{ esc_url(get_permalink($post_id)) }}"
    class="group directory-shop-card__link relative block w-full max-w-none overflow-hidden rounded-[11px] outline-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-glowleaf">
    <div class="relative h-[294px] w-full bg-dustleaf">
      @if ($has_hover_photo)
        <img
          src="{{ esc_url($feat_thumb_url) }}"
          alt=""
          class="directory-shop-card__hover-photo absolute inset-0 z-10 size-full rounded-[11px] object-cover opacity-0 transition-opacity duration-300 ease-out motion-reduce:transition-none group-hover:opacity-100 group-focus-within:opacity-100"
          loading="lazy"
          decoding="async" />
        <div
          class="directory-shop-card__photo-overlay pointer-events-none absolute inset-0 z-20 rounded-[11px] opacity-0 transition-opacity duration-300 ease-out motion-reduce:transition-none group-hover:opacity-100 group-focus-within:opacity-100"
          aria-hidden="true"></div>
      @endif

      <div
        class="directory-shop-card__logo-slot pointer-events-none absolute inset-x-0 top-0 z-30 flex h-[213px] items-center justify-center px-6 {{ $logoFadeClasses }}">
        @if ($primary_category_name !== '')
          <span class="text-center font-heading text-2xl font-medium text-white">{{ esc_html($primary_category_name) }}</span>
        @else
          <span class="text-center font-heading text-2xl font-medium text-white">{{ get_the_title($post_id) }}</span>
        @endif
      </div>

      <div class="pointer-events-none absolute left-0 right-0 top-[213px] z-40 h-px bg-white" aria-hidden="true"></div>

      <h2 class="absolute left-[23px] top-[233px] z-40 max-w-[calc(100%-46px)] font-sans text-2xl font-medium text-white">
        {{ get_the_title($post_id) }}
      </h2>
      @if ($subtitle !== '')
        <p class="absolute left-[23px] top-[263px] z-40 max-w-[calc(100%-46px)] font-sans text-sm font-light text-white">
          {{ esc_html($subtitle) }}
        </p>
      @endif
    </div>
  </a>
</article>
