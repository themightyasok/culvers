<?php

declare(strict_types=1);

namespace App\Nav;

/**
 * Optional mega-menu hover preview image per submenu item (attachment ID).
 */
final class NavMenuItemMeta
{
    public const META_PREVIEW_ATTACHMENT = '_culvers_mega_preview_attachment_id';

    /** Remote preview URL (e.g. Figma MCP export); used when no attachment is set. */
    public const META_PREVIEW_URL = '_culvers_mega_preview_url';

    public static function register(): void
    {
        register_post_meta('nav_menu_item', self::META_PREVIEW_ATTACHMENT, [
            'type' => 'integer',
            'single' => true,
            'default' => 0,
            'show_in_rest' => false,
            'auth_callback' => static fn (): bool => current_user_can('edit_theme_options'),
        ]);

        register_post_meta('nav_menu_item', self::META_PREVIEW_URL, [
            'type' => 'string',
            'single' => true,
            'default' => '',
            'show_in_rest' => false,
            'auth_callback' => static fn (): bool => current_user_can('edit_theme_options'),
        ]);

        add_action('wp_nav_menu_item_custom_fields', [self::class, 'renderField'], 10, 4);
        add_action('wp_update_nav_menu_item', [self::class, 'saveField'], 10, 3);
    }

    /**
     * @param int|string $item_id
     */
    public static function renderField($item_id, \WP_Post $item, int $depth, \stdClass|null $args): void
    {
        unset($item, $args);
        if ($depth === 0) {
            return;
        }

        $value = (int) get_post_meta((int) $item_id, self::META_PREVIEW_ATTACHMENT, true);
        ?>
        <p class="description description-wide culvers-mega-preview-field">
            <label for="culvers-mega-preview-<?php echo esc_attr((string) $item_id); ?>">
                <?php esc_html_e('Mega menu preview image (attachment ID)', 'culvers'); ?>
            </label>
            <input
                type="number"
                min="0"
                step="1"
                class="widefat"
                id="culvers-mega-preview-<?php echo esc_attr((string) $item_id); ?>"
                name="culvers-mega-preview[<?php echo esc_attr((string) $item_id); ?>]"
                value="<?php echo $value > 0 ? esc_attr((string) $value) : ''; ?>"
            />
            <span class="description"><?php esc_html_e('Shown when this submenu link is hovered. Falls back to the linked page featured image if empty.', 'culvers'); ?></span>
        </p>
        <?php
    }

    public static function saveField(int $menu_id, int $menu_item_db_id, array $menu_item_data): void
    {
        unset($menu_id, $menu_item_data);
        if (! isset($_POST['culvers-mega-preview']) || ! is_array($_POST['culvers-mega-preview'])) {
            return;
        }

        $posted = wp_unslash($_POST['culvers-mega-preview']);
        if (! is_array($posted) || ! isset($posted[$menu_item_db_id])) {
            return;
        }

        $raw = $posted[$menu_item_db_id];
        $aid = is_numeric($raw) ? (int) $raw : 0;
        if ($aid <= 0) {
            delete_post_meta($menu_item_db_id, self::META_PREVIEW_ATTACHMENT);

            return;
        }

        if (get_post_type($aid) !== 'attachment') {
            return;
        }

        update_post_meta($menu_item_db_id, self::META_PREVIEW_ATTACHMENT, $aid);
    }
}
