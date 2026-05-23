# Shop — related shops (`shop_related_shops`)

"More shops you might enjoy" row at the bottom of a shop single. Pick
up to four directory entries; the current shop is automatically
skipped on the front end.

| | |
| --- | --- |
| Layout key | `shop_related_shops` |
| ACF schema | [`app/Components/shop_related_shops.php`](../../app/Components/shop_related_shops.php) |
| Blade view | [`resources/views/components/shop-related-shops.blade.php`](../../resources/views/components/shop-related-shops.blade.php) |
| CSS partial | _(reuses `patterns/directory-archive.css` card styles)_ |
| Alpine module | [`resources/scripts/alpine/shop-related-shops.js`](../../resources/scripts/alpine/shop-related-shops.js) — mobile Splide carousel |
| BEM root | `.shop-related-shops`; cards re-use `.directory-shop-card` |

## When to use

Bottom-of-shop-single "more like this" picker. Editors can curate picks
via the post picker; the front end also supports random published shops
when no picks are saved.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `shops_related_heading` | text | `More shops you might enjoy` | |
| `shops_related_heading_level` | select | `h2` | |
| `shops_related_posts` | post_object (`culvers_shop`, multi, max 4) | — | Pick up to four. Current shop is skipped on render. |
| `shops_related_view_all_url` | url | — | Typically the shop directory archive. |
| `shops_related_view_all_label` | text | `View all` | |

## Behaviour notes

- Cards re-use the `partials/directory-shop-card.blade.php` partial
  (294 px tile height; same grid gap/columns as `/shops/` via `.directory-card-grid`).
  so visual + interaction parity with the directory archive is free.
- Current shop is skipped via a `get_the_ID()` comparison in the
  Blade — editors don't need to manually exclude.
- Mobile: Splide carousel via shared `shopRelatedShops()` Alpine factory.

## Related components

- [`shop_related_eat_drink`](SHOP-RELATED-EAT-DRINK.md) — eat & drink directory variant.
- [`three_card_block`](THREE-CARD-BLOCK.md) — three-up cards for
  non-shop content.
- [`horizontal_scroller`](HORIZONTAL-SCROLLER.md) — for >4 items.

## Data migration

Renamed from shared `related_*` keys on 2026-05-23. Run
`scripts/migrations/2026-05-23-rename-related-layout-fields.php` on
existing databases.
