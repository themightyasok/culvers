@php
  /**
   * Eat & Drink directory card — back-compat shim.
   *
   * The card markup has moved to the canonical
   * {@see resources/views/partials/directory-card.blade.php} partial; per-CPT
   * field resolution lives in
   * {@see App\Directory\Cards\DirectoryCardSpecFactory::forEatDrink()}.
   *
   * Archive templates keep their existing include paths so this file stays
   * around as a small dispatcher — call sites don't need to know which CPT
   * resolver to invoke.
   *
   * Shops flagged "Also list under Eat & Drink" are cross-listed onto this
   * archive (see App\Directory\DirectoryPostTypes::registerEatDrinkCrossListing),
   * so resolve by the post's real type rather than assuming eat & drink —
   * a cross-listed shop then renders with its own logo + storefront hover.
   */
  $directory_card_post = isset($directory_card_post_id) ? (int) $directory_card_post_id : (int) get_the_ID();
  $directory_card_spec = \App\Directory\Cards\DirectoryCardSpecFactory::forPostType(
      $directory_card_post,
      (string) get_post_type($directory_card_post)
  ) ?? \App\Directory\Cards\DirectoryCardSpecFactory::forEatDrink($directory_card_post);
@endphp
@include('partials.directory-card', ['directory_card_spec' => $directory_card_spec])
