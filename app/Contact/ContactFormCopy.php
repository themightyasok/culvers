<?php

declare(strict_types=1);

namespace App\Contact;

/**
 * Contact form copy — theme defaults with optional per-block overrides from ACF.
 */
final class ContactFormCopy
{
    /**
     * @param array<string, mixed> $component
     */
    public static function firstNameLabel(array $component): string
    {
        return self::text($component, 'contact_form_first_name_label', __('First name*', 'culvers'));
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function firstNamePlaceholder(array $component): string
    {
        return self::text($component, 'contact_form_first_name_placeholder', __('Name', 'culvers'));
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function lastNameLabel(array $component): string
    {
        return self::text($component, 'contact_form_last_name_label', __('Last name*', 'culvers'));
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function lastNamePlaceholder(array $component): string
    {
        return self::text($component, 'contact_form_last_name_placeholder', __('Last name', 'culvers'));
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function emailLabel(array $component): string
    {
        return self::text($component, 'contact_form_email_label', __('Email*', 'culvers'));
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function emailPlaceholder(array $component): string
    {
        return self::text($component, 'contact_form_email_placeholder', __('Email address', 'culvers'));
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function reasonLabel(array $component): string
    {
        return self::text($component, 'contact_form_reason_label', __('Reason for enquiry*', 'culvers'));
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function reasonPlaceholder(array $component): string
    {
        return self::text($component, 'contact_form_reason_placeholder', __('Select', 'culvers'));
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function messageLabel(array $component): string
    {
        return self::text($component, 'contact_form_message_label', __('Message', 'culvers'));
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function messagePlaceholder(array $component): string
    {
        return self::text($component, 'contact_form_message_placeholder', __('Type message here', 'culvers'));
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function submitLabel(array $component): string
    {
        return self::text($component, 'contact_form_submit_label', __('Submit', 'culvers'));
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function successMessage(array $component): string
    {
        return self::text(
            $component,
            'contact_form_success_message',
            __('Thanks — your message is on its way.', 'culvers')
        );
    }

    /**
     * @return list<string>
     */
    public static function enquiryReasonChoices(): array
    {
        return [
            __('General enquiry', 'culvers'),
            __('Lost property', 'culvers'),
            __('Accessibility', 'culvers'),
            __('Feedback', 'culvers'),
            __('Press / media', 'culvers'),
            __('Leasing & commercial', 'culvers'),
        ];
    }

    /**
     * @param array<string, mixed> $component
     * @return list<string>
     */
    public static function enquiryReasonChoicesFromComponent(array $component): array
    {
        $reasons = [];
        if (isset($component['contact_form_reasons']) && is_array($component['contact_form_reasons'])) {
            foreach ($component['contact_form_reasons'] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $reasonValue = trim((string) ($row['item_reason'] ?? ''));
                if ($reasonValue !== '') {
                    $reasons[] = $reasonValue;
                }
            }
        }

        return $reasons;
    }

    /**
     * @param array<string, mixed> $component
     */
    public static function useReasonSelect(array $component): bool
    {
        return self::enquiryReasonChoicesFromComponent($component) !== [];
    }

    /**
     * @param array<string, mixed> $component
     */
    private static function text(array $component, string $key, string $default): string
    {
        $value = trim((string) ($component[$key] ?? ''));

        return $value !== '' ? $value : $default;
    }
}
