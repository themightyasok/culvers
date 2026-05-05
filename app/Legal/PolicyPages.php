<?php

declare(strict_types=1);

namespace App\Legal;

/**
 * Routes WP pages (by slug) to structured policy layouts — copy sourced from Culver Square developer Figma.
 */
final class PolicyPages
{
    /**
     * @return array{hero_title: string, hero_subtitle: string, sections: list<array{aside: string, body: string}>}|null
     */
    public static function layoutDataForPost(?\WP_Post $post): ?array
    {
        if (! $post instanceof \WP_Post || $post->post_type !== 'page') {
            return null;
        }

        $slug = (string) $post->post_name;

        return match ($slug) {
            'privacy-policy' => PrivacyPolicyDefinition::data(),
            'cookie-policy' => CookiePolicyDefinition::data(),
            'terms-and-conditions' => TermsPolicyDefinition::data(),
            default => null,
        };
    }
}
