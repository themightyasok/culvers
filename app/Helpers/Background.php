<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Config\ThemeTokens;
use App\Constants\ComponentTypes;
use App\Helpers\Sanitizer;
use App\Helpers\Video;

/**
 * Resolves component background data into CSS classes / inline styles
 * (solid, gradient, image, image-centered, video, overlay).
 */
final class Background
{
    private const DEFAULT_OVERLAY_ALPHA = 0.3;

    /**
     * Return empty background stub when background is handled by parent wrapper.
     * Use when _background_handled is true to avoid re-processing.
     *
     * @return array<string, mixed>
     */
    public static function getEmptyStub(): array
    {
        return [
            'type' => 'none',
            'classes' => '',
            'styles' => '',
            'image' => null,
            'video' => null,
            'video_embed_url' => null,
            'video_poster' => null,
            'overlay_styles' => '',
            'centered_card_styles' => '',
            'centered_card_classes' => '',
            'parallax' => true,
        ];
    }

    /**
     * Solid/gradient fill for a component `<section>` when the visual background is on
     * `[data-component-background-wrapper]` (flexible layout). Without this, the section uses
     * getEmptyStub() and stays transparent; the grid can show the wrong colour vs the CMS choice,
     * especially with a wrapper overlay. Image/video backgrounds stay wrapper-only (return '').
     *
     * @param array<string, mixed> $component
     */
    public static function sectionPaintStylesWhenOnWrapper(array $component): string
    {
        $processed = self::process($component);
        $type = $processed['type'];
        if (! in_array($type, ['color', 'gradient'], true)) {
            return '';
        }

        return trim($processed['styles']);
    }

    /**
     * Process background data from component and generate CSS classes/styles.
     *
     * @param array<string, mixed> $component Component data array
     * @return array{
     *   type: string,
     *   classes: string,
     *   styles: string,
     *   image: array<string, mixed>|null,
     *   video: array<string, mixed>|null,
     *   video_embed_url: string|null,
     *   video_poster: array<string, mixed>|null,
     *   overlay_styles: string,
     *   centered_card_styles: string,
     *   centered_card_classes: string,
     *   parallax: bool,
     * }
     */
    public static function process(array $component): array
    {
        $type = $component['background_type'] ?? ComponentTypes::BACKGROUND_NONE;
        // Normalise: ACF can return label or value; accept both for Centered Image Card
        if ($type === 'Centered Image Card') {
            $type = ComponentTypes::BACKGROUND_IMAGE_CENTERED;
        }
        $result = [
            'type' => $type,
            'classes' => '',
            'styles' => '',
            'image' => null,
            'video' => null,
            'video_embed_url' => null,
            'video_poster' => null,
            'overlay_styles' => '',
            'centered_card_styles' => '',
            'centered_card_classes' => '',
            'parallax' => true,
        ];

        switch ($type) {
            case 'color':
                $color = $component['background_color'] ?? '';
                if ($color) {
                    $sanitizedColor = Sanitizer::color((string) $color);
                    if ($sanitizedColor) {
                        $themeClass = self::themeSolidBackgroundClass($sanitizedColor);
                        if ($themeClass !== null) {
                            $result['classes'] = $themeClass;
                        } else {
                            $result['styles'] = 'background-color: ' . esc_attr($sanitizedColor) . ';';
                        }
                    }
                }
                break;

            case 'gradient':
                $colorFrom = self::normalizeColorInput($component['background_gradient_color_from'] ?? '');
                $colorTo = self::normalizeColorInput($component['background_gradient_color_to'] ?? '');
                $angle = (string) ($component['background_gradient_angle'] ?? '90');
                if ($colorFrom !== '' && $colorTo !== '') {
                    $sanitizedFrom = Sanitizer::color($colorFrom);
                    $sanitizedTo = Sanitizer::color($colorTo);
                    if ($sanitizedFrom && $sanitizedTo) {
                        $cssAngle = self::gradientAngleToCss($angle);
                        $result['styles'] = sprintf(
                            'background: linear-gradient(%ddeg, %s, %s);',
                            $cssAngle,
                            esc_attr($sanitizedFrom),
                            esc_attr($sanitizedTo)
                        );
                    }
                }
                break;

            case 'image':
            case 'image_centered':
                $image = $component['background_image'] ?? null;
                if ($image && is_array($image)) {
                    // Sanitize image array
                    $sanitizedImage = Sanitizer::image($image);
                    if ($sanitizedImage && isset($sanitizedImage['url'])) {
                        $result['image'] = $sanitizedImage;
                    }
                }
                if ($type === ComponentTypes::BACKGROUND_IMAGE_CENTERED) {
                    $cardColor = (string) ($component['background_image_color'] ?? '');
                    $sanitizedCardColor = Sanitizer::color($cardColor);
                    if ($sanitizedCardColor !== '') {
                        $themeCardClass = self::themeSolidBackgroundClass($sanitizedCardColor);
                        if ($themeCardClass !== null) {
                            $result['centered_card_classes'] = $themeCardClass;
                        } else {
                            $result['centered_card_styles'] =
                                'background-color: ' . esc_attr($sanitizedCardColor) . ';';
                        }
                    }
                }
                $rawParallax = $component['background_parallax'] ?? true;
                $result['parallax'] = $rawParallax === true || $rawParallax === 1 || $rawParallax === '1';
                break;

            case 'video':
                $video = $component['background_video'] ?? null;
                $poster = $component['background_video_poster'] ?? null;
                $youtubeInput = (string) ($component['background_video_youtube_url'] ?? '');
                if ($video && is_array($video)) {
                    // Sanitize video array
                    $sanitizedVideo = Sanitizer::image($video);
                    if ($sanitizedVideo && isset($sanitizedVideo['url'])) {
                        $result['video'] = $sanitizedVideo;
                        if ($poster && is_array($poster)) {
                            $result['video_poster'] = Sanitizer::image($poster);
                        }
                    }
                }
                if ($result['video'] === null && $youtubeInput !== '') {
                    $youtubeEmbedUrl = Video::youtubeEmbedUrl($youtubeInput);
                    if ($youtubeEmbedUrl !== null) {
                        $result['video_embed_url'] = $youtubeEmbedUrl;
                    }
                }
                break;
        }

        // Handle flat overlay color. Only apply when explicitly set; treat legacy default as none.
        $overlay = (string) ($component['background_overlay'] ?? '');
        $sanitizedOverlay = Sanitizer::color($overlay);
        if ($sanitizedOverlay !== '') {
            $overlayAlpha = self::resolveOverlayAlpha($component);
            $normalizedOverlay = self::ensureOverlayAlpha($sanitizedOverlay, $overlayAlpha);
            if (! self::isLegacyDefaultOverlay($normalizedOverlay)) {
                $result['overlay_styles'] = 'background-color: ' . esc_attr($normalizedOverlay) . ';';
            }
        }

        return $result;
    }

