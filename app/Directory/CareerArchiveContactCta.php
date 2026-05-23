<?php

declare(strict_types=1);

namespace App\Directory;

use App\Helpers\Cast;
use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Careers archive — “Don’t see a job that suits you?” band below the job grid.
 *
 * Renders the shared {@see resources/views/components/info-block.blade.php}
 * intro stack (heading + body + Contact CTA). Copy defaults match Figma
 * careers frame; overrides live under Careers directory theme options.
 *
 * @see filter culvers_careers_archive_contact_cta_component
 */
final class CareerArchiveContactCta
{
    /**
     * @return array<string, mixed>|null
     */
    public static function componentOrNull(): ?array
    {
        /** @var mixed $filtered */
        $filtered = apply_filters('culvers_careers_archive_contact_cta_component', null);
        if (is_array($filtered)) {
            return self::finalize($filtered);
        }

        return self::fromOptions();
    }

    public static function appendFields(FieldsBuilder $group): void
    {
        $prefix = CareerArchiveFields::FIELD_PREFIX;

        $group->addMessage(
            __('Contact CTA (below job grid)', 'culvers'),
            __(
                'Centered intro band with a Contact button when no listed role fits. '
                . 'Leave fields blank to use the Figma defaults.',
                'culvers'
            ),
            ['new_lines' => 'wpautop']
        );

        $group->addTrueFalse("{$prefix}_contact_cta_enabled", [
            'label' => __('Show contact CTA band', 'culvers'),
            'default_value' => 1,
            'ui' => 1,
            'wrapper' => ['width' => '50'],
        ]);

        $group->addText("{$prefix}_contact_cta_heading", [
            'label' => __('Heading', 'culvers'),
            'instructions' => __('Default: “Don’t see a job that suits you?”', 'culvers'),
            'wrapper' => ['width' => '50'],
        ]);

        $group->addTextarea("{$prefix}_contact_cta_body", [
            'label' => __('Body', 'culvers'),
            'instructions' => __('Short paragraph above the button.', 'culvers'),
            'rows' => 4,
            'new_lines' => 'wpautop',
        ]);

        $group->addText("{$prefix}_contact_cta_label", [
            'label' => __('Button label', 'culvers'),
            'instructions' => __('Default: “Contact us”.', 'culvers'),
            'wrapper' => ['width' => '50'],
        ]);

        $group->addUrl("{$prefix}_contact_cta_url", [
            'label' => __('Button URL', 'culvers'),
            'instructions' => __('Default: the Contact page (/contact/).', 'culvers'),
            'wrapper' => ['width' => '50'],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function fromOptions(): ?array
    {
        if (function_exists('get_field')) {
            $enabled = get_field(CareerArchiveFields::FIELD_PREFIX . '_contact_cta_enabled', 'option');
            if ($enabled === false || $enabled === 0 || $enabled === '0') {
                return null;
            }
        }

        return self::finalize(self::defaultsSkeleton());
    }

    /**
     * @return array<string, mixed>
     */
    private static function defaultsSkeleton(): array
    {
        $prefix = CareerArchiveFields::FIELD_PREFIX;

        $heading = self::defaultHeading();
        $bodyHtml = self::defaultBodyHtml();
        $ctaLabel = self::defaultCtaLabel();
        $ctaUrl = self::defaultContactUrl();

        if (function_exists('get_field')) {
            $headingOverride = trim(Cast::toString(get_field("{$prefix}_contact_cta_heading", 'option')));
            if ($headingOverride !== '') {
                $heading = $headingOverride;
            }

            $bodyOverride = trim(Cast::toString(get_field("{$prefix}_contact_cta_body", 'option')));
            if ($bodyOverride !== '') {
                $bodyHtml = wpautop($bodyOverride);
            }

            $labelOverride = trim(Cast::toString(get_field("{$prefix}_contact_cta_label", 'option')));
            if ($labelOverride !== '') {
                $ctaLabel = $labelOverride;
            }

            $urlOverride = trim(Cast::toString(get_field("{$prefix}_contact_cta_url", 'option')));
            if ($urlOverride !== '') {
                $ctaUrl = $urlOverride;
            }
        }

        return [
            'info_heading' => $heading,
            'info_subheading' => '',
            'info_body' => $bodyHtml,
            'info_cta_label' => $ctaLabel,
            'info_cta_url' => $ctaUrl,
            'info_items' => [],
            '_grid_classes' => 'relative z-20 w-full pt-4 text-deep-moss md:pt-8',
        ];
    }

    /**
     * @param  array<string, mixed>  $component
     * @return array<string, mixed>|null
     */
    private static function finalize(array $component): ?array
    {
        $merged = array_merge(self::defaultsSkeleton(), $component);

        $heading = trim(Cast::toString($merged['info_heading'] ?? ''));
        $body = Cast::toString($merged['info_body'] ?? '');
        $ctaLabel = trim(Cast::toString($merged['info_cta_label'] ?? ''));
        $ctaUrl = trim(Cast::toString($merged['info_cta_url'] ?? ''));

        if ($heading === '' && trim(strip_tags($body)) === '' && ($ctaLabel === '' || $ctaUrl === '')) {
            return null;
        }

        if ($heading === '') {
            $merged['info_heading'] = self::defaultHeading();
        }

        if (trim(strip_tags($body)) === '') {
            $merged['info_body'] = self::defaultBodyHtml();
        }

        if ($ctaLabel === '') {
            $merged['info_cta_label'] = self::defaultCtaLabel();
        }

        if ($ctaUrl === '') {
            $merged['info_cta_url'] = self::defaultContactUrl();
        }

        return $merged;
    }

    private static function defaultHeading(): string
    {
        return __('Don’t see a job that suits you?', 'culvers');
    }

    private static function defaultBodyHtml(): string
    {
        return sprintf(
            '<p>%s</p>',
            esc_html__(
                'We’re always on the look out for enthusiastic individuals. '
                . 'Please get in touch using the contact form via the button below '
                . 'and we’ll be in touch should anything come up.',
                'culvers'
            )
        );
    }

    private static function defaultCtaLabel(): string
    {
        return __('Contact us', 'culvers');
    }

    private static function defaultContactUrl(): string
    {
        $page = get_page_by_path('contact');

        if ($page instanceof \WP_Post) {
            $permalink = get_permalink($page);

            return $permalink !== '' ? $permalink : home_url('/contact/');
        }

        return home_url('/contact/');
    }
}
