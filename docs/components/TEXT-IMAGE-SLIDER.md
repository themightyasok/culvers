# Text-image slider (`text_image_slider`)

Vertical stack of large Canela headlines that expand in place to reveal
a body paragraph plus two polaroid-style images that pop in (left +
right) with a staggered scale / rotate animation. Inactive headlines
fade to a muted tone while one is open.

| | |
| --- | --- |
| Layout key | `text_image_slider` |
| ACF schema | [`app/Components/text_image_slider.php`](../../app/Components/text_image_slider.php) |
| Blade view | [`resources/views/components/text-image-slider.blade.php`](../../resources/views/components/text-image-slider.blade.php) |
| CSS partial | _(none — animations driven by GSAP via the Alpine module)_ |
| Alpine module | [`resources/scripts/alpine/text-image-slider.js`](../../resources/scripts/alpine/text-image-slider.js) |
| BEM root | `.text-image-slider` |
| Figma reference | `51:8074` (closed) / `51:8114` (open) |

## When to use

For an "expandable headlines" pattern with rich imagery — Guest
Services, About / Our Values, leasing pitch sections. Each row's body
needs both a paragraph and two complementary polaroid images. For
plain Q&A use [`faq`](FAQ.md) instead.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `tis_heading` | text | — | Optional section heading. |
| `tis_heading_level` | select | `h2` | |
| `tis_open_mode` | select (`single`, `multi`) | `single` | |
| `tis_initial_open_index` | number (-1..30) | -1 | 0-based. -1 = no row open. |
| `tis_items` | repeater (1–12, `block` layout) | — | |

### Item sub-fields

| Sub-field | Type | Default | Notes |
| --- | --- | --- | --- |
| `item_label` | text | — | Large Canela serif label. |
| `item_body` | wysiwyg (`toolbar: basic`, `media_upload: 0`) | — | |
| `item_image_left` | image | — | Pops in from the left when row opens. |
| `item_image_right` | image | — | Pops in from the right when row opens. |
| `item_image_left_tilt` | range (-20..20°) | -8 | |
| `item_image_right_tilt` | range (-20..20°) | 6 | |

## Behaviour notes

- Open/close animations are GSAP timelines (`gsap-manager.js`
  exposes the shared instance). Staggered scale / rotate / opacity
  for the polaroid pop-in.
- Inactive headlines fade via opacity transition while another row
  is open.
- All animations gated by `prefers-reduced-motion: reduce`.

## Related components

- [`faq`](FAQ.md) — text-only expandable rows.
- [`shop_split_highlight`](SHOP-SPLIT-HIGHLIGHT.md) — split copy +
  image band with optional cross-fade tabs.
