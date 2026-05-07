<?php

declare(strict_types=1);

namespace App\Travel;

use App\Customizer\GoogleMapsCustomizer;

/**
 * REST endpoint backing the front-end Travel Calculator component.
 *
 * `POST /wp-json/culvers/v1/travel-calculator`
 *   Body: { origin: string, mode: 'driving'|'transit'|'walking'|'bicycling' }
 *   Headers: X-WP-Nonce (wp_rest)
 *
 * The Google API key never leaves the server. Soft IP rate-limit (10/min) and
 * a 24h response cache (in {@see GoogleDistanceMatrixClient}) keep API costs
 * predictable.
 */
final class TravelCalculatorEndpoint
{
    public const NAMESPACE = 'culvers/v1';

    public const ROUTE = '/travel-calculator';

    private const RATE_LIMIT_REQUESTS = 10;

    private const RATE_LIMIT_WINDOW_SECONDS = 60;

    private const RATE_LIMIT_PREFIX = 'culvers_tc_rl_';

    public static function register(): void
    {
        register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods' => 'POST',
            'callback' => [self::class, 'handle'],
            'permission_callback' => [self::class, 'permission'],
            'args' => [
                'origin' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'mode' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => GoogleDistanceMatrixClient::ALLOWED_MODES,
                ],
            ],
        ]);
    }

    public static function permission(\WP_REST_Request $request): bool|\WP_Error
    {
        $nonce = $request->get_header('x_wp_nonce');
        if (! is_string($nonce) || $nonce === '' || ! wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error(
                'culvers_travel_invalid_nonce',
                __('Session expired — please refresh the page.', 'culvers'),
                ['status' => 403]
            );
        }

        if (self::isRateLimited()) {
            return new \WP_Error(
                'culvers_travel_rate_limited',
                __('Too many lookups — please wait a minute.', 'culvers'),
                ['status' => 429]
            );
        }

        return true;
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $origin = (string) $request->get_param('origin');
        $mode = (string) $request->get_param('mode');

        if (mb_strlen($origin) > 200) {
            return new \WP_Error(
                'culvers_travel_origin_too_long',
                __('Please use a shorter address.', 'culvers'),
                ['status' => 400]
            );
        }

        try {
            $client = new GoogleDistanceMatrixClient();
            $result = $client->fetchRoute($origin, $mode);
        } catch (TravelCalculatorException $e) {
            $status = match ($e->getCode()) {
                TravelCalculatorException::CODE_INVALID_ORIGIN,
                TravelCalculatorException::CODE_INVALID_MODE => 400,
                TravelCalculatorException::CODE_MISSING_API_KEY => 503,
                TravelCalculatorException::CODE_TRANSPORT,
                TravelCalculatorException::CODE_HTTP,
                TravelCalculatorException::CODE_MALFORMED => 502,
                TravelCalculatorException::CODE_API_STATUS,
                TravelCalculatorException::CODE_ROUTE_STATUS => 422,
                default => 500,
            };

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[culvers][travel-calculator] ' . $e->getMessage() . ' ' . wp_json_encode($e->getContext()));
            }

            return new \WP_Error('culvers_travel_failed', $e->getMessage(), ['status' => $status]);
        }

        return rest_ensure_response([
            'mode' => $result['mode'],
            'distance' => [
                'text' => $result['distance_text'],
                'value' => $result['distance_value'],
            ],
            'duration' => [
                'text' => $result['duration_text'],
                'value' => $result['duration_value'],
            ],
            'origin' => $result['resolved_origin'],
            'destination' => [
                'address' => $result['resolved_destination'],
                'label' => GoogleMapsCustomizer::destinationLabel(),
            ],
            'message' => self::buildResultMessage($result['mode'], $result['distance_text'], $result['duration_text']),
        ]);
    }

    private static function buildResultMessage(string $mode, string $distance, string $duration): string
    {
        $modeLabel = match ($mode) {
            'driving' => __('car', 'culvers'),
            'transit' => __('public transport', 'culvers'),
            'walking' => __('foot', 'culvers'),
            'bicycling' => __('bicycle', 'culvers'),
            default => $mode,
        };

        return sprintf(
            /* translators: 1: travel mode label (car/public transport/foot/bicycle), 2: distance, 3: duration */
            __('Your journey by %1$s is %2$s and it will take approximately %3$s.', 'culvers'),
            $modeLabel,
            $distance,
            $duration
        );
    }

    private static function isRateLimited(): bool
    {
        $key = self::RATE_LIMIT_PREFIX . md5(self::clientFingerprint());
        $count = get_transient($key);
        $count = is_int($count) ? $count : 0;

        if ($count >= self::RATE_LIMIT_REQUESTS) {
            return true;
        }

        set_transient($key, $count + 1, self::RATE_LIMIT_WINDOW_SECONDS);

        return false;
    }

    private static function clientFingerprint(): string
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR'])
            ? $_SERVER['REMOTE_ADDR']
            : 'unknown';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT'])
            ? $_SERVER['HTTP_USER_AGENT']
            : '';

        return $ip . '|' . substr($ua, 0, 64);
    }
}
