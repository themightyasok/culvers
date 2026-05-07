<?php

declare(strict_types=1);

namespace App\Travel;

/**
 * Translates Google Distance Matrix failure modes into editor- and user-facing
 * messages without leaking provider-specific jargon. The HTTP layer maps these
 * to a stable JSON shape (see {@see TravelCalculatorEndpoint}).
 */
final class TravelCalculatorException extends \RuntimeException
{
    public const CODE_INVALID_ORIGIN = 1;

    public const CODE_INVALID_MODE = 2;

    public const CODE_MISSING_API_KEY = 3;

    public const CODE_TRANSPORT = 4;

    public const CODE_HTTP = 5;

    public const CODE_MALFORMED = 6;

    public const CODE_API_STATUS = 7;

    public const CODE_ROUTE_STATUS = 8;

    /** @var array<string, string> */
    private array $context = [];

    public static function invalidOrigin(): self
    {
        $exception = new self(
            __('Please enter a starting address.', 'culvers'),
            self::CODE_INVALID_ORIGIN
        );

        return $exception;
    }

    public static function invalidMode(string $mode): self
    {
        $exception = new self(
            __('Pick a travel mode (driving, public transport, cycling, or walking).', 'culvers'),
            self::CODE_INVALID_MODE
        );
        $exception->context['mode'] = $mode;

        return $exception;
    }

    public static function missingApiKey(): self
    {
        return new self(
            __('Travel Calculator is not configured yet.', 'culvers'),
            self::CODE_MISSING_API_KEY
        );
    }

    public static function transport(string $message): self
    {
        $exception = new self(
            __('Couldn\'t reach the travel service. Please try again.', 'culvers'),
            self::CODE_TRANSPORT
        );
        $exception->context['detail'] = $message;

        return $exception;
    }

    public static function http(int $code): self
    {
        $exception = new self(
            __('The travel service returned an unexpected response.', 'culvers'),
            self::CODE_HTTP
        );
        $exception->context['http_code'] = (string) $code;

        return $exception;
    }

    public static function malformed(): self
    {
        return new self(
            __('Couldn\'t read the travel response.', 'culvers'),
            self::CODE_MALFORMED
        );
    }

    public static function apiStatus(string $status, string $detail = ''): self
    {
        $exception = new self(
            self::messageForApiStatus($status),
            self::CODE_API_STATUS
        );
        $exception->context['api_status'] = $status;
        if ($detail !== '') {
            $exception->context['detail'] = $detail;
        }

        return $exception;
    }

    public static function routeStatus(string $status): self
    {
        $exception = new self(
            self::messageForRouteStatus($status),
            self::CODE_ROUTE_STATUS
        );
        $exception->context['route_status'] = $status;

        return $exception;
    }

    /** @return array<string, string> */
    public function getContext(): array
    {
        return $this->context;
    }

    private static function messageForApiStatus(string $status): string
    {
        return match ($status) {
            'OVER_QUERY_LIMIT', 'OVER_DAILY_LIMIT' => __(
                'Too many travel lookups right now — please try again in a moment.',
                'culvers'
            ),
            'REQUEST_DENIED' => __('Travel lookups aren\'t available right now.', 'culvers'),
            'INVALID_REQUEST' => __('Please add a more specific starting address.', 'culvers'),
            default => __('Couldn\'t calculate the journey. Please try a different address.', 'culvers'),
        };
    }

    private static function messageForRouteStatus(string $status): string
    {
        return match ($status) {
            'NOT_FOUND' => __('We couldn\'t find that address — please be more specific.', 'culvers'),
            'ZERO_RESULTS' => __('No route found for that travel mode. Try a different mode.', 'culvers'),
            'MAX_ROUTE_LENGTH_EXCEEDED' => __('That journey is too long to calculate.', 'culvers'),
            default => __('Couldn\'t calculate the journey. Please try again.', 'culvers'),
        };
    }
}
