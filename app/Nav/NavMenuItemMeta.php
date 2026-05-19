<?php

declare(strict_types=1);

namespace App\Nav;

/**
 * Mega-menu hover preview image per submenu item (Media Library attachment).
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

        $itemId = (int) $item_id;
        $attachmentId = (int) get_post_meta($itemId, self::META_PREVIEW_ATTACHMENT, true);
        $thumbUrl = $attachmentId > 0 ? wp_get_attachment_image_url($attachmentId, 'medium') : false;
        $hasImage = is_string($thumbUrl) && $thumbUrl !== '';
        ?>
        <p class="description description-wide culvers-mega-preview-field">
            <label for="culvers-mega-preview-<?php echo esc_attr((string) $itemId); ?>">
                <?php esc_html_e('Mega menu preview image', 'culvers'); ?>
            </label>
            <input
                type="hidden"
                class="culvers-mega-preview__input"
                id="culvers-mega-preview-<?php echo esc_attr((string) $itemId); ?>"
                name="culvers-mega-preview[<?php echo esc_attr((string) $itemId); ?>]"
                value="<?php echo $attachmentId > 0 ? esc_attr((string) $attachmentId) : ''; ?>"
            />
            <span class="culvers-mega-preview-field__frame">
                <span class="culvers-mega-preview-field__thumb">
                    <img
                        class="culvers-mega-preview__preview"
                        src="<?php echo $hasImage ? esc_url($thumbUrl) : ''; ?>"
                        alt=""
                        <?php echo $hasImage ? '' : 'hidden'; ?>
                    />
                    <span class="culvers-mega-preview__placeholder culvers-mega-preview-field__placeholder"<?php echo $hasImage ? ' hidden' : ''; ?>>
                        <?php esc_html_e('No image', 'culvers'); ?>
                    </span>
                </span>
                <span class="culvers-mega-preview-field__actions">
                    <button
                        type="button"
                        class="button culvers-mega-preview__select">
                        <?php echo $hasImage
                            ? esc_html__('Change image', 'culvers')
                            : esc_html__('Select image', 'culvers'); ?>
                    </button>
                    <button
                        type="button"
                        class="button-link button-link-delete culvers-mega-preview__remove"
                        <?php echo $hasImage ? '' : ' hidden'; ?>>
                        <?php esc_html_e('Remove image', 'culvers'); ?>
                    </button>
                </span>
            </span>
            <span class="description">
                <?php esc_html_e(
                    'Large image on the right of the mega menu when this submenu link is hovered. '
                    . 'If empty, the linked page’s featured image is used when available.',
                    'culvers'
                ); ?>
            </span>
        </p>
        <?php
    }

    /**
     * @param array<string, mixed> $menu_item_data
     */
    public static function saveField(int $menu_id, int $menu_item_db_id, array $menu_item_data): void
    {
        unset($menu_id, $menu_item_data);
        if (! isset($_POST['culvers-mega-preview']) || ! is_array($_POST['culvers-mega-preview'])) {
            return;
        }

        $posted = wp_unslash($_POST['culvers-mega-preview']);
        if (! isset($posted[$menu_item_db_id])) {
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
        delete_post_meta($menu_item_db_id, self::META_PREVIEW_URL);
    }
}
