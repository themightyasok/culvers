# Three card block (`three_card_block`)

Three-up card row — manual entries (image / video card with title and
link) or dynamically pulled from selected blog post categories with
filter pills above the cards. Used on the homepage and at the bottom
of the shops archive.

| | |
| --- | --- |
| Layout key | `three_card_block` |
| ACF schema | [`app/Components/three_card_block.php`](../../app/Components/three_card_block.php) |
| Blade view | [`resources/views/components/three-card-block.blade.php`](../../resources/views/components/three-card-block.blade.php) |
| CSS partial | _(none)_ |
| Alpine module | [`resources/scripts/alpine/three-card-block.js`](../../resources/scripts/alpine/three-card-block.js) |
| BEM root | `.three-card-block` |

## When to use

When you need a three-up "feature row" of cards. For more than three
items use [`horizontal_scroller`](HORIZONTAL-SCROLLER.md). For an
icon-cell grid (4-up small tiles) use [`info_block`](INFO-BLOCK.md).

The `cards_source` switch covers two distinct use cases:

- **Manual** — pick exactly three cards (image / video, title, link).
- **Blog** — pick category tabs (one row of pills) and the cards
  populate from recent posts of the active tab.

## Editor fields

### Source selector

| Field | Type | Default |
| --- | --- | --- |
| `cards_source` | radio (`manual`, `blog`) | `manual` |

### Common fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `cards_heading` | text | — | |
| `cards_subheading` | text | — | Optional small uppercase line above the body. |
| `cards_heading_level` | select | `h2` | H1 allowed for top-of-page rows. |
| `cards_body` | wysiwyg (`toolbar: full`, `media_upload: 1`) | — | |

### Manual mode

`cards_items` repeater (0–3 rows, `block` layout):

| Sub-field | Type | Notes |
| --- | --- | --- |
| `card_title` | text (required) | |
| `card_url` | url (required) | |
| `card_media_type` | radio (`image`, `video`) | |
| `card_image` | image | Used when media type is Image. |
| `card_image_alt` | text | |
| `card_video` | file (mp4 / webm) | Used when media type is Video. Plays on hover (respects reduced motion). |
| `card_video_poster` | image | Optional poster (the live card actually shows the first frame of the video file). |

### Blog mode

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `cards_blog_categories` | taxonomy (multi-select, `category`) | — | Order = pill order. Each tab pulls latest posts. |
| `cards_blog_per_category` | number (1–12) | 3 | Up to three columns wrap. |
| `cards_view_all_url` | url | — | Defaults to the blog index. |
| `cards_view_all_label` | text | `View all` | |

## Behaviour notes

- Conditional logic in ACF hides Manual / Blog field groups based on
  the source switch.
- Hover-play of video cards is gated by
  `prefers-reduced-motion: reduce` — falls back to the first decoded
  frame.
- Reused by `App\Directory\ShopArchiveThreeCard` and **`EatDrinkArchiveThreeCard`** (each reads its own Theme Options `{archive}_three_card_*` rows; defaults CPT tabs News / Events / Offers; optional blog categories override).

## Related components

- [`horizontal_scroller`](HORIZONTAL-SCROLLER.md) — for >3 items.
- [`info_block`](INFO-BLOCK.md) — 4-up icon cells under an intro.
- [`shop_related_shops`](SHOP-RELATED-SHOPS.md) — uses the directory
  card style on shop singles.
