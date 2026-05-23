# Leasing — agent grid (`leasing_agent_grid`)

Lettings trio — up to three agent columns (logo, name, phone, website)
on a lighter-cream band with vertical rules between columns.

| | |
| --- | --- |
| Layout key | `leasing_agent_grid` |
| ACF schema | [`app/Components/leasing_agent_grid.php`](../../app/Components/leasing_agent_grid.php) |
| Blade view | [`resources/views/components/leasing-agent-grid.blade.php`](../../resources/views/components/leasing-agent-grid.blade.php) |
| CSS partial | _(none — uses shared layout shell + prose utilities)_ |
| Alpine module | _(none)_ |
| BEM root | `.leasing-agent-grid` |
| Figma reference | `51:6524`–`51:6527` |

## When to use

Leasing / lettings pages that need a three-up agent contact grid below
intro copy.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `agents_heading` | text | `Lettings` | |
| `agents_heading_level` | select | `h2` | |
| `agents_intro` | textarea | — | Centred intro below the heading. |
| `leasing_agents` | repeater (0–3) | — | Agent columns, left-to-right on desktop. |

### Agent sub-fields

| Sub-field | Type | Notes |
| --- | --- | --- |
| `agent_logo` | image | ~150×39 brand art; scales proportionally. |
| `agent_name` | text | Required. |
| `agent_phone` | text | |
| `agent_website_url` | url | Full URL including `https://`. |
| `agent_website_label` | text | Optional display text; hostname when blank. |

## Behaviour notes

- Mirrors shop Store Details rhythm (lighter-cream band, vertical rules).
- Empty agent rows (no name, phone, or website) are skipped on render.

## Related components

- [`shop_store_details`](SHOP-STORE-DETAILS.md) — shop contact columns on singles.
- [`info_block`](INFO-BLOCK.md) — four-up icon cells for simpler contact patterns.
