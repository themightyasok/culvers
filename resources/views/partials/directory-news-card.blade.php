@php
  /**
   * News directory card — back-compat shim.
   *
   * The card markup has moved to the canonical
   * {@see resources/views/partials/directory-card.blade.php} partial; per-CPT
   * field resolution lives in
   * {@see App\Directory\Cards\DirectoryCardSpecFactory::forNews()}.
   */
  $directory_card_spec = \App\Directory\Cards\DirectoryCardSpecFactory::forNews(
      isset($directory_card_post_id) ? (int) $directory_card_post_id : (int) get_the_ID()
  );
@endphp
@include('partials.directory-card', ['directory_card_spec' => $directory_card_spec])
