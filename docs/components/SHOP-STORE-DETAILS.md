# Shop — store details (`shop_store_details`)

Three-column "Store Details" band on shop singles: contact (label +
phone), address (label + multi-line address), social (label +
Instagram handle linked to URL). Drops to a two-column layout when no
Instagram is set.

| | |
| --- | --- |
| Layout key | `shop_store_details` |
| ACF schema | [`app/Components/shop_store_details.php`](../../app/Components/shop_store_details.php) |
| Blade view | [`resources/views/components/shop-store-details.blade.php`](../../resources/views/components/shop-store-details.blade.php) |
| CSS partial | _(none)_ |
| Alpine module | _(none)_ |
| BEM root | `.shop-store-details` |

## When to use

Per-shop "Store Details" panel — a constraint-friendly alternative to
free-form prose for editors filling many shops. The labels are
configurable so the editor can localise.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `details_heading` | text | `Store Details` | |
| `details_heading_level` | select | `h2` | |
| `details_contact_label` | text | `Contact Number` | Column label. |
| `details_contact_phone` | text | — | |
| `details_address_label` | text | `Address` | Column label. |
| `details_address` | textarea (`new_lines: br`) | — | |
| `details_social_label` | text | `Social Media` | Column label. Leave Instagram fields blank to switch to a 2-column layout. |
| `details_instagram_url` | url | — | |
| `details_instagram_handle` | text | — | Include `@` if desired. |

## Behaviour notes

- Body tone defaults to the light-band override (defends light bands).
- Layout switches to two columns automatically when both Instagram
  fields are blank — the social column is omitted entirely rather
  than leaving an empty cell.

## Related components

- [`opening_hours`](OPENING-HOURS.md) — "centre opening hours" panel
  (different scope — centre-wide vs single shop).
- [`event_meta`](EVENT-META.md) — sibling layout for single events.
- [`career_detail`](CAREER-DETAIL.md) — sibling layout for single
  careers.
