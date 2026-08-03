<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Auto-unpublish {@see culvers_event} posts the calendar day after {@see event_ends_on}.
 *
 * Uses a daily WP-Cron sweep plus a one-shot schedule per event on save, so expiry
 * still runs if traffic is light. Empty end dates are left alone (recurring / ongoing).
 */
final class EventExpiry
{
    public const FIELD = 'event_ends_on';

    public const DAILY_HOOK = 'culvers_event_expiry_daily';

    public const SINGLE_HOOK = 'culvers_event_unpublish_post';

    private const META_UNPUBLISHED_AT = '_culvers_event_auto_unpublished_at';

    public static function register(): void
    {
        add_action('init', [self::class, 'ensureDailySchedule'], 30);
        add_action(self::DAILY_HOOK, static function (): void {
            self::unpublishExpired();
        });
        add_action(self::SINGLE_HOOK, static function (int|string $postId): void {
            self::unpublishPost($postId);
        }, 10, 1);
        add_action('acf/save_post', [self::class, 'onAcfSave'], 30);
        add_action('transition_post_status', [self::class, 'onStatusTransition'], 10, 3);

        add_filter('manage_culvers_event_posts_columns', [self::class, 'adminColumns']);
        add_action('manage_culvers_event_posts_custom_column', [self::class, 'renderAdminColumn'], 10, 2);
    }

    public static function ensureDailySchedule(): void
    {
        if (wp_next_scheduled(self::DAILY_HOOK) !== false) {
            return;
        }

        $tz = wp_timezone();
        $next = (new \DateTimeImmutable('tomorrow 00:10', $tz))->getTimestamp();
        wp_schedule_event($next, 'daily', self::DAILY_HOOK);
    }

    /**
     * Draft every published event whose end date is before today (site timezone).
     *
     * @return list<int> Unpublished post IDs
     */
    public static function unpublishExpired(): array
    {
        $today = (new \DateTimeImmutable('now', wp_timezone()))->format('Ymd');
        $unpublished = [];

        $query = new \WP_Query([
            'post_type' => 'culvers_event',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
            'meta_query' => [
                [
                    'key' => self::FIELD,
                    'value' => '',
                    'compare' => '!=',
                ],
                [
                    'key' => self::FIELD,
                    'value' => $today,
                    'compare' => '<',
                    'type' => 'CHAR',
                ],
            ],
        ]);

        foreach ($query->posts as $postId) {
            $id = (int) $postId;
            if ($id <= 0) {
                continue;
            }

            if (self::unpublishPost($id)) {
                $unpublished[] = $id;
            }
        }

        return $unpublished;
    }

    /**
     * @param int|string $postId Cron may pass a stringified ID.
     */
    public static function unpublishPost(int|string $postId): bool
    {
        $id = (int) $postId;
        if ($id <= 0) {
            return false;
        }

        $post = get_post($id);
        if (! $post instanceof \WP_Post || $post->post_type !== 'culvers_event') {
            return false;
        }

        if ($post->post_status !== 'publish') {
            return false;
        }

        $end = self::endDateYmd($id);
        if ($end === null) {
            return false;
        }

        $today = (new \DateTimeImmutable('now', wp_timezone()))->format('Ymd');
        if ($end >= $today) {
            return false;
        }

        $result = wp_update_post([
            'ID' => $id,
            'post_status' => 'draft',
        ], true);

        if (is_wp_error($result)) {
            error_log('[culvers][event-expiry] failed to draft #' . $id . ': ' . $result->get_error_message());

            return false;
        }

        update_post_meta($id, self::META_UNPUBLISHED_AT, gmdate('c'));
        wp_clear_scheduled_hook(self::SINGLE_HOOK, [$id]);

        return true;
    }

    public static function onAcfSave(int|string $postId): void
    {
        if (! is_numeric($postId)) {
            return;
        }

        self::rescheduleForPost((int) $postId);
    }

    public static function onStatusTransition(string $newStatus, string $oldStatus, \WP_Post $post): void
    {
        if ($post->post_type !== 'culvers_event') {
            return;
        }

        if ($newStatus === 'publish') {
            self::rescheduleForPost((int) $post->ID);

            return;
        }

        wp_clear_scheduled_hook(self::SINGLE_HOOK, [(int) $post->ID]);
    }

    public static function rescheduleForPost(int $postId): void
    {
        if ($postId <= 0 || get_post_type($postId) !== 'culvers_event') {
            return;
        }

        if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return;
        }

        wp_clear_scheduled_hook(self::SINGLE_HOOK, [$postId]);

        if (get_post_status($postId) !== 'publish') {
            return;
        }

        $end = self::endDateImmutable($postId);
        if ($end === null) {
            return;
        }

        $unpublishAt = $end->modify('+1 day')->setTime(0, 10);
        $timestamp = $unpublishAt->getTimestamp();

        if ($timestamp <= time()) {
            self::unpublishPost($postId);

            return;
        }

        wp_schedule_single_event($timestamp, self::SINGLE_HOOK, [$postId]);
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public static function adminColumns(array $columns): array
    {
        $ordered = [];
        foreach ($columns as $key => $label) {
            $ordered[$key] = $label;
            if ($key === 'title') {
                $ordered['event_ends_on'] = __('Ends', 'culvers');
            }
        }

        if (! isset($ordered['event_ends_on'])) {
            $ordered['event_ends_on'] = __('Ends', 'culvers');
        }

        return $ordered;
    }

    public static function renderAdminColumn(string $column, int $postId): void
    {
        if ($column !== 'event_ends_on') {
            return;
        }

        $end = self::endDateImmutable($postId);
        if ($end === null) {
            echo '&mdash;';

            return;
        }

        echo esc_html(wp_date('j M Y', $end->getTimestamp()));
    }

    public static function endDateYmd(int $postId): ?string
    {
        $end = self::endDateImmutable($postId);

        return $end?->format('Ymd');
    }

    public static function endDateImmutable(int $postId): ?\DateTimeImmutable
    {
        $raw = null;
        if (function_exists('get_field')) {
            $raw = get_field(self::FIELD, $postId);
        }
        if ($raw === null || $raw === false || $raw === '') {
            $raw = get_post_meta($postId, self::FIELD, true);
        }

        return self::parseDate($raw);
    }

    /**
     * @param mixed $raw
     */
    public static function parseDate(mixed $raw): ?\DateTimeImmutable
    {
        if (is_int($raw)) {
            $raw = (string) $raw;
        }
        if (! is_string($raw)) {
            return null;
        }

        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $tz = wp_timezone();
        foreach (['Ymd', 'Y-m-d', 'j F Y', 'd/m/Y'] as $format) {
            $dt = \DateTimeImmutable::createFromFormat('!' . $format, $raw, $tz);
            if (! $dt instanceof \DateTimeImmutable) {
                continue;
            }

            $errors = \DateTimeImmutable::getLastErrors();
            if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                continue;
            }

            return $dt;
        }

        return null;
    }
}
