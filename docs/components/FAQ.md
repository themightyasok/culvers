# FAQ (`faq`)

Centred Canela serif heading + glowleaf keyline + accordion of
question / answer disclosure rows. Optional decorative line-art SVGs /
PNGs flank the column on desktop.

| | |
| --- | --- |
| Layout key | `faq` |
| ACF schema | [`app/Components/faq.php`](../../app/Components/faq.php) |
| Blade view | [`resources/views/components/faq.blade.php`](../../resources/views/components/faq.blade.php) |
| CSS partial | _(none)_ |
| Alpine module | [`resources/scripts/alpine/faq.js`](../../resources/scripts/alpine/faq.js) |
| BEM root | `.faq` |
| Figma reference | `51:7998` |

## When to use

For a real Q&A list — Guest Services FAQs, ticketing FAQs, leasing
FAQs. For a longer-form policy doc that just *includes* questions as
H3s, use [`content_section`](CONTENT-SECTION.md) instead.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `faq_heading` | text | `Frequently Asked Questions` | Centred Canela serif heading. |
| `faq_heading_level` | select | `h2` | |
| `faq_show_keyline` | true_false | on | Glowleaf keyline under the heading. |
| `faq_open_mode` | select (`single`, `multi`) | `single` | Single — opening one row closes the others. Multi — toggles independently. |
| `faq_items` | repeater (1–30, `block` layout) | — | |
| `faq_decorations_left` | repeater (0–4) | — | Optional line-art images that float to the left on large screens. |
| `faq_decorations_right` | repeater (0–4) | — | |

### Item sub-fields

| Sub-field | Type | Notes |
| --- | --- | --- |
| `item_question` | text | |
| `item_answer` | wysiwyg (`toolbar: basic`, `media_upload: 0`) | |
| `item_open_default` | true_false | Pre-expand on first render. In single mode only the first pre-expanded row wins. |

## Behaviour notes

- Disclosure semantics — each row is a real `<button>` controlling a
  `<div role="region">` answer panel via `aria-expanded` / `aria-controls`.
- Keyboard friendly: Enter / Space toggles; Esc closes the active row
  in single mode.
- Decorative images are decorative (`alt=""`) — the FAQ heading is
  the section's accessible name.

## Related components

- [`content_section`](CONTENT-SECTION.md) — long-form alternative.
- [`text_image_slider`](TEXT-IMAGE-SLIDER.md) — same "rows expand to
  reveal more" idea, but with side images.
