<?php

declare(strict_types=1);

namespace App\Directory;

/**
 * Shopping Directory demo retailers — imagery sourced from Figma Developer Release frame
 * “Culver Square - Shopping Directory Page - Filter Hidden Window (4 grid)” (node 51:5152),
 * exported MCP asset URLs (same origin pattern as {@see Nav\CulverSquareFigmaPrimaryMenu}).
 *
 * @see scripts/shops-directory-populate.php
 */
final class ShopDirectorySeedData
{
    public const FIGMA_FRAME_NOTE = 'KoBl6rTY98YnvusBgKLx4A · Shopping Directory 4-grid · node 51:5152';

    /** Default opening-hours line on cards (matches Figma tiles). */
    public const DEFAULT_HOURS_LINE = 'Open Today 9am - 5.30pm';

    // --- Logos (MCP asset URLs from `get_design_context` export) ---
    private const LOGO_FLYING_TIGER = 'https://www.figma.com/api/mcp/asset/84adaa3a-6fdf-4c5a-83e8-647481bd7afe';

    private const LOGO_ACCESSORIZE = 'https://www.figma.com/api/mcp/asset/576f8eef-f4b6-4e4a-ae92-9dd858ccd0a8';

    private const LOGO_PANDORA = 'https://www.figma.com/api/mcp/asset/ec0105c1-2c98-4319-8f76-41c6acb57ea9';

    private const LOGO_TK_MAXX = 'https://www.figma.com/api/mcp/asset/16949365-5b2f-40db-adff-8bb54dc5cbbd';

    private const LOGO_ALL_4_U_CARE = 'https://www.figma.com/api/mcp/asset/c258103b-5de6-4cf8-98f5-ffc016492bfd';

    private const LOGO_SMIGGLE = 'https://www.figma.com/api/mcp/asset/809baecb-b9e7-4576-a4f8-85287ce1a2ab';

    /** H&M tile storefront (`imgRectangle612`, node I51:5299;153:7253). */
    private const FEAT_HM_STOREFRONT = 'https://www.figma.com/api/mcp/asset/f6da80b7-da19-438f-9b34-6a173e77d307';

    private const LOGO_CLAIRES = 'https://www.figma.com/api/mcp/asset/a6da769d-3a6a-4893-aed0-9740ba0d395e';

    private const LOGO_SCHUH = 'https://www.figma.com/api/mcp/asset/e0c3744c-a74f-4880-9185-c6be1a4f4091';

    private const LOGO_GAME = 'https://www.figma.com/api/mcp/asset/1906cd8e-59a6-4794-9ec1-717db7a3622b';

    private const LOGO_HMV = 'https://www.figma.com/api/mcp/asset/db779b18-c3e4-43ac-afe0-dfe2b8517356';

    private const LOGO_BOOTS = 'https://www.figma.com/api/mcp/asset/652bcdd2-8b36-47d8-baad-4ce7724f73f5';

    private const LOGO_NEW_LOOK = 'https://www.figma.com/api/mcp/asset/178244a8-de44-4020-9a2e-30f110f50f9e';

    private const LOGO_JD = 'https://www.figma.com/api/mcp/asset/3b0b7f45-2c59-4f12-9760-398610bf278f';

    private const LOGO_SOSTRENE_GRENE = 'https://www.figma.com/api/mcp/asset/27cf02c6-11a4-4a5d-ac87-89ab6b5f7bdb';

    private const LOGO_PHONE_RETAIL = 'https://www.figma.com/api/mcp/asset/6fdb6312-2463-43ee-8beb-d41ac8d43d40';

    private const LOGO_CARD_MARKET = 'https://www.figma.com/api/mcp/asset/355570de-ac84-4204-a4ea-f84e57494831';

    private const LOGO_ECOVAPE = 'https://www.figma.com/api/mcp/asset/e8c029d4-34be-4c6b-bad0-4edd49bb7081';

