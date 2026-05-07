<?php

declare(strict_types=1);

namespace App\Contact;

use App\Customizer\FooterCustomizer;

/**
 * REST endpoint backing the front-end Contact component form.
 *
 * `POST /wp-json/culvers/v1/contact-form`
 *   Body: { first_name, last_name, email, reason, message, website }
 *   Headers: X-WP-Nonce (wp_rest)
 *
 * Anti-abuse stack: nonce + honeypot field (`website`) + per-IP rate limit
 * (5 submissions / hour). Messages are dispatched via `wp_mail` to
 * {@see FooterCustomizer::contactFormRecipient()} (falls back to `admin_email`).
 */
final class ContactFormEndpoint
{
    public const NAMESPACE = 'culvers/v1';

    public const ROUTE = '/contact-form';

    private const RATE_LIMIT_REQUESTS = 5;

    private const RATE_LIMIT_WINDOW_SECONDS = 3600;

    private const RATE_LIMIT_PREFIX = 'culvers_cf_rl_';

    private const MAX_FIRST_NAME_LENGTH = 100;

    private const MAX_LAST_NAME_LENGTH = 100;

    private const MAX_EMAIL_LENGTH = 200;

    private const MAX_REASON_LENGTH = 100;

    private const MAX_MESSAGE_LENGTH = 5000;

    public static function register(): void
    {
        register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods' => 'POST',
            'callback' => [self::class, 'handle'],
            'permission_callback' => [self::class, 'permission'],
        ]);
    }

    public static function permission(\WP_REST_Request $request): bool|\WP_Error
    {
        $nonce = $request->get_header('x_wp_nonce');
        if (! is_string($nonce) || $nonce === '' || ! wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error(
                'culvers_contact_invalid_nonce',
                __('Session expired — please refresh the page.', 'culvers'),
                ['status' => 403]
            );
        }

        if (self::isRateLimited()) {
            return new \WP_Error(
                'culvers_contact_rate_limited',
                __('Too many submissions — please wait a few minutes and try again.', 'culvers'),
                ['status' => 429]
            );
        }

        return true;
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        try {
            $payload = self::validate($request);
            $recipient = FooterCustomizer::contactFormRecipient();
            if ($recipient === '') {
                throw ContactFormException::noRecipient();
            }
            self::send($recipient, $payload);
        } catch (ContactFormException $e) {
            $status = match ($e->getCode()) {
                ContactFormException::CODE_VALIDATION => 400,
                ContactFormException::CODE_HONEYPOT => 422,
                ContactFormException::CODE_RATE_LIMITED => 429,
                ContactFormException::CODE_NO_RECIPIENT => 503,
                ContactFormException::CODE_MAIL_FAILED => 502,
                default => 500,
            };

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[culvers][contact-form] ' . $e->getMessage() . ' ' . wp_json_encode($e->getContext()));
            }

            return new \WP_Error('culvers_contact_failed', $e->getMessage(), ['status' => $status]);
        }

        return rest_ensure_response([
            'message' => __('Thanks — your message is on its way.', 'culvers'),
        ]);
    }

    /**
     * @return array{first_name: string, last_name: string, email: string, reason: string, message: string}
     * @throws ContactFormException
     */
    private static function validate(\WP_REST_Request $request): array
    {
        $honeypot = sanitize_text_field((string) $request->get_param('website'));
        if ($honeypot !== '') {
            throw ContactFormException::honeypot();
        }

        $firstName = sanitize_text_field((string) $request->get_param('first_name'));
        $lastName = sanitize_text_field((string) $request->get_param('last_name'));
        $email = sanitize_email((string) $request->get_param('email'));
        $reason = sanitize_text_field((string) $request->get_param('reason'));
        $message = sanitize_textarea_field((string) $request->get_param('message'));

        if ($firstName === '') {
            throw ContactFormException::validation(__('Please tell us your first name.', 'culvers'));
        }
        if ($lastName === '') {
            throw ContactFormException::validation(__('Please tell us your last name.', 'culvers'));
        }
        if ($email === '' || ! is_email($email)) {
            throw ContactFormException::validation(__('Please enter a valid email address.', 'culvers'));
        }
        if ($message === '') {
            throw ContactFormException::validation(__('Please write a message.', 'culvers'));
        }

        if (
            mb_strlen($firstName) > self::MAX_FIRST_NAME_LENGTH
            || mb_strlen($lastName) > self::MAX_LAST_NAME_LENGTH
            || mb_strlen($email) > self::MAX_EMAIL_LENGTH
            || mb_strlen($reason) > self::MAX_REASON_LENGTH
            || mb_strlen($message) > self::MAX_MESSAGE_LENGTH
        ) {
            throw ContactFormException::validation(__('One of your fields is too long — please shorten it.', 'culvers'));
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'reason' => $reason,
            'message' => $message,
        ];
    }

    /**
     * @param array{first_name: string, last_name: string, email: string, reason: string, message: string} $payload
     * @throws ContactFormException
     */
    private static function send(string $recipient, array $payload): void
    {
        $reasonLine = $payload['reason'] !== ''
            ? sprintf(__('Reason: %s', 'culvers'), $payload['reason'])
            : '';

        $subject = sprintf(
            /* translators: 1: site name, 2: reason or 'enquiry', 3: submitter full name */
            __('[%1$s] %2$s from %3$s', 'culvers'),
            wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES),
            $payload['reason'] !== '' ? $payload['reason'] : __('New enquiry', 'culvers'),
            trim($payload['first_name'] . ' ' . $payload['last_name'])
        );

        $bodyLines = [
            sprintf(__('From: %s %s <%s>', 'culvers'), $payload['first_name'], $payload['last_name'], $payload['email']),
            $reasonLine,
            '',
            __('Message:', 'culvers'),
            $payload['message'],
            '',
            '— ' . sprintf(__('Sent via %s', 'culvers'), home_url('/')),
        ];

        $body = implode("\n", array_filter($bodyLines, static fn (string $line): bool => $line !== ''));

        $sent = wp_mail(
            $recipient,
            wp_specialchars_decode($subject, ENT_QUOTES),
            $body,
            [
                'Reply-To: ' . $payload['first_name'] . ' ' . $payload['last_name'] . ' <' . $payload['email'] . '>',
            ]
        );

        if (! $sent) {
            throw ContactFormException::mailFailed();
        }
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
