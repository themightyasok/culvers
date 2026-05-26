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
  $filterOnly = ! empty($filterOnly);
  $catUrl = trim((string) ($cat['url'] ?? ''));
  $rowClass = 'centre-map__category group flex h-full w-full cursor-pointer items-center gap-3 text-left text-light-green transition hover:text-glowleaf culvers-focus-ring-compact lg:rounded-md lg:px-1 lg:py-1.5 ' . Component::centreMapFilterLabelClasses();
  $bulletClass = 'centre-map__category-bullet inline-flex size-3 shrink-0 items-center justify-center self-center rounded-full border border-lighter-cream/60 transition';
@endphp
@if($catUrl !== '' && ! $filterOnly)
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
      :class="activeCategorySlug === {{ $catSlugJson }} ? 'centre-map__category-bullet--active border-deep-moss bg-glowleaf' : ''"
      aria-hidden="true">
    </span>
    <span class="min-w-0 text-pretty">{{ esc_html($cat['label'] ?? '') }}</span>
  </button>
@endif
