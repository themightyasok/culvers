<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Default Page Components stacks for directory singles (shops, venues, events, careers).
 *
 * When a post has no saved flexible rows yet (null / false / empty array), editors
 * and the front end see the canonical layout order from the developer-release singles.
 * {@see self::maybePersistOnSave()} writes the stack on first save for new posts;
 * {@see scripts/directory-flexible-backfill.php} repairs existing records in bulk.
 */
final class DirectoryFlexibleDefaults
{
    /** @var array<string, list<string>> */
    private const LAYOUTS_BY_POST_TYPE = [
        'culvers_shop' => [
            'image_hero',
            'shop_intro_block',
            'shop_split_highlight',
            'shop_store_details',
            'opening_hours',
            'centre_map',
            'shop_related_shops',
        ],
        'culvers_eat_drink' => [
            'image_hero',
            'shop_intro_block',
            'shop_split_highlight',
            'shop_store_details',
            'opening_hours',
            'centre_map',
            'section_header',
            'three_card_block',
        ],
        'culvers_event' => [
            'image_hero',
            'section_header',
            'shop_split_highlight',
            'social_share',
            'three_card_block',
        ],
        'culvers_offer' => [
            'image_hero',
            'section_header',
            'shop_split_highlight',
            'social_share',
            'three_card_block',
        ],
        'culvers_news' => [
            'image_hero',
            'event_meta',
            'section_header',
            'shop_split_highlight',
            'section_header',
            'three_card_block',
        ],
        'culvers_career' => [
            'image_hero',
            'career_detail',
            'shop_split_highlight',
            'info_block',
        ],
    ];

    private static bool $persisting = false;

    public static function register(): void
    {
        add_filter('acf/load_value/name=components', [self::class, 'filterLoadComponents'], 10, 3);
        add_action('acf/save_post', [self::class, 'maybePersistOnAcfSave'], 20);
    }

    /**
     * Read the DB value only (bypasses {@see filterLoadComponents}).
     *
     * @return list<array<string, mixed>>|null Null when the meta key is not stored yet.
     */
    public static function storedComponents(int $postId): ?array
    {
        if (! metadata_exists('post', $postId, 'components')) {
            return null;
        }

        $raw = get_post_meta($postId, 'components', true);

        if (! is_array($raw)) {
            return [];
        }

        return self::normalizeStoredRows($raw);
    }

    /**
     * ACF may persist flexible layouts as layout-name strings or full row arrays.
     *
     * @param list<mixed> $raw
     *
     * @return list<array<string, mixed>>
     */
    private static function normalizeStoredRows(array $raw): array
    {
        $rows = [];

        foreach ($raw as $row) {
            if (is_string($row) && $row !== '') {
                $rows[] = ['acf_fc_layout' => $row];

                continue;
            }

            if (is_array($row) && isset($row['acf_fc_layout'])) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>>|list<string> $components
     *
     * @return list<string>
     */
    private static function layoutKeysFromStored(array $components): array
    {
        $keys = [];

        foreach ($components as $row) {
            if (is_string($row) && $row !== '') {
                $keys[] = $row;

                continue;
            }

            if (! is_array($row)) {
                continue;
            }

            $layout = isset($row['acf_fc_layout']) ? (string) $row['acf_fc_layout'] : '';
            if ($layout !== '') {
                $keys[] = $layout;
            }
        }

        return $keys;
    }

    /**
     * @return list<string>
     */
    public static function layoutKeysForPostType(string $postType): array
    {
        return self::LAYOUTS_BY_POST_TYPE[$postType] ?? [];
    }

    /**
     * @return list<array{acf_fc_layout: string}>
     */
    public static function defaultLayoutRowsFor(string $postType): array
    {
        $keys = self::layoutKeysForPostType($postType);
        if ($keys === []) {
            return [];
        }

        return array_map(
            static fn (string $layout): array => ['acf_fc_layout' => $layout],
            $keys
        );
    }

    /**
     * @return list<string>
     */
    public static function supportedPostTypes(): array
    {
        return array_keys(self::LAYOUTS_BY_POST_TYPE);
    }

    /**
     * @param mixed $components
     */
    public static function shouldPersistDefaults($components, string $postType): bool
    {
        return self::backfillPlan($components, $postType) !== null;
    }

    /**
     * Decide how to repair `components` for a directory single.
     *
     * - Empty / missing → full default stack.
     * - Lone `image_hero` stub → full default stack.
     * - Canonical prefix with trailing layouts missing → append missing rows (keeps editor data).
     * - Otherwise → leave untouched (custom or complete stack).
     *
     * @param mixed $components
     *
     * @return list<array<string, mixed>>|null Rows to persist, or null when no change.
     */
    public static function backfillPlan($components, string $postType): ?array
    {
        $expected = self::layoutKeysForPostType($postType);
        if ($expected === []) {
            return null;
        }

        if (! is_array($components) || $components === []) {
            return self::defaultLayoutRowsFor($postType);
        }

        $keys = self::layoutKeysFromStored($components);

        if ($keys === []) {
            return self::defaultLayoutRowsFor($postType);
        }

        if (count($keys) === 1 && $keys[0] === 'image_hero') {
            return self::defaultLayoutRowsFor($postType);
        }

        if ($keys === $expected) {
            return null;
        }

        $expectedPrefix = array_slice($expected, 0, count($keys));
        if ($keys === $expectedPrefix && count($keys) < count($expected)) {
            $missing = array_slice($expected, count($keys));
            $append = array_map(
                static fn (string $layout): array => ['acf_fc_layout' => $layout],
                $missing
            );

            return array_merge(self::normalizeStoredRows($components), $append);
        }

        return null;
    }

    public static function persistDefaultsForPost(int $postId): bool
    {
        $postType = get_post_type($postId);
        if (! is_string($postType)) {
            return false;
        }

        if (! function_exists('update_field')) {
            return false;
        }

        $stored = self::storedComponents($postId);
        $plan = self::backfillPlan($stored, $postType);
        if ($plan === null) {
            return false;
        }

        self::$persisting = true;
        $result = update_field('components', $plan, $postId);
        self::$persisting = false;

        return $result !== false;
    }

    /**
     * @param string|int $postId
     */
    public static function maybePersistOnAcfSave($postId): void
    {
        if (self::$persisting || ! is_numeric($postId)) {
            return;
        }

        $pid = (int) $postId;
        if ($pid <= 0 || wp_is_post_revision($pid) || wp_is_post_autosave($pid)) {
            return;
        }

        $postType = get_post_type($pid);
        if (! is_string($postType) || ! isset(self::LAYOUTS_BY_POST_TYPE[$postType])) {
            return;
        }

        self::persistDefaultsForPost($pid);
    }

    /**
     * @param mixed $value
     * @param string|int|false $postId
     * @param array<string, mixed> $field
     * @return mixed
     */
    public static function filterLoadComponents($value, $postId, array $field)
    {
        unset($field);

        if (! is_numeric($postId)) {
            return $value;
        }

        $pid = (int) $postId;
        $postType = get_post_type($pid);
        if (! is_string($postType) || ! isset(self::LAYOUTS_BY_POST_TYPE[$postType])) {
            return $value;
        }

        $plan = self::backfillPlan($value, $postType);
        if ($plan !== null) {
            return $plan;
        }

        if (is_array($value) && $value !== []) {
            return $value;
        }

        if ($value !== null && $value !== false && ! is_array($value)) {
            return $value;
        }

        return self::defaultLayoutRowsFor($postType);
    }
}
