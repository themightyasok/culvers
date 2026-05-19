<?php

declare(strict_types=1);

namespace App\Admin;

/**
 * Hides WordPress's main content editor for post types built from ACF Page Components.
 */
final class HideClassicEditor
{
    /**
     * Must stay aligned with {@see \App\ComponentRegistry::registerFlexibleContent()} locations.
     *
     * @var list<string>
     */
    private const POST_TYPES = [
        'page',
        'culvers_shop',
        'culvers_eat_drink',
        'culvers_event',
        'culvers_offer',
        'culvers_news',
        'culvers_career',
    ];

    public static function register(): void
    {
        add_action('init', [self::class, 'removeEditorSupport'], 100);
    }

    public static function removeEditorSupport(): void
    {
        foreach (self::POST_TYPES as $postType) {
            if (! post_type_exists($postType)) {
                continue;
            }

            remove_post_type_support($postType, 'editor');
        }
    }
}
