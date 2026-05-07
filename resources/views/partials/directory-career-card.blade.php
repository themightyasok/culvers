@php
  /**
   * Career directory card — back-compat shim.
   *
   * The card markup has moved to the canonical
   * {@see resources/views/partials/directory-card.blade.php} partial; per-CPT
   * field resolution lives in
   * {@see App\Directory\Cards\DirectoryCardSpecFactory::forCareer()}.
   *
   * Note: the career resolver intentionally returns an empty `hoverPhotoUrl`
   * to preserve the legacy "no hover overlay" behaviour for career cards.
   * If we want career cards to gain the hover-photo treatment, change the
   * factory — the canonical partial is already wired for it.
   */
  $directory_card_spec = \App\Directory\Cards\DirectoryCardSpecFactory::forCareer(
      isset($directory_card_post_id) ? (int) $directory_card_post_id : (int) get_the_ID()
  );
@endphp
@include('partials.directory-card', ['directory_card_spec' => $directory_card_spec])
