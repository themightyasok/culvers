# Horizontal scroller (`horizontal_scroller`)

GSAP-driven seamless infinite-scroll strip. Use for brand-logo strips,
photo galleries, mixed-media rows, or any "many items in a horizontal
row that can scroll past the viewport edge". Drag-to-pan, optional
auto-scroll speed, per-item layout controls (size, vertical offset,
aspect ratio), and style presets for the section header.

| | |
| --- | --- |
| Layout key | `horizontal_scroller` |
| ACF schema | [`app/Components/horizontal_scroller.php`](../../app/Components/horizontal_scroller.php) |
| Blade view | [`resources/views/components/horizontal-scroller.blade.php`](../../resources/views/components/horizontal-scroller.blade.php) |
| CSS partial | [`resources/styles/components/horizontal-scroller.css`](../../resources/styles/components/horizontal-scroller.css) |
| Alpine module | [`resources/scripts/alpine/horizontal-scroller.js`](../../resources/scripts/alpine/horizontal-scroller.js) |
| Preset helper | [`app/Helpers/HorizontalScrollerPreset.php`](../../app/Helpers/HorizontalScrollerPreset.php) |
| BEM root | `.horizontal-scroller`, repeating items as `.horizontal-scroller-item` |

## When to use

- Brand / retailer logo strip (8+ logos auto-scrolling) — use **Homepage brand strip** preset on the home page.
- Mixed media row (image + text + video items).
- Press / accolade quotes that scroll past in an editorial layout.

For a strict three-up row use [`three_card_block`](THREE-CARD-BLOCK.md).

## Editor fields

Registry tabs: **Main** and **Items** only. There are **no** legacy Content / Fonts / Padding tabs — typography, colours, alignment, item gap, and intro flush come from the **Style preset** (code in `HorizontalScrollerPreset`).

Read [`app/Components/horizontal_scroller.php`](../../app/Components/horizontal_scroller.php) for the canonical field list.

### Main tab

| Field | Notes |
| --- | --- |
| `scroller_preset` | **Default** (light text on dark band) or **Homepage brand strip** (moss/olive on white, wider logo gap). |
| `scroller_header_text` | WYSIWYG main heading. |
| `scroller_subheading_text` | Optional subheading. |
| `scroller_body_text` | Optional body. |
| `scroller_button_text` / `scroller_button_link` | Optional CTA; blank label hides button. |
| `scroller_speed` | `slow` / `medium` / `fast` auto-scroll. |
| `scroller_disabled` | Static centred row — no drag, no infinite loop. |

Preset merges also set header colours, alignment, typography scale, `scroller_intro_flush`, and CSS variable `--hs-item-gap` (80px default, 133px homepage brands).

### Items tab

`scroller_items` repeater (1–50), each row:

| Sub-field | Notes |
| --- | --- |
| `item_type` | `image`, `video`, `text`, `image_text` |
| `item_size` | `small` … `xlarge` |
| `item_vertical_offset` | Stagger up/down within the strip |
| `item_aspect_ratio` | Portrait, square, landscape, tall |
| `item_kicker`, `item_heading`, `item_body` | Text slots |
| `item_image`, `item_image_alt` | Image items |
| `item_video`, `item_video_youtube_url`, `item_video_poster`, `item_video_show_controls` | Video items |

## Behaviour notes

- Alpine clones items until the loop is long enough for seamless scroll at the chosen speed.
- Pointer drag pauses auto-scroll; release resumes.
- Respects `prefers-reduced-motion: reduce` — static row.
- Static mode (`scroller_disabled`) renders a single centred row.
- GSAP Observer + ticker only (no Lenis path).

## Related components

- [`three_card_block`](THREE-CARD-BLOCK.md) — strict 3-up, no scroll.
- [`text_image_slider`](TEXT-IMAGE-SLIDER.md) — vertical headline stack + polaroid images.
- [`info_block`](INFO-BLOCK.md) — 4-up icon cells under an intro.
