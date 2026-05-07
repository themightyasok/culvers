@php
  /**
   * Shop directory card — back-compat shim.
   *
   * The card markup has moved to the canonical
   * {@see resources/views/partials/directory-card.blade.php} partial; per-CPT
   * field resolution lives in
   * {@see App\Directory\Cards\DirectoryCardSpecFactory::forShop()}.
   *
   * Archive templates keep their existing include paths so this file stays
   * around as a 6-line dispatcher — call sites don't need to know which CPT
   * resolver to invoke.
   *
   * Inputs (compatible with the legacy partial):
   *   • `$directory_card_post_id` — integer post ID (preferred from
   *     `shop-related-shops`, `three-card-block`, etc.).
   *   • Falls back to the loop post via `get_the_ID()`.
   */
  $directory_card_spec = \App\Directory\Cards\DirectoryCardSpecFactory::forShop(
      isset($directory_card_post_id) ? (int) $directory_card_post_id : (int) get_the_ID()
  );
@endphp
@include('partials.directory-card', ['directory_card_spec' => $directory_card_spec])