    /**
     * Detect legacy ACF default overlay (30% black). Treat as "no overlay" so existing
     * components without an explicit choice are not darkened.
     */
    private static function isLegacyDefaultOverlay(string $color): bool
    {
        return (bool) preg_match('/^rgba\(\s*0\s*,\s*0\s*,\s*0\s*,\s*0\.30?\s*\)$/i', $color);
    }

    /**
     * Normalize color input to string (handles ACF array format).
     *
     * @param mixed $value Color from component (string or ACF rgba array)
     * @return string Color string for Sanitizer::color, or empty string
     */
    private static function normalizeColorInput(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value) && isset($value['red'], $value['green'], $value['blue'])) {
            $a = isset($value['alpha']) ? (float) $value['alpha'] : 1;

            return sprintf('rgba(%d,%d,%d,%s)', (int) $value['red'], (int) $value['green'], (int) $value['blue'], $a);
        }

        return '';
    }

    /**
     * Map UI gradient angle (0°=left→right, 90°=bottom→top) to CSS angle (0deg=to top, 90deg=to right).
     */
    private static function gradientAngleToCss(string $angle): int
    {
        $map = [
            '0' => 90,
            '45' => 45,
            '90' => 0,
            '135' => 315,
            '180' => 270,
            '225' => 225,
            '270' => 180,
            '315' => 135,
        ];
        return (int) ($map[$angle] ?? 0);
    }

    /**
     * Resolve the preferred overlay alpha from component data.
     * Accepts 0-1 values or 0-100 percentage values.
     *
     * @param array<string, mixed> $component
     */
    private static function resolveOverlayAlpha(array $component): float
    {
        $rawOpacity = $component['background_overlay_opacity'] ?? null;
        if (! is_numeric($rawOpacity)) {
            return self::DEFAULT_OVERLAY_ALPHA;
        }

        $opacity = (float) $rawOpacity;
        if ($opacity > 1) {
            $opacity = $opacity / 100;
        }

        return max(0.0, min(1.0, $opacity));
    }

    /**
     * Ensure overlay color contains an alpha channel.
     * Existing rgba values are preserved; rgb/hex values receive the fallback alpha.
     */
    private static function ensureOverlayAlpha(string $color, float $fallbackAlpha): string
    {
        $alpha = max(0.0, min(1.0, $fallbackAlpha));
        $alphaString = rtrim(rtrim(number_format($alpha, 2, '.', ''), '0'), '.');

        if (preg_match('/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*([\d\.]+)\s*\)$/i', $color)) {
            return $color;
        }

        if (preg_match('/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i', $color, $matches)) {
            return sprintf(
                'rgba(%d,%d,%d,%s)',
                (int) $matches[1],
                (int) $matches[2],
                (int) $matches[3],
                $alphaString
            );
        }

        if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i', $color, $matches)) {
            return sprintf(
                'rgba(%d,%d,%d,%s)',
                (int) $matches[1],
                (int) $matches[2],
                (int) $matches[3],
                $alphaString
            );
        }

        if (preg_match('/^#([A-Fa-f0-9]{3})$/', $color, $matches)) {
            $hex = $matches[1];
            return sprintf(
                'rgba(%d,%d,%d,%s)',
                hexdec(str_repeat($hex[0], 2)),
                hexdec(str_repeat($hex[1], 2)),
                hexdec(str_repeat($hex[2], 2)),
                $alphaString
            );
        }

        if (preg_match('/^#([A-Fa-f0-9]{6})$/', $color, $matches)) {
            $hex = $matches[1];
            return sprintf(
                'rgba(%d,%d,%d,%s)',
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2)),
                $alphaString
            );
        }

        if (strtolower($color) === 'transparent') {
            return 'rgba(0,0,0,0)';
        }

        return $color;
    }

    /**
     * When a sanitised colour matches `@theme` `--color-*`, use `bg-{slug}` instead of inline CSS.
     */
    private static function themeSolidBackgroundClass(string $sanitizedColor): ?string
    {
        if (! str_starts_with($sanitizedColor, '#')) {
            return null;
        }

        $norm = ThemeTokens::normalizeColorHex($sanitizedColor);
        if ($norm === '') {
            return null;
        }

        $slug = ThemeTokens::slugForNormalizedHex($norm);

        return $slug !== null ? 'bg-' . $slug : null;
    }
}
