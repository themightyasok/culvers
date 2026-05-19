@php
  use App\Helpers\Component;

  /**
   * Centre map category row — shared by mobile 2-col grid and desktop accordion list.
   *
   * @var array{label: string, slug: string, url: string} $cat
   * @var string $catSlugJson
   * @var string $catLabelJson
   * @var bool $isAll
   */
  $cat = is_array($cat ?? null) ? $cat : [];
  $catSlugJson = (string) ($catSlugJson ?? '""');
  $catLabelJson = (string) ($catLabelJson ?? '""');
  $isAll = ! empty($isAll);
  $catUrl = trim((string) ($cat['url'] ?? ''));
  $rowClass = 'centre-map__category group flex w-full items-start gap-3 text-left font-label text-xs font-semibold uppercase leading-6 tracking-wide text-light-green transition hover:text-glowleaf culvers-focus-ring-compact ' . Component::centreMapCategoryMobileClasses() . ' max-lg:items-center max-lg:gap-3 lg:items-center lg:rounded-md lg:px-1 lg:py-1.5 lg:font-sans lg:text-sm lg:font-semibold lg:uppercase lg:tracking-[0.18em]';
  $bulletClass = 'centre-map__category-bullet mt-0.5 inline-flex size-3 shrink-0 items-center justify-center rounded-full border border-lighter-cream/60 transition max-lg:mt-0 lg:mt-0';
@endphp
@if($catUrl !== '')
  <a
    href="{{ esc_url($catUrl) }}"
    class="{{ $rowClass }}">
    <span class="{{ $bulletClass }}" aria-hidden="true"></span>
    <span class="min-w-0 text-pretty">{{ esc_html($cat['label'] ?? '') }}</span>
  </a>
@else
  <button
    type="button"
    class="{{ $rowClass }}"
    :class="activeCategorySlug === {{ $catSlugJson }} ? 'text-glowleaf' : ''"
    @click="@if($isAll)activeCategorySlug = ''; activeCategoryLabel = '';@else activeCategorySlug = activeCategorySlug === {{ $catSlugJson }} ? '' : {{ $catSlugJson }}; activeCategoryLabel = activeCategorySlug === {{ $catSlugJson }} ? {{ $catLabelJson }} : '';@endif">
    <span
      class="{{ $bulletClass }}"
      :class="activeCategorySlug === {{ $catSlugJson }} ? 'centre-map__category-bullet--active border-glowleaf bg-glowleaf' : ''"
      aria-hidden="true">
    </span>
    <span class="min-w-0 text-pretty">{{ esc_html($cat['label'] ?? '') }}</span>
  </button>
@endif
