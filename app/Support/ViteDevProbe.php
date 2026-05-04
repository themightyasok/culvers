<?php

declare(strict_types=1);

namespace App\Support;

/**
 * HTTP probe for the local Vite dev server (HMR). Used only when {@see CULVERS_USE_VITE} is enabled.
 */
final class ViteDevProbe
{
    /**
     * True when the URL responds with a 2xx/3xx status. Restricted to loopback hosts for safety.
     */
    public static function localUrlOk(string $url): bool
    {
        $parsed = wp_parse_url($url);
        if (! isset($parsed['host']) || ! in_array($parsed['host'], ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        $response = wp_remote_get($url, [
            'timeout' => 0.5,
            'sslverify' => false,
            'headers' => [
                'Accept' => 'text/css,*/*;q=0.1',
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        return $code >= 200 && $code < 400;
    }
}
