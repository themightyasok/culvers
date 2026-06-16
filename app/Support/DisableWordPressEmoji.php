<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Disables WordPress core Twemoji replacement (inline CSS, footer loader module,
 * s.w.org dns-prefetch). Emoji in content still work as native Unicode; the CMS
 * no longer injects detection scripts into page source.
 */
final class DisableWordPressEmoji
{
    public static function register(): void
    {
        add_action('init', [self::class, 'disable'], 1);
    }

    public static function disable(): void
    {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
        remove_action('admin_enqueue_scripts', 'wp_enqueue_emoji_styles');
        remove_action('embed_head', 'print_emoji_detection_script');
        remove_action('enqueue_embed_scripts', 'wp_enqueue_emoji_styles');

        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

        add_filter('tiny_mce_plugins', [self::class, 'stripTinyMceEmojiPlugin']);
        add_filter('wp_resource_hints', [self::class, 'stripEmojiDnsPrefetch'], 10, 2);
    }

    /**
     * @param list<string>|mixed $plugins
     * @return list<string>|mixed
     */
    public static function stripTinyMceEmojiPlugin(mixed $plugins): mixed
    {
        if (! is_array($plugins)) {
            return $plugins;
        }

        return array_values(array_diff($plugins, ['wpemoji']));
    }

    /**
     * @param list<string>|mixed $urls
     * @return list<string>|mixed
     */
    public static function stripEmojiDnsPrefetch(mixed $urls, string $relationType): mixed
    {
        if ($relationType !== 'dns-prefetch' || ! is_array($urls)) {
            return $urls;
        }

        return array_values(array_filter(
            $urls,
            static fn (mixed $url): bool => ! is_string($url)
                || strpos($url, 'https://s.w.org/images/core/emoji/') === false
        ));
    }
}
