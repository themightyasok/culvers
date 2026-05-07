<?php

declare(strict_types=1);

namespace App\Contact;

/**
 * Translates contact-form failure modes into editor- and user-facing
 * messages without leaking internals. The HTTP layer maps these to a
 * stable JSON shape (see {@see ContactFormEndpoint}).
 */
final class ContactFormException extends \RuntimeException
{
    public const CODE_VALIDATION = 1;

    public const CODE_HONEYPOT = 2;

    public const CODE_RATE_LIMITED = 3;

    public const CODE_NO_RECIPIENT = 4;

    public const CODE_MAIL_FAILED = 5;

    /** @var array<string, string> */
    private array $context = [];

    public static function validation(string $message): self
    {
        return new self($message, self::CODE_VALIDATION);
    }

    public static function honeypot(): self
    {
        return new self(__('Submission rejected.', 'culvers'), self::CODE_HONEYPOT);
    }

    public static function rateLimited(): self
    {
        return new self(
            __('Too many submissions — please wait a few minutes and try again.', 'culvers'),
            self::CODE_RATE_LIMITED
        );
    }

    public static function noRecipient(): self
    {
        return new self(
            __('The contact form is not configured yet.', 'culvers'),
            self::CODE_NO_RECIPIENT
        );
    }

    public static function mailFailed(): self
    {
        return new self(
            __('We couldn\'t send your message. Please try again or email us directly.', 'culvers'),
            self::CODE_MAIL_FAILED
        );
    }

    public function withContext(string $key, string $value): self
    {
        $this->context[$key] = $value;

        return $this;
    }

    /** @return array<string, string> */
    public function getContext(): array
    {
        return $this->context;
    }
}
