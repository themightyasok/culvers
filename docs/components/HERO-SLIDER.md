# Hero slider (`hero_slider`)

Full-viewport Splide carousel: looping slides with imagery, a headline
stack, and a single CTA. Used as the homepage hero and at the top of
the shops directory archive.

| | |
| --- | --- |
| Layout key | `hero_slider` |
| ACF schema | [`app/Components/hero_slider.php`](../../app/Components/hero_slider.php) |
| Blade view | [`resources/views/components/hero-slider.blade.php`](../../resources/views/components/hero-slider.blade.php) |
| CSS partial | _(uses Splide CSS imported in `resources/styles/app.css`)_ |
| Alpine module | [`resources/scripts/alpine/hero-slider.js`](../../resources/scripts/alpine/hero-slider.js) |
| BEM root | `.hero-slider` |
| Always full-width | Yes — registered in `culvers_default_full_width_components` filter. |

## When to use

Looping multi-slide hero — homepage and category-landing patterns. For
a static single image at the top of an interior page use `image_hero`.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `hero_instructions` | message | — | Editor-only note (use as the first block on the page; the fixed header overlaps the imagery). |
| `hero_slides` | repeater (1–12, `block` layout) | — | At least one slide. |
| `hero_content_align` | select (`left`, `center`, `right`) | `center` | Horizontal alignment of the copy block (vertical centring is fixed). |

### Slide sub-fields

| Sub-field | Type | Notes |
| --- | --- | --- |
| `slide_image` | image (required) | Desktop image. |
| `slide_image_mobile` | image | Optional tighter crop. Falls back to desktop image. |
| `slide_headline` | textarea (`new_lines: br`) | Large Canela headline (`font-heading`). |
| `slide_kicker` | text | Short uppercase line under the headline. |
| `slide_body` | textarea (`new_lines: br`) | Body copy under the headline. |
| `slide_cta_label` | text | Hide button by leaving blank. |
| `slide_cta_url` | url | Required if a label is set. |

## Behaviour notes

- Splide drives the carousel; imported via `splide-init.js` in
  `resources/scripts/utils/`.
- Same field schema is reused by the shops archive options page
  (`ShopArchiveFields`) so the same Blade component renders
  `/shops/`'s hero — pass the slides array via the
  `culvers_shops_archive_hero_component` filter to override.
- Tucks under the fixed header (negative top offset) so the imagery
  paints behind the nav from the very top.

## Related components

- [`image_hero`](IMAGE-HERO.md) — the static counterpart.
- [`shop_split_highlight`](SHOP-SPLIT-HIGHLIGHT.md) — for in-page
  split-band promos.
