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

The canonical "centre opening hours" panel. Reuse on the homepage,
Plan My Visit, and Guest Services. For shop-specific opening hours
use the shop listing field (`opening_hours_summary` line on each
shop) rendered in the directory cards / shop singles.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `hours_heading` | text | — | |
| `hours_heading_level` | select | `h2` | |
| `hours_subheading` | textarea (`new_lines: br`) | — | |
| `hours_body` | wysiwyg (`toolbar: basic`, `media_upload: 0`) | — | |
| `hours_graphic_left` | image | — | Optional line art on large screens. |
| `hours_graphic_right` | image | — | |
| `hours_rows` | repeater (0–14, `table` layout) | — | |
| `hours_footnote` | textarea (`new_lines: br`) | — | Small note below the list. |

### Row sub-fields

| Sub-field | Type | Notes |
| --- | --- | --- |
| `day_label` | text (required) | Display label, e.g. "Monday" or "Easter Sunday". |
| `time_range` | text (required) | "9am – 5:30pm" or "Closed". |
| `weekday_highlight` | select (`none`, `sun`–`sat`) | Choose `none` for special rows that should never highlight. |

## Behaviour notes

- "Today" is computed server-side via WordPress's site timezone — no
  client clock; identical render for every visitor.
- Inner shell uses `LayoutShell::INNER_READABLE_960` (narrow column).

## Related components

- [`info_block`](INFO-BLOCK.md) — same intro pattern but with 4-up
  icon cells instead of an hours list.
