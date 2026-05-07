@php
  /**
   * Directory career card — mirrors `directory-shop-card.blade.php` visually
   * so the careers archive is identical to the shop / eat-drink archives.
   *
   * Shape:
   *   • Dark moss tile, employer logo (white) centred in the upper portion.
   *   • White hairline divider.
   *   • Bottom strip: role title (h2) + employment-type meta line.
   *
   * Logo source: `career_employer_logo` (image). When empty the tile shows
   * a centred title lockup so a card never goes blank — same fallback as
   * `directory-shop-card.blade.php`.
   *
   * Filter hooks: the article exposes `data-category-slugs` (department)
   * and `data-type-slugs` (employment type, slugified) so the shared
   * `directoryArchive` Alpine module can filter without per-archive code.
   */

  /** @var \WP_Post $post */
  $post_id = isset($directory_card_post_id) ? (int) $directory_card_post_id : get_the_ID();

  $logo = function_exists('get_field') ? get_field('career_employer_logo', $post_id) : null;
  $logo_url = is_array($logo) && ! empty($logo['url']) ? (string) $logo['url'] : '';

  $employment_type_raw = function_exists('get_field') ? get_field('career_employment_type', $post_id) : '';
  $employment_type = is_string($employment_type_raw) ? trim($employment_type_raw) : '';

  $dept_slugs = wp_get_post_terms($post_id, 'culvers_career_department', ['fields' => 'slugs']);
  $dept_slugs = is_array($dept_slugs) ? $dept_slugs : [];

  /* Employment type lives in a text field; slugify so the shared filter
     module's `data-type-slugs` matches the sidebar's slugified options. */
  $type_slugs = $employment_type !== '' ? [sanitize_title($employment_type)] : [];

  $sort_title = strtolower((string) get_the_title($post_id));
@endphp
<article
  data-directory-card
  data-category-slugs="{{ esc_attr(implode(',', $dept_slugs)) }}"
  data-type-slugs="{{ esc_attr(implode(',', $type_slugs)) }}"
  data-sort-title="{{ esc_attr($sort_title) }}"
  class="directory-career-card min-w-0 w-full">
  <a
    href="{{ esc_url(get_permalink($post_id)) }}"
    class="group directory-career-card__link relative block w-full max-w-none overflow-hidden rounded-[11px] outline-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-glowleaf">
    <div class="relative h-[294px] w-full bg-dustleaf">
      @if ($logo_url !== '')
        <div
          class="directory-career-card__logo-slot pointer-events-none absolute inset-x-0 top-0 z-30 flex h-[213px] items-center justify-center px-8">
          <img
            src="{{ esc_url($logo_url) }}"
            alt=""
            class="max-h-[120px] w-auto max-w-[85%] object-contain brightness-0 invert"
            loading="lazy"
            decoding="async" />
        </div>
      @else
        <div
          class="directory-career-card__logo-slot pointer-events-none absolute inset-x-0 top-0 z-30 flex h-[213px] items-center justify-center px-6">
          <span class="text-center font-heading text-2xl font-medium text-white">{{ get_the_title($post_id) }}</span>
        </div>
      @endif

      <div class="pointer-events-none absolute left-0 right-0 top-[213px] z-40 h-px bg-white" aria-hidden="true"></div>

      <h2 class="absolute left-[23px] top-[233px] z-40 max-w-[calc(100%-46px)] font-sans text-2xl font-medium text-white">
        {{ get_the_title($post_id) }}
      </h2>
      @if ($employment_type !== '')
        <p class="absolute left-[23px] top-[263px] z-40 max-w-[calc(100%-46px)] font-sans text-sm font-light text-white">
          {{ esc_html($employment_type) }}
        </p>
      @endif
    </div>
  </a>
</article>