    private const LOGO_LOVE_REFORM = 'https://www.figma.com/api/mcp/asset/419adfc8-05da-48c7-b19e-8f3b64f097ea';

    /** Cosmic Tattoo tile logo (`Cosmic_tattoo_logo 1`, node 51:5451, frame node 51:5152). */
    private const LOGO_COSMIC_TATTOO = 'https://www.figma.com/api/mcp/asset/fc89bc79-7fa7-40e3-8723-5b58a901e94a';

    private const LOGO_ERNEST_JONES = 'https://www.figma.com/api/mcp/asset/a74283d7-1f67-4436-83c2-9779c5751e9e';

    private const LOGO_WYE = 'https://www.figma.com/api/mcp/asset/d9b1f50e-93ec-4f3a-867f-195750e5b8f6';

    /** Hero backdrop — directory hero image from same frame (shopping hero band). */
    public const HERO_DESKTOP_IMAGE = 'https://www.figma.com/api/mcp/asset/36b1d69e-dd63-4d37-8cde-6be9a8d42cb2';

    /**
     * @return list<array{
     *     title: string,
     *     logo_url: string|null,
     *     featured_url: string|null,
     *     category_slug: string,
     *     type_slug: string
     * }>
     */
    public static function retailers(): array
    {
        $national = 'national-store';
        $indie = 'independent';

        return [
            [
                'title' => 'Flying Tiger Copenhagen',
                'logo_url' => self::LOGO_FLYING_TIGER,
                'featured_url' => self::storefrontDemoPhoto('flying-tiger'),
                'category_slug' => 'toys-gifts',
                'type_slug' => $national,
            ],
            [
                'title' => 'Accessorize London',
                'logo_url' => self::LOGO_ACCESSORIZE,
                'featured_url' => self::storefrontDemoPhoto('accessorize'),
                'category_slug' => 'fashion',
                'type_slug' => $national,
            ],
            [
                'title' => 'Pandora',
                'logo_url' => self::LOGO_PANDORA,
                'featured_url' => self::storefrontDemoPhoto('pandora'),
                'category_slug' => 'jewellery',
                'type_slug' => $national,
            ],
            [
                'title' => 'TK Maxx',
                'logo_url' => self::LOGO_TK_MAXX,
                'featured_url' => self::storefrontDemoPhoto('tk-maxx'),
                'category_slug' => 'fashion',
                'type_slug' => $national,
            ],
            [
                'title' => 'ALL 4 U CARE',
                'logo_url' => self::LOGO_ALL_4_U_CARE,
                'featured_url' => self::storefrontDemoPhoto('all4u-care'),
                'category_slug' => 'beauty-wellbeing',
                'type_slug' => $indie,
            ],
            [
                'title' => 'Smiggle',
                'logo_url' => self::LOGO_SMIGGLE,
                'featured_url' => self::storefrontDemoPhoto('smiggle'),
                'category_slug' => 'toys-gifts',
                'type_slug' => $national,
            ],
            [
                'title' => 'H&M',
                'logo_url' => null,
                'featured_url' => self::FEAT_HM_STOREFRONT,
                'category_slug' => 'fashion',
                'type_slug' => $national,
            ],
            [
                'title' => "Claire's",
                'logo_url' => self::LOGO_CLAIRES,
                'featured_url' => self::storefrontDemoPhoto('claires'),
                'category_slug' => 'toys-gifts',
                'type_slug' => $national,
            ],
            [
                'title' => 'Schuh',
                'logo_url' => self::LOGO_SCHUH,
                'featured_url' => self::storefrontDemoPhoto('schuh'),
                'category_slug' => 'fashion',
                'type_slug' => $national,
            ],
            [
                'title' => 'GAME',
                'logo_url' => self::LOGO_GAME,
                'featured_url' => self::storefrontDemoPhoto('game'),
                'category_slug' => 'technology',
                'type_slug' => $national,
            ],
            [
                'title' => 'HMV',
                'logo_url' => self::LOGO_HMV,
                'featured_url' => self::storefrontDemoPhoto('hmv'),
                'category_slug' => 'technology',
                'type_slug' => $national,
            ],
            [
                'title' => 'Boots',
                'logo_url' => self::LOGO_BOOTS,
                'featured_url' => self::storefrontDemoPhoto('boots'),
                'category_slug' => 'beauty-wellbeing',
                'type_slug' => $national,
            ],
            [
                'title' => 'New Look',
                'logo_url' => self::LOGO_NEW_LOOK,
                'featured_url' => self::storefrontDemoPhoto('new-look'),
                'category_slug' => 'fashion',
                'type_slug' => $national,
            ],
            [
                'title' => 'JD Sports',
                'logo_url' => self::LOGO_JD,
                'featured_url' => self::storefrontDemoPhoto('jd'),
                'category_slug' => 'fashion',
                'type_slug' => $national,
            ],
            [
                'title' => 'Søstrene Grene',
                'logo_url' => self::LOGO_SOSTRENE_GRENE,
                'featured_url' => self::storefrontDemoPhoto('sostrene-grene'),
                'category_slug' => 'home',
                'type_slug' => $national,
            ],
            [
                'title' => 'Three',
                'logo_url' => self::LOGO_PHONE_RETAIL,
                'featured_url' => self::storefrontDemoPhoto('three'),
                'category_slug' => 'technology',
                'type_slug' => $national,
            ],
            [
                'title' => 'Card Factory',
                'logo_url' => self::LOGO_CARD_MARKET,
                'featured_url' => self::storefrontDemoPhoto('card-factory'),
                'category_slug' => 'toys-gifts',
                'type_slug' => $national,
            ],
            [
                'title' => 'Ecovape',
                'logo_url' => self::LOGO_ECOVAPE,
                'featured_url' => self::storefrontDemoPhoto('ecovape'),
                'category_slug' => 'services',
                'type_slug' => $indie,
            ],
            [
                'title' => 'Love Reform',
                'logo_url' => self::LOGO_LOVE_REFORM,
                'featured_url' => self::storefrontDemoPhoto('love-reform'),
                'category_slug' => 'beauty-wellbeing',
                'type_slug' => $indie,
            ],
            [
                'title' => 'Cosmic Tattoo',
                'logo_url' => self::LOGO_COSMIC_TATTOO,
                'featured_url' => self::storefrontDemoPhoto('cosmic-tattoo'),
                'category_slug' => 'services',
                'type_slug' => $indie,
            ],
            [
                'title' => 'Ernest Jones',
                'logo_url' => self::LOGO_ERNEST_JONES,
                'featured_url' => self::storefrontDemoPhoto('ernest-jones'),
                'category_slug' => 'jewellery',
                'type_slug' => $national,
            ],
            [
                'title' => 'Wye Mobility',
                'logo_url' => self::LOGO_WYE,
                'featured_url' => self::storefrontDemoPhoto('wye'),
                'category_slug' => 'services',
                'type_slug' => $indie,
            ],
        ];
    }

    /**
     * Deterministic demo storefront/interior for directory hover (replace with real photography per retailer).
     */
    /**
     * Per-shop storefront photo URL.
     *
     * The Figma developer release only ships a real storefront photo for the
     * H&M anchor tile ({@see FEAT_HM_STOREFRONT}); every other shop tile in
     * the design uses the logo on a brand-coloured card. Returning `null`
     * here keeps that intent — the shop card component renders logo-only when
     * featured_url is missing — and avoids the prior random `picsum.photos`
     * landscape placeholders that obviously aren't from Figma.
     *
     * Pass-through API kept (callers reference each shop by `$seedKey`) so
     * that wiring up additional Figma storefront exports later is a one-line
     * change per shop.
     */
    private static function storefrontDemoPhoto(string $seedKey): null
    {
        unset($seedKey); // intentional — no per-shop Figma storefront yet beyond H&M

        return null;
    }
}
