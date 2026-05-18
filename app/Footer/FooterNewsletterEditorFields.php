<?php

declare(strict_types=1);

namespace App\Footer;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Sidebar field on pages + directory singles — optional override for the footer
 * newsletter band background. When empty, {@see FooterNewsletterImage} uses the
 * directory archive option (Events / News / Shops etc.) then the Customizer default.
 */
final class FooterNewsletterEditorFields
{
    public static function register(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        $group = new FieldsBuilder('group_culvers_footer_newsletter_context', [
            'title' => __('Footer newsletter', 'culvers'),
        ]);

        $group->setGroupConfig('position', 'side');

        $group->addImage(FooterNewsletterImage::SINGULAR_FIELD, [
            'label' => __('Newsletter background', 'culvers'),
            'instructions' => __(
                'Large photo behind the rounded newsletter panel. Leave empty to use the default '
                . 'for this content type (Appearance → the matching directory screen, e.g. Shop directory / Latest News) '
                . 'and finally the site-wide image under Appearance → Customize → Culver Square footer.',
                'culvers'
            ),
            'return_format' => 'id',
            'preview_size' => 'large',
        ]);

        $group->setLocation('post_type', '==', 'page')
            ->or('post_type', '==', 'culvers_shop')
            ->or('post_type', '==', 'culvers_eat_drink')
            ->or('post_type', '==', 'culvers_event')
            ->or('post_type', '==', 'culvers_offer')
            ->or('post_type', '==', 'culvers_news')
            ->or('post_type', '==', 'culvers_career');

        acf_add_local_field_group($group->build());
    }
}
