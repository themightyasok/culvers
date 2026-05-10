# Image hero (`image_hero`)

Static full-bleed page header. Wide lifestyle / storefront image, an
optional center logo lockup or large title + spaced subtitle, and an
adjustable image overlay. The default static counterpart to
`hero_slider`.

| | |
| --- | --- |
| Layout key | `image_hero` |
| ACF schema | [`app/Components/image_hero.php`](../../app/Components/image_hero.php) |
| Blade view | [`resources/views/components/image-hero.blade.php`](../../resources/views/components/image-hero.blade.php) |
| CSS partial | rules in `resources/styles/app.css` (`.image-hero--viewport`) |
| Alpine module | _(none)_ |
| BEM root | `.image-hero` |
| Figma reference | `51:9360` |
| Always full-width | Yes — registered in `culvers_full_width_components` filter (see `setup.php`). |

## When to use

Anywhere you want a single static photograph at the top of a page —
Contact, About, leasing, brand-lockup pages. For a looping multi-slide
hero use `hero_slider`. The two cover the entire "header hero" surface
between them; do not invent a third.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `hero_image` | image | — | Wide lifestyle / storefront shot. Figma band is 1440×646. |
| `hero_image_mobile` | image | — | Optional tighter crop for small screens. |
| `hero_logo` | image | — | Optional centre lockup over the hero (white artwork preferred). When set, the title/subtitle are hidden. |
| `hero_title_line` | text | — | Large Canela headline when no logo is set (96 px on desktop). |
| `hero_title_tone` | select (`glowleaf`, `white`, `lighter-cream`) | `glowleaf` | Tone for the title — keep glowleaf unless contrast on a busy photo demands white. |
| `hero_subtitle_line` | textarea (`new_lines: br`) | — | Spaced uppercase Commuter Sans line under the title (20 px / SemiBold / 4 px tracking). |
| `hero_overlay_opacity` | number (0–85, %) | 20 | Solid black overlay on the image. Push higher only when text contrast on a busy photo demands it. |
| `hero_title_in_image` | true/false | `false` | Set to true **only** when the supplied artwork already contains the page title baked into the image (rare — usually a brand handover). The title and subtitle render `sr-only` so screen readers still announce them, but no visible text is drawn over the image. Always prefer clean photography + the editable text fields. |

## Behaviour notes

- Always renders edge-to-edge — registered in
  `culvers_default_full_width_components` and
  `culvers_full_width_components` filters in
  [`app/setup.php`](../../app/setup.php) so `Component::rootClasses()`
  is called with `includePadding: false` and the section paints
  flush against the fixed header.
- The mobile image is preferred under the `(max-width: 767px)` media
  query via a `<picture>` source.
- When `hero_logo` is set the title / subtitle render slots are
  intentionally suppressed — use one or the other, never both
  (matches Figma).
- When `hero_image` is empty the band paints a deep-moss brand
  background with the logo / title centred on it instead of
  collapsing to nothing. Useful for shop-detail pages whose
  storefront photography hasn't shipped yet.

## Related components

- [`hero_slider`](HERO-SLIDER.md) — animated multi-slide hero.
- [`section_header`](SECTION-HEADER.md) — small intro band that often
  sits below an `image_hero` on the same page.
