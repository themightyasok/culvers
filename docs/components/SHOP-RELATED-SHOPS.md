# Shop — related shops (`shop_related_shops`)

"More shops you might enjoy" row at the bottom of a shop single. Pick
up to four directory entries; the current shop is automatically
skipped on the front end.

| | |
| --- | --- |
| Layout key | `shop_related_shops` |
| ACF schema | [`app/Components/shop_related_shops.php`](../../app/Components/shop_related_shops.php) |
| Blade view | [`resources/views/components/shop-related-shops.blade.php`](../../resources/views/components/shop-related-shops.blade.php) |
| CSS partial | _(reuses `directory-archive.css` card styles)_ |
| Alpine module | _(none)_ |
| BEM root | `.shop-related-shops`; cards re-use `.directory-shop-card` |

## When to use

Bottom-of-shop-single "more like this" picker. Editors curate the
selection rather than auto-pulling related shops by category — keeps
the experience editorial.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `related_heading` | text | `More shops you might enjoy` | |
| `related_heading_level` | select | `h2` | |
| `related_shop_posts` | post_object (`culvers_shop`, multi, max 4) | — | Pick up to four. Current shop is skipped on render. |
| `related_view_all_url` | url | — | Typically the shop directory archive. |
| `related_view_all_label` | text | `View all` | |

## Behaviour notes

- Cards re-use the `partials/directory-shop-card.blade.php` partial
  so visual + interaction parity with the directory archive is free.
- Current shop is skipped via a `get_the_ID()` comparison in the
  Blade — editors don't need to manually exclude.

## Related components

- [`three_card_block`](THREE-CARD-BLOCK.md) — three-up cards for
  non-shop content.
- [`horizontal_scroller`](HORIZONTAL-SCROLLER.md) — for >4 items.
