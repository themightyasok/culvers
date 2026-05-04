<?php

/**
 * Theme filters.
 */

declare(strict_types=1);

namespace App;

/**
 * Shared document title for Blade (`@yield` headers and shared vars).
 */
function culvers_document_title(): string
{
    if (is_home()) {
        $home = get_option('page_for_posts', true);
        if ($home) {
            return get_the_title((int) $home);
        }

        return __('Latest posts', 'culvers');
    }

    if (is_archive()) {
        return get_the_archive_title();
    }

    if (is_search()) {
        return sprintf(
            /* translators: %s search query */
            __('Search results for %s', 'culvers'),
            esc_html(get_search_query())
        );
    }

    if (is_404()) {
        return __('Not found', 'culvers');
    }

    $title = get_the_title();
    if (is_page() && trim((string) $title) === '') {
        $slug = (string) get_post_field('post_name', get_queried_object_id());
        if ($slug !== '') {
            return ucwords(str_replace(['-', '_'], ' ', $slug));
        }
    }

    return (string) $title;
}
