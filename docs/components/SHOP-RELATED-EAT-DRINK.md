# Eat & drink — related venues (`shop_related_eat_drink`)

"More flavours to discover" row at the bottom of an eat & drink single.
Shows up to four directory cards using the same styling as the
`/eat-drink/` archive.

| | |
| --- | --- |
| Layout key | `shop_related_eat_drink` |
| ACF schema | [`app/Components/shop_related_eat_drink.php`](../../app/Components/shop_related_eat_drink.php) |
| Blade view | [`resources/views/components/shop-related-eat-drink.blade.php`](../../resources/views/components/shop-related-eat-drink.blade.php) |
| CSS partial | _(reuses `patterns/directory-archive.css` card styles)_ |
| Alpine module | [`resources/scripts/alpine/shop-related-shops.js`](../../resources/scripts/alpine/shop-related-shops.js) — mobile Splide carousel |
| BEM root | `.shop-related-eat-drink`; cards re-use `.directory-eat-drink-card` |

## When to use

Bottom-of-venue-single "more like this" row on eat & drink singles.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `eat_drink_related_heading` | text | `More flavours to discover` | |
| `eat_drink_related_heading_level` | select | `h2` | |
| `eat_drink_related_posts` | post_object (`culvers_eat_drink`, multi, max 4) | — | Optional picker; front end falls back to random published venues when empty. |
| `eat_drink_related_view_all_url` | url | — | Typically the eat & drink directory archive. |
| `eat_drink_related_view_all_label` | text | `View all` | |

## Behaviour notes

- Cards re-use `partials/directory-eat-drink-card.blade.php` for archive parity.
- Mobile: Splide carousel via shared `shopRelatedShops()` Alpine factory.
- Current venue is excluded when resolving random picks.

## Related components

- [`shop_related_shops`](SHOP-RELATED-SHOPS.md) — shop directory variant.
- [`three_card_block`](THREE-CARD-BLOCK.md) — three-up cards for non-directory content.

## Data migration

Renamed from shared `related_*` keys on 2026-05-23. Run
`scripts/migrations/2026-05-23-rename-related-layout-fields.php` on
existing databases.
