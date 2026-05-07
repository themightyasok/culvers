# Info block (`info_block`)

Editorial intro (heading + subheading + body + CTA) followed by a grid
of square "icon cells" — one row of four on large screens, one column
on mobile. Sits on the page white — there is no coloured band or
decorative motif behind the band.

| | |
| --- | --- |
| Layout key | `info_block` |
| ACF schema | [`app/Components/info_block.php`](../../app/Components/info_block.php) |
| Blade view | [`resources/views/components/info-block.blade.php`](../../resources/views/components/info-block.blade.php) |
| CSS partial | _(none — Tailwind utilities only)_ |
| Alpine module | _(none)_ |
| BEM root | `.info-block` |

## When to use

When you need an intro paragraph **and** a row of bite-sized
"key facts" tiles (icon + title + description). For just the intro
band use [`section_header`](SECTION-HEADER.md) or
[`content_section`](CONTENT-SECTION.md). For three feature cards
without the intro paragraph use [`three_card_block`](THREE-CARD-BLOCK.md).

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `info_heading` | text | — | |
| `info_heading_level` | select | `h2` | |
| `info_subheading` | textarea (`new_lines: br`) | — | Above the body. |
| `info_body` | wysiwyg (`toolbar: basic`, `media_upload: 0`) | — | |
| `info_cta_label` | text | — | Leave blank to hide the button. |
| `info_cta_url` | url | — | Required if a label is set. |
| `info_items` | repeater (0–16, `block` layout) | — | Square tiles in a 4-col grid on large screens. |

### Item sub-fields

| Sub-field | Type | Notes |
| --- | --- | --- |
| `item_image` | image | Line art or illustration; centred in the cell. |
| `item_heading` | text (required) | |
| `item_description` | textarea (`new_lines: br`) | Renders in uppercase styling. |

## Behaviour notes

- Default `body_text_tone` is `'light-band'` (set in
  `ComponentRegistry::addGeneralTab()`) — overrides editor picks of
  white/zinc/brand to a legible default on the white band.
- The band paints flush white (`bg-white`); cells are also `bg-white`
  with a 1 px hairline grid (`gap-px bg-deep-moss/15`) acting as the
  divider between tiles.

## Related components

- [`section_header`](SECTION-HEADER.md) — intro band without the
  4-up tiles.
- [`three_card_block`](THREE-CARD-BLOCK.md) — three feature cards
  with optional intro paragraph.
- [`opening_hours`](OPENING-HOURS.md) — same "intro + side
  illustrations + content list" pattern, but specialised for hours.
