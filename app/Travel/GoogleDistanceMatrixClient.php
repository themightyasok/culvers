<?php

declare(strict_types=1);

namespace App\Travel;

use App\Customizer\GoogleMapsCustomizer;

/**
 * Thin wrapper around Google's Distance Matrix API.
 *
 * Network calls go through `wp_remote_get`. Successful results are cached as
 * a transient keyed by `(origin, destination, mode, language, units)` so the
 * same lookup costs a single HTTP request per cache window.
 *
 * @phpstan-type RouteResult array{
 *     status: string,
 *     mode: string,
 *     distance_text: string,
 *     distance_value: int,
 *     duration_text: string,
 *     duration_value: int,
 *     resolved_origin: string,
 *     resolved_destination: string
 * }
 */
final class GoogleDistanceMatrixClient
{
    public const ALLOWED_MODES = ['driving', 'transit', 'walking', 'bicycling'];

    private const ENDPOINT = 'https://maps.googleapis.com/maps/api/distancematrix/json';

    private const CACHE_PREFIX = 'culvers_dist_matrix_';

    private const CACHE_TTL_SECONDS = 86400;

    private const REQUEST_TIMEOUT_SECONDS = 8;

    /**
     * @return RouteResult
     * @throws TravelCalculatorException
     */
    public function fetchRoute(string $origin, string $mode, string $language = 'en-GB', string $units = 'imperial'): array
    {
        $origin = trim($origin);
        if ($origin === '') {
            throw TravelCalculatorException::invalidOrigin();
        }
        if (! in_array($mode, self::ALLOWED_MODES, true)) {
            throw TravelCalculatorException::invalidMode($mode);
        }

        $apiKey = GoogleMapsCustomizer::apiKey();
        if ($apiKey === '') {
            throw TravelCalculatorException::missingApiKey();
        }

        $destination = self::buildDestinationParam();
        $cacheKey = self::cacheKey($origin, $destination, $mode, $language, $units);
        $cached = get_transient($cacheKey);
        if (is_array($cached) && isset($cached['status'])) {
            /** @var RouteResult $cached */
            return $cached;
        }

        $url = add_query_arg(
            [
                'origins' => $origin,
                'destinations' => $destination,
                'mode' => $mode,
                'language' => $language,
                'units' => $units,
                'key' => $apiKey,
            ],
            self::ENDPOINT
        );

        $response = wp_remote_get($url, [
            'timeout' => self::REQUEST_TIMEOUT_SECONDS,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            throw TravelCalculatorException::transport($response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            throw TravelCalculatorException::http($code);
        }

        $body = (string) wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            throw TravelCalculatorException::malformed();
        }

        $apiStatus = isset($decoded['status']) && is_string($decoded['status']) ? $decoded['status'] : '';
        if ($apiStatus !== 'OK') {
            throw TravelCalculatorException::apiStatus($apiStatus, isset($decoded['error_message']) && is_string($decoded['error_message']) ? $decoded['error_message'] : '');
        }

        $rows = isset($decoded['rows']) && is_array($decoded['rows']) ? $decoded['rows'] : [];
        $row = $rows[0] ?? null;
        $elements = is_array($row) && isset($row['elements']) && is_array($row['elements']) ? $row['elements'] : [];
        $element = $elements[0] ?? null;
        if (! is_array($element)) {
            throw TravelCalculatorException::malformed();
        }

        $elementStatus = isset($element['status']) && is_string($element['status']) ? $element['status'] : '';
        if ($elementStatus !== 'OK') {
            throw TravelCalculatorException::routeStatus($elementStatus);
        }

        $distance = is_array($element['distance'] ?? null) ? $element['distance'] : [];
        $duration = is_array($element['duration'] ?? null) ? $element['duration'] : [];

        $resolvedOrigins = isset($decoded['origin_addresses']) && is_array($decoded['origin_addresses'])
            ? $decoded['origin_addresses']
            : [];
        $resolvedDestinations = isset($decoded['destination_addresses']) && is_array($decoded['destination_addresses'])
            ? $decoded['destination_addresses']
            : [];

        $result = [
            'status' => 'OK',
            'mode' => $mode,
            'distance_text' => isset($distance['text']) ? (string) $distance['text'] : '',
            'distance_value' => isset($distance['value']) ? (int) $distance['value'] : 0,
            'duration_text' => isset($duration['text']) ? (string) $duration['text'] : '',
            'duration_value' => isset($duration['value']) ? (int) $duration['value'] : 0,
            'resolved_origin' => isset($resolvedOrigins[0]) ? (string) $resolvedOrigins[0] : $origin,
            'resolved_destination' => isset($resolvedDestinations[0]) ? (string) $resolvedDestinations[0] : $destination,
        ];

        set_transient($cacheKey, $result, self::CACHE_TTL_SECONDS);

        return $result;
    }

    private static function buildDestinationParam(): string
    {
        $placeId = GoogleMapsCustomizer::destinationPlaceId();
        if ($placeId !== '') {
            return 'place_id:' . $placeId;
        }

        return GoogleMapsCustomizer::destinationAddress();
    }

    private static function cacheKey(string $origin, string $destination, string $mode, string $language, string $units): string
    {
        return self::CACHE_PREFIX . md5(implode('|', [
            strtolower($origin),
            strtolower($destination),
            $mode,
            $language,
            $units,
        ]));
    }
}
