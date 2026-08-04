# Shop — split highlight (`shop_split_highlight`)

Split content + image band — left olive copy column with kicker /
headline / body / CTA, right column with a lifestyle image. Choose a
60/40 or 50/50 ratio. Optionally swap the static copy for a tabbed
deck whose panels cross-fade inside the olive column.

| | |
| --- | --- |
| Layout key | `shop_split_highlight` |
| ACF schema | [`app/Components/shop_split_highlight.php`](../../app/Components/shop_split_highlight.php) |
| Blade view | [`resources/views/components/shop-split-highlight.blade.php`](../../resources/views/components/shop-split-highlight.blade.php) |
| CSS partial | _(none)_ |
| Alpine module | [`resources/scripts/alpine/shop-split-highlight.js`](../../resources/scripts/alpine/shop-split-highlight.js) — drives tab cross-fade |
| BEM root | `.shop-split-highlight` |

## When to use

Despite the `shop_` prefix this is reusable on any page — used on
shop singles, About, Plan My Visit, leasing pages. For a true 50/50
content / image with no tabs and no olive panel, prefer designing
with the General-tab `background_type` colour fields on a smaller
component.

## Editor fields

### Layout

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `split_ratio` | select (`60-40`, `50-50`) | `60-40` | Width split between copy and image. |
| `split_use_tabs` | true_false | off | Switches the copy column to a tabbed deck. |

### Static copy mode (`split_use_tabs = false`)

Copy is always centre-aligned horizontally and vertically in the theme. Use **`split_copy_background`** to switch olive/white type treatment.

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `split_copy_background` | select (`olive`, `white`) | `olive` | Olive + white text, or white + deep moss text. |
| `split_kicker` | text | | Glowleaf Canela first line (dates / emphasis belong here, not in body). |
| `split_headline` | text | | Second Glowleaf Canela line (dates / emphasis OK here too). |
| `split_body` | wysiwyg (`toolbar: basic`, `media_upload: 0`) | Halyard (body) copy. `<highlight>` = Glowleaf colour; `<extended>` = Glowleaf + Canela; `<validity>` = offer-date Action Label. Do not wrap dates in `<extended>`. |
| `split_cta_label` | text | Leave blank to hide. |
| `split_cta_url` | url | |

### Tab mode (`split_use_tabs = true`)

`split_tabs` repeater (0–8 rows, `block` layout):

| Sub-field | Type | Notes |
| --- | --- | --- |
| `tab_label` | text | Pill label (uppercase). |
| `tab_headline` | text | Glowleaf Canela headline shown in the panel. |
| `tab_kicker` | text | Optional kicker above the headline. |
| `tab_body` | wysiwyg (`toolbar: basic`, `media_upload: 0`) | Supports `<highlight>` / `<extended>` / `<validity>` accent tags. |
| `tab_cta_label` | text | |
| `tab_cta_url` | url | |

### Image

| Field | Type | Notes |
| --- | --- | --- |
| `split_image` | image | Lifestyle crop; fills the right image column. |

## Behaviour notes

- Conditional logic in ACF hides the static fields when tabs are on
  (and vice versa) so editors don't see noise.
- Tabs use ARIA `tablist` / `tab` / `tabpanel`. Arrow keys navigate
  pills; Home / End jump to first / last; Esc returns focus to the
  active pill.
- Cross-fade between tab panels is GSAP-driven (`gsap-manager.js`
  shared timeline). Falls back to opacity transition under
  `prefers-reduced-motion: reduce`.

## Related components

- [`text_image_slider`](TEXT-IMAGE-SLIDER.md) — vertical headline
  stack that pops in side images.
- [`info_block`](INFO-BLOCK.md) — intro + 4-up icon cells.
- [`shop_intro_block`](SHOP-INTRO-BLOCK.md) — single centred intro
  panel.
