{{--
  Client-side pagination for directory archives — Figma `51:8828`.
  Works with `directoryArchive` Alpine (filters + paging share the same card set).
--}}
<nav
  class="directory-archive__pagination mt-10 flex flex-wrap items-center justify-center gap-3 sm:mt-12"
  aria-label="{{ esc_attr__('Directory pages', 'culvers') }}"
  x-show="totalPages > 1"
  x-cloak>
  <button
    type="button"
    class="directory-archive__pagination-arrow inline-flex size-[43px] shrink-0 items-center justify-center rounded-full border border-faded-olive/30 text-faded-olive transition hover:border-faded-olive hover:text-deep-moss disabled:pointer-events-none disabled:opacity-40 culvers-focus-ring-compact"
    x-bind:disabled="currentPage <= 1"
    x-on:click="goToPage(currentPage - 1)"
    aria-label="{{ esc_attr__('Previous page', 'culvers') }}">
    @include('partials.icons.figma-header-icon', [
        'header_icon_variant' => 'explore-arrow',
        'header_icon_class' => 'size-4 shrink-0 rotate-180',
    ])
  </button>

  <div class="flex flex-wrap items-center justify-center gap-2">
    <template x-for="page in pageNumbers" x-bind:key="page">
      <button
        type="button"
        class="directory-archive__pagination-page inline-flex size-[43px] shrink-0 items-center justify-center rounded-full font-sans text-xl font-light leading-none text-faded-olive transition culvers-focus-ring-compact"
        x-bind:class="page === currentPage ? 'bg-glowleaf font-medium text-deep-moss' : 'hover:text-deep-moss'"
        x-bind:aria-current="page === currentPage ? 'page' : false"
        x-bind:aria-label="'{{ esc_attr__('Page', 'culvers') }} ' + page"
        x-on:click="goToPage(page)"
        x-text="page">
      </button>
    </template>
  </div>

  <button
    type="button"
    class="directory-archive__pagination-arrow inline-flex size-[43px] shrink-0 items-center justify-center rounded-full border border-faded-olive/30 text-faded-olive transition hover:border-faded-olive hover:text-deep-moss disabled:pointer-events-none disabled:opacity-40 culvers-focus-ring-compact"
    x-bind:disabled="currentPage >= totalPages"
    x-on:click="goToPage(currentPage + 1)"
    aria-label="{{ esc_attr__('Next page', 'culvers') }}">
    @include('partials.icons.figma-header-icon', [
        'header_icon_variant' => 'explore-arrow',
        'header_icon_class' => 'size-4 shrink-0',
    ])
  </button>
</nav>
