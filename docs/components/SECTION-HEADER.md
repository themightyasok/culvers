# Section header (`section_header`)

Small text-only intro band: optional eyebrow line, optional heading
(with configurable level + alignment), optional short body paragraph.
Use as the opener for a content section ("Getting Here", "About
Colchester", "Accessible Guide", single-event intro, leasing pitch).

| | |
| --- | --- |
| Layout key | `section_header` |
| ACF schema | [`app/Components/section_header.php`](../../app/Components/section_header.php) |
| Blade view | [`resources/views/components/section-header.blade.php`](../../resources/views/components/section-header.blade.php) |
| CSS partial | _(none)_ |
| Alpine module | _(none)_ |
| BEM root | `.section-header` |

## When to use

Reach for `section_header` when you need a small intro band above
something else (`centre_map`, a `three_card_block`, a list of links).
For long-form content (multiple paragraphs, lists, links) use
`content_section` instead. For an intro that ends in a button + decorative
icon cells, use `info_block`.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `header_eyebrow` | text | — | Small uppercase line in faded olive above the heading. |
| `header_heading` | text | — | Canela headline. Leave blank for body-only intros. |
| `header_heading_level` | select | `h2` | Heading level (H1 allowed for top-of-page intros). |
| `header_body` | textarea (`new_lines: br`) | — | Short paragraph (1–3 lines). |
| `header_align` | select (`center`, `left`) | `center` | Figma uses centred for most page intros. |
| `header_max_width` | select (`narrow`, `medium`, `full`) | `narrow` | Constrains body width for legibility. |

## Behaviour notes

- All three content fields are optional. The component renders nothing
  (or the editor placeholder for logged-in editors) if all three are
  blank — useful as a "schedule a future intro" placeholder slot.
- Body text is split on `<br>` and rendered with explicit `<br>` tags.
- Body max-width maps to Tailwind: `max-w-3xl` / `max-w-4xl` /
  `max-w-none`.

## Related components

- [`content_section`](CONTENT-SECTION.md) — long-form WYSIWYG body.
- [`info_block`](INFO-BLOCK.md) — heading + body + CTA + 4-up icons.
- [`event_meta`](EVENT-META.md) — sits *below* a section_header on a
  single event page.
