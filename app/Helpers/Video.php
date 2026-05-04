<?php

namespace App\Helpers;

/**
 * Video helper utilities.
 *
 * Handles parsing YouTube URLs/iframe snippets and building safe embed URLs.
 */
class Video
{
    private const YOUTUBE_ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';

    /**
     * Build a privacy-enhanced YouTube embed URL.
     *
     * @param string|null $input YouTube URL, ID, or iframe embed markup
     * @param array<string, bool> $options Embed options
     */
    public static function youtubeEmbedUrl(?string $input, array $options = []): ?string
    {
        $videoId = self::extractYouTubeId($input);
        if ($videoId === null) {
            return null;
        }

        $startSeconds = self::extractYouTubeStartTime($input);

        $defaults = [
            'autoplay' => true,
            'controls' => false,
            'loop' => true,
            'modestbranding' => true,
            'mute' => true,
            'playsinline' => true,
            'rel' => false,
        ];

        $config = array_merge($defaults, $options);
        $query = [
            'autoplay' => ! empty($config['autoplay']) ? '1' : '0',
            'controls' => ! empty($config['controls']) ? '1' : '0',
            'iv_load_policy' => '3',
            'loop' => ! empty($config['loop']) ? '1' : '0',
            'modestbranding' => ! empty($config['modestbranding']) ? '1' : '0',
            'mute' => ! empty($config['mute']) ? '1' : '0',
            'playsinline' => ! empty($config['playsinline']) ? '1' : '0',
            'rel' => ! empty($config['rel']) ? '1' : '0',
        ];

        if ($startSeconds !== null && $startSeconds > 0) {
            $query['start'] = (string) (int) $startSeconds;
        }

        if (! empty($config['loop'])) {
            // YouTube requires playlist=<video_id> for single-video looping.
            $query['playlist'] = $videoId;
        }

        $url = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($videoId) . '?' .
            http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        $sanitized = esc_url_raw($url);

        return $sanitized !== '' ? $sanitized : null;
    }

    /**
     * Extract start time (seconds) from YouTube URL if present.
     * Supports ?t=30, &t=30, ?start=30, youtu.be/xxx?t=30
     */
    public static function extractYouTubeStartTime(?string $input): ?int
    {
        if (! is_string($input) || trim($input) === '') {
            return null;
        }

        $candidate = trim(html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if (stripos($candidate, '<iframe') !== false) {
            if (preg_match('/src=(["\'])(.*?)\1/i', $candidate, $matches) === 1) {
                $candidate = trim((string) ($matches[2] ?? ''));
            }
        }

        $parts = wp_parse_url($candidate);
        if (! is_array($parts)) {
            return null;
        }

        $query = (string) ($parts['query'] ?? '');
        if ($query === '') {
            return null;
        }

        $params = [];
        parse_str($query, $params);

        $t = $params['t'] ?? $params['start'] ?? null;
        if ($t === null || $t === '') {
            return null;
        }

        $seconds = 0;
        if (is_numeric($t)) {
            $seconds = (int) $t;
        } elseif (is_string($t)) {
            if (preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s?)?$/i', trim($t), $m)) {
                $seconds = ((int) ($m[1] ?? 0)) * 3600 + ((int) ($m[2] ?? 0)) * 60 + ((int) ($m[3] ?? 0));
            } elseif (is_numeric(trim($t))) {
                $seconds = (int) $t;
            }
        }

        return $seconds > 0 ? $seconds : null;
    }

    /**
     * Parse a YouTube ID from URL, raw ID, or iframe markup.
     */
    public static function extractYouTubeId(?string $input): ?string
    {
        if (! is_string($input)) {
            return null;
        }

        $candidate = trim(html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($candidate === '') {
            return null;
        }

        if (stripos($candidate, '<iframe') !== false) {
            if (preg_match('/src=(["\'])(.*?)\1/i', $candidate, $matches) === 1) {
                $candidate = trim((string) ($matches[2] ?? ''));
            }
        }

        if (preg_match(self::YOUTUBE_ID_PATTERN, $candidate) === 1) {
            return $candidate;
        }

        $parts = wp_parse_url($candidate);
        if (! is_array($parts)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $query = (string) ($parts['query'] ?? '');

        $videoId = '';

        if (self::isYouTubeShortHost($host)) {
            $segments = $path !== '' ? explode('/', $path) : [];
            $videoId = (string) ($segments[0] ?? '');
        } elseif (self::isYouTubeHost($host)) {
            if (str_starts_with($path, 'embed/')) {
                $videoId = self::firstPathSegment(substr($path, strlen('embed/')));
            } elseif (str_starts_with($path, 'shorts/')) {
                $videoId = self::firstPathSegment(substr($path, strlen('shorts/')));
            } elseif (str_starts_with($path, 'live/')) {
                $videoId = self::firstPathSegment(substr($path, strlen('live/')));
            } else {
                $queryParams = [];
                parse_str($query, $queryParams);
                $videoId = (string) ($queryParams['v'] ?? '');
            }
        }

        return self::validateYouTubeId($videoId);
    }

    private static function isYouTubeShortHost(string $host): bool
    {
        return in_array($host, ['youtu.be', 'www.youtu.be'], true);
    }

    private static function isYouTubeHost(string $host): bool
    {
        return in_array(
            $host,
            ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'],
            true
        );
    }

    private static function firstPathSegment(string $path): string
    {
        $trimmed = trim($path, '/');
        if ($trimmed === '') {
            return '';
        }

        $segments = explode('/', $trimmed);

        return (string) ($segments[0] ?? '');
    }

    private static function validateYouTubeId(string $id): ?string
    {
        $candidate = trim($id);
        if (preg_match(self::YOUTUBE_ID_PATTERN, $candidate) !== 1) {
            return null;
        }

        return $candidate;
    }
}
