<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Resolves the page title surfaced to Blade via the shared `title` variable.
 *
 * Wired up in `functions.php` (`wp` action) and rendered by `partials.page-header`.
 * Treats Posts page (`page_for_posts`), archives, search, 404 and slug fallback
 * before falling back to `get_the_title()`.
 */
final class DocumentTitle
{
    public static function current(): string
    {
        if (is_home()) {
            $home = (int) get_option('page_for_posts', 0);
            if ($home > 0) {
                return get_the_title($home);
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
        if (is_page() && trim($title) === '') {
            $slug = (string) get_post_field('post_name', get_queried_object_id());
            if ($slug !== '') {
                return ucwords(strtr($slug, ['-' => ' ', '_' => ' ']));
            }
        }

        return $title;
    }
}
