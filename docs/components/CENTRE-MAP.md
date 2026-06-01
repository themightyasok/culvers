# Centre map (`centre_map`)

Dark "where am I?" wayfinding band: deep-moss background with a flat
centre-floor map graphic on one side and a collapsible filter panel on
the other. The filter panel groups categories into accordion sections
(Shop / Eat & Drink / Guest Services) and lets visitors deep-link into
the directory archives. The map itself is a flat image with optional
zoom controls — there are no pins.

| | |
| --- | --- |
| Layout key | `centre_map` |
| ACF schema | [`app/Components/centre_map.php`](../../app/Components/centre_map.php) |
| Blade view | [`resources/views/components/centre-map.blade.php`](../../resources/views/components/centre-map.blade.php) |
| Alpine module | [`resources/scripts/alpine/centre-map.js`](../../resources/scripts/alpine/centre-map.js) |
| Shared partial | [`resources/views/partials/map-zoom-controls.blade.php`](../../resources/views/partials/map-zoom-controls.blade.php) (also used by contact map embed) |
| BEM root | `.centre-map` |

## When to use

The wayfinding band on the homepage and the dedicated "Centre Map"
page. For a flat list of category links to the shop directory use
`info_block` or `three_card_block` instead.

## Editor fields

### Content

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `centre_map_eyebrow` | text | — | Small uppercase line above the heading. |
| `centre_map_heading` | text | `Centre Map` | |
| `centre_map_heading_level` | select | `h2` | |
| `centre_map_body` | textarea (`new_lines: br`) | — | Optional intro paragraph. |
| `centre_map_image` | image | — | SVG / PNG / JPG / WebP. Rendered as a flat image inside a rounded clipping container; the zoom buttons CSS-scale the image. |

### Layout

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `centre_map_panel_position` | select (`left` / `right`) | `left` | Side the filter panel sits on at `lg`. |
| `centre_map_filter_button_label` | text | `Hide filter` | Pill-button label when the panel is open; the Blade swaps `Hide` → `Show` automatically when the panel collapses. |
| `centre_map_show_zoom_controls` | true/false | `true` | Hides the +/- pill stack when off. |

### Categories (filter panel)

Repeater (`centre_map_categories`) — up to 30 rows. Categories are
grouped on render by their `category_group` value, so a single flat
repeater renders as one accordion section per group.

| Sub-field | Type | Notes |
| --- | --- | --- |
| `category_group` | text | Group label (e.g. `Shop`, `Eat and drink`, `Guest Services`). Empty values fall under a single `Categories` bucket. |
| `category_label` | text (required) | Row label. |
| `category_slug` | text (required) | Lowercase, hyphenated. Used as the Alpine selection key. |
| `category_url` | url | Optional. With a URL the row is a link; without, it's a tap-to-select toggle. |

## Behaviour notes

- The filter pill (`.centre-map__filter-toggle`) toggles `panelOpen` on
  the Alpine root. When closed the grid collapses to one column and
  the map fills the band.
- Accordion groups (`.centre-map__group`) are mutually exclusive — one
  open at a time. The first group is open on first render.
- Zoom controls scale the map image between 1× and 2.5× in 0.25 steps;
  buttons disable at the bounds.
- All controls are real `<button>` / `<a>` elements with focus rings
  and `aria-expanded` / `aria-controls` wiring.

## Related components

- [`info_block`](INFO-BLOCK.md) — non-interactive 4-up category cells.
- [`three_card_block`](THREE-CARD-BLOCK.md) — three picked categories
  as cards (no map).
- [`horizontal_scroller`](HORIZONTAL-SCROLLER.md) — for "logo strip" /
  retailer carousel patterns.
