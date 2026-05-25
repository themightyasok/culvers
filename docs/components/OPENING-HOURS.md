# Opening hours (`opening_hours`)

Day-of-week list with optional intro copy and side illustrations. Today's
row is highlighted automatically (site timezone), and editors can map
custom row labels (e.g. "Easter Sunday") to a weekday for the highlight.

| | |
| --- | --- |
| Layout key | `opening_hours` |
| ACF schema | [`app/Components/opening_hours.php`](../../app/Components/opening_hours.php) |
| Blade view | [`resources/views/components/opening-hours.blade.php`](../../resources/views/components/opening-hours.blade.php) |
| CSS partial | _(none)_ |
| Alpine module | _(none — `is_today` decided server-side via site timezone)_ |
| BEM root | `.opening-hours` |

## When to use

One shared layout for **centre-wide** and **retailer-specific** hours:

| Context | `hours_context` | Where rows are saved |
| --- | --- | --- |
| Homepage, Plan My Visit, Guest Services | **Centre** | On that page's flexible stack |
| Shop / eat & drink single | **Retailer** | On **that venue's** post — each store's own week |

Directory cards read the same per-post rows for the "Open Today …" subtitle
(`OpeningHoursCardLine`). The one-line card fallback is `opening_hours_summary`
on the shop / eat & drink listing fields.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `hours_context` | select (`centre`, `retailer`) | `centre` | Presentation only — does not copy or share hour data. |
| `hours_heading` | text | — | |
| `hours_heading_level` | select | `h2` | |
| `hours_subheading` | textarea (`new_lines: br`) | — | |
| `hours_body` | wysiwyg (`toolbar: basic`, `media_upload: 0`) | — | |
| `hours_graphic_left` | image | — | Optional line art on large screens. |
| `hours_graphic_right` | image | — | |
| `hours_rows` | repeater (0–14, `table` layout) | — | **This venue's or centre's schedule** — always edited per post. |
| `hours_footnote` | textarea (`new_lines: br`) | — | Small note below the list. |

### Row sub-fields

| Sub-field | Type | Notes |
| --- | --- | --- |
| `day_label` | text (required) | Display label, e.g. "Monday" or "Easter Sunday". |
| `time_range` | text (required) | "9am – 5:30pm" or "Closed". |
| `weekday_highlight` | select (`none`, `sun`–`sat`) | Choose `none` for special rows that should never highlight. |

## Behaviour notes

- **Retailer** context: faded-olive typography, full-width band, olive row dividers (shop / eat & drink singles).
- **Centre** context: deep-moss body copy, narrow intro shell (Plan My Visit, homepage).
- "Today" is computed server-side from **this row's** `hours_rows` in the site timezone.
- Inner shell uses `LayoutShell::INNER_SECTION_7XL`.

## Related components

- [`info_block`](INFO-BLOCK.md) — same intro pattern but with 4-up
  icon cells instead of an hours list.
- [`shop_store_details`](SHOP-STORE-DETAILS.md) — contact / address band on directory singles.
