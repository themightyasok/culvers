<?php

declare(strict_types=1);

namespace App\Admin;

/**
 * WordPress admin sidebar order and housekeeping.
 *
 * Directory CPTs and their matching ACF “directory” options pages are
 * interleaved so editors see “Shops” → “Shop directory” → “Eat & Drink” → …
 * instead of all CPTs first and all archive settings buried under Appearance.
 */
final class AdminMenu
{
    /** @var int First slot after core “Pages” (20). */
    public const POS_SHOP = 21;

    public const POS_SHOP_DIRECTORY = 22;

    public const POS_EAT_DRINK = 23;

    public const POS_EAT_DRINK_DIRECTORY = 24;

    public const POS_EVENTS = 25;

    public const POS_EVENTS_DIRECTORY = 26;

    public const POS_OFFERS = 27;

    public const POS_OFFERS_DIRECTORY = 28;

    public const POS_NEWS = 29;

    public const POS_NEWS_DIRECTORY = 30;

    public const POS_CAREERS = 31;

    public const POS_CAREERS_DIRECTORY = 32;

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'removeCommentsMenu'], 999);
        add_action('admin_bar_menu', [self::class, 'removeCommentsAdminBarNode'], 999);
    }

    public static function removeCommentsMenu(): void
    {
        remove_menu_page('edit-comments.php');
    }

    public static function removeCommentsAdminBarNode(\WP_Admin_Bar $adminBar): void
    {
        $adminBar->remove_node('comments');
    }
}
