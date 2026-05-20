{{--
  Footer mobile accordion +/- (Figma Footer — Mobile/Default `2:1059`, 16×16 filled plus).
  Included via @include — expects $expandedExpression (Alpine), e.g. openWhatsHere.
--}}
<span
  class="inline-flex size-4 shrink-0 items-center justify-center text-lighter-cream"
  aria-hidden="true">
  <svg
    x-show="!({{ $expandedExpression }})"
    x-cloak
    class="block size-4"
    viewBox="0 0 16 16"
    fill="currentColor"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true">
    <path d="M6.85714 9.14286H0V6.85714H6.85714V0H9.14286V6.85714H16V9.14286H9.14286V16H6.85714V9.14286Z" />
  </svg>
  <svg
    x-show="{{ $expandedExpression }}"
    x-cloak
    class="block size-4"
    viewBox="0 0 16 16"
    fill="currentColor"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true">
    <path d="M0 6.85714H16V9.14286H0V6.85714Z" />
  </svg>
</span>
