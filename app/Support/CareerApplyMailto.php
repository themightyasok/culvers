<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Builds a mailto: apply link for career singles from the job email + title.
 */
final class CareerApplyMailto
{
    /**
     * @param  list<array{label: string, value: string}>  $metaRows
     */
    public static function build(string $email, string $jobTitle, string $pageUrl, array $metaRows = []): string
    {
        $email = sanitize_email($email);
        if ($email === '') {
            return '';
        }

        $title = $jobTitle !== '' ? $jobTitle : __('Role at Culver Square', 'culvers');
        $subject = sprintf(
            /* translators: %s job title */
            __('Application: %s', 'culvers'),
            $title
        );

        $lines = [
            sprintf(__('Hi,', 'culvers')),
            '',
            sprintf(
                /* translators: %s job title */
                __('I would like to apply for the %s position advertised at Culver Square.', 'culvers'),
                $title
            ),
            '',
        ];

        foreach ($metaRows as $row) {
            $label = trim($row['label']);
            $value = trim($row['value']);
            if ($label === '' || $value === '') {
                continue;
            }
            $lines[] = $label . ': ' . $value;
        }

        if ($pageUrl !== '') {
            $lines[] = '';
            $lines[] = __('Job listing:', 'culvers') . ' ' . $pageUrl;
        }

        $lines[] = '';
        $lines[] = __('Kind regards,', 'culvers');
        $lines[] = '[Your name]';

        $body = implode("\n", $lines);

        return 'mailto:' . $email
            . '?subject=' . rawurlencode($subject)
            . '&body=' . rawurlencode($body);
    }
}
