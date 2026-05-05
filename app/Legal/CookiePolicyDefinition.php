<?php

declare(strict_types=1);

namespace App\Legal;

final class CookiePolicyDefinition
{
    /**
     * @return array{hero_title: string, hero_subtitle: string, sections: list<array{aside: string, body: string}>}
     */
    public static function data(): array
    {
        $whatBody = <<<'HTML'
<p>Cookies are small text files that are stored by the browser on your computer or mobile phone. Websites are able to read and write these files, allowing them to store things like personalisation details or user preferences. You can think of cookies as providing a “memory” for the website, enabling it to recognise a user and respond appropriately.</p>
<p>You may wish to visit <a href="https://www.aboutcookies.org" rel="noopener noreferrer" target="_blank">www.aboutcookies.org</a> which contains comprehensive information on how to do this on a wide variety of browsers. You will also find details on how to delete cookies from your computer as well as more general information about cookies. For information on how to do this on the browser of your mobile phone you will need to refer to your handset manual.</p>
HTML;

        $messageBody = <<<'HTML'
<p>You may see a banner when you visit <a href="https://www.culversquare.co.uk">www.culversquare.co.uk</a> inviting you to accept cookies or to set your cookie preferences. We’ll set cookies so that your computer knows you’ve seen it and not to show it again, and also to store your settings. These settings will expire after 1 year when you will be invited to review your preferences.</p>
HTML;

        return [
            'hero_title' => __('Cookie Policy.', 'culvers'),
            'hero_subtitle' => __('Not the chocolate-chip kind (sadly)', 'culvers'),
            'sections' => [
                [
                    'aside' => '<p>' . esc_html__('What are Cookies?', 'culvers') . '</p>',
                    'body' => $whatBody,
                ],
                [
                    'aside' => '<p>' . esc_html__('Cookies message', 'culvers') . '</p>',
                    'body' => $messageBody,
                ],
            ],
        ];
    }
}
