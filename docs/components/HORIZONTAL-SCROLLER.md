# Horizontal scroller (`horizontal_scroller`)

GSAP-driven seamless infinite-scroll strip. Use for brand-logo strips,
photo galleries, mixed-media rows, or any "many items in a horizontal
row that can scroll past the viewport edge". Drag-to-pan, optional
auto-scroll speed, per-item layout controls (size, vertical offset,
aspect ratio), and rich typography options for the section header.

| | |
| --- | --- |
| Layout key | `horizontal_scroller` |
| ACF schema | [`app/Components/horizontal_scroller.php`](../../app/Components/horizontal_scroller.php) |
| Blade view | [`resources/views/components/horizontal-scroller.blade.php`](../../resources/views/components/horizontal-scroller.blade.php) |
| CSS partial | [`resources/styles/components/horizontal-scroller.css`](../../resources/styles/components/horizontal-scroller.css) |
| Alpine module | [`resources/scripts/alpine/horizontal-scroller.js`](../../resources/scripts/alpine/horizontal-scroller.js) |
| BEM root | `.horizontal-scroller`, repeating items as `.horizontal-scroller-item` |

## When to use

This is the most flexible "row of stuff" component. Reach for it when:

- Brand / retailer logo strip (8+ logos auto-scrolling).
- Mixed media row (image + text + video items).
- Press / accolade quotes that scroll past in an editorial layout.

For a strict three-up row use [`three_card_block`](THREE-CARD-BLOCK.md).

## Editor fields (high level)

The schema is large; read
[`app/Components/horizontal_scroller.php`](../../app/Components/horizontal_scroller.php)
for the canonical definition. Headline groups:

### Content tab
- `scroller_header_text` (wysiwyg), `scroller_subheading_text`,
  `scroller_body_text`.
- `scroller_header_alignment`, `scroller_header_text_alignment`.
- `scroller_intro_flush` — collapse the gap between the header and
  the strip when the header functions as the strip's title.
- `scroller_button_text`, `scroller_button_link`,
  `scroller_button_variant`, `scroller_button_size`,
  `scroller_button_show_arrow`.
- `scroller_speed` (slow / medium / fast),
  `scroller_disabled` (toggle off auto-scroll for a static centered
  row), `scroller_item_spacing` (px between items).
- `scroller_items` repeater — one row per item with:
  - `item_type` (`image`, `video`, `text`, `image_text`).
  - `item_size`, `item_vertical_offset`, `item_aspect_ratio`.
  - `item_kicker`, `item_heading`, `item_body`.
  - `item_image` / `item_image_alt`.
  - `item_video` / `item_video_youtube_url` /
    `item_video_poster` / `item_video_show_controls`.

### Fonts tab
- Per-element colour / size / weight selects driven by
  `App\Helpers\Typography::getHeaderSizeChoices()` /
  `getBodySizeChoices()` / `getWeightChoices()`. Header, subheading,
  body, kicker, item heading, item body — each gets its own trio.

### Padding tab
- `scroller_remove_vertical_padding` — flush the outer + strip
  vertical padding for an edge-to-edge band.
- Per-element padding-above / padding-below selects driven by
  `App\Helpers\Padding::getHeaderSubheaderPaddingChoices()` for
  fine-tuned design tweaks.

## Behaviour notes

- The Alpine module clones items into the strip until the loop is
  long enough to scroll seamlessly at the chosen speed.
- Pointer drag pauses auto-scroll while held; auto-scroll resumes on
  pointer release.
- Auto-scroll respects `prefers-reduced-motion: reduce` — falls back
  to a static row.
- "Tight header to cards" toggles a flush-bottom modifier on the
  intro block (`.horizontal-scroller--intro-flush`) so headers like
  "Over X artists…" sit tight against the strip.
- Static mode (`scroller_disabled = true`) renders a single centered
  row with no drag and no infinite duplication.

## Related components

- [`three_card_block`](THREE-CARD-BLOCK.md) — strict 3-up, no scroll.
- [`text_image_slider`](TEXT-IMAGE-SLIDER.md) — vertical headline
  stack that pops in side images.
- [`info_block`](INFO-BLOCK.md) — 4-up icon cells under an intro.
