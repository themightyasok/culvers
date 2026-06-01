# Theme CLI scripts

Operational scripts live in `scripts/`. Run from WordPress root (`app/public`) via the Local wrapper:

```bash
cd /path/to/app/public
./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file wp-content/themes/culvers/scripts/<script>.php [args]
```

> Scripts executed by `wp eval-file` intentionally omit `declare(strict_types=1)` — Local's bundled PHP can fatal on strict files.

## Navigation & mega menu

| Script | Purpose |
| --- | --- |
| `mega-menu-sync-previews.php` | Re-apply Figma preview attachment IDs/URLs on existing menu items |
| `mega-menu-distinct-previews.php` | Assign distinct preview images when siblings share one URL |
| `nav-sync-pages.php` | Sync primary nav placeholder URLs + header utility shortcuts |
| `rebuild-primary-nav-menu.php` | Rebuild primary menu from Figma structure (destructive — confirm first) |

## Homepage populate (writes DB)

| Script | Purpose |
| --- | --- |
| `homepage-brand-scroller-sync.php` | Sync homepage brand logo scroller items |
| `homepage-brand-logos-sync.php` | Brand logo attachments for scroller |
| `homepage-three-card-mobile-videos-sync.php` | Mobile video fields on three-card rows |
| `upload-homepage-info-icons.php` | Info block icon attachments |

## Directory / shops / eat & drink

| Script | Purpose |
| --- | --- |
| `shops-sync-from-live.php` | Pull shop singles from live `/retailers/{slug}/` (dry-run flags) |
| `eat-drink-sync-from-live.php` | Eat & drink venue sync from live |
| `shops-sync-live-intro-copy.php` | Intro/split copy from live retailer pages |
| `shops-sync-intro-cta.php` | Repair intro CTA labels/URLs from live website links |
| `shops-store-details-populate.php` | Populate store details block fields |
| `shops-repair-centre-map.php` | Repair empty centre_map repeaters on shop singles |
| `shops-repair-opening-hours.php` | Repair retailer opening hours from live |
| `shops-repair-deploy-logos.php` | Logo + hero repair for deploy media map |
| `directory-flexible-backfill.php` | Backfill flexible `components` stacks on directory singles |
| `directory-offers-whats-on-import.php` | Import offers/events/news demo content |
| `sync-directory-filter-terms.php` | Sync filter taxonomy terms |
| `strip-event-meta-flexible-row.php` | Remove legacy `event_meta` flexible rows from singles |

## One-off fixes / content

| Script | Purpose |
| --- | --- |
| `clone-commercialisation-opportunities-page.php` | Clone commercialisation page structure |
| `fix-senior-supervisor-career-layout.php` | Repair a specific career single layout |
| `fix-shop-jpg-svg-logos.php` | Repair shop logo attachment formats |
| `fix-svg-attachment-meta.php` | SVG attachment metadata repair |
| `fix-figma-svg-aspect.php` | Figma SVG aspect ratio fix |

## Quality / migrations

| Script | Purpose |
| --- | --- |
| `check-component-blade-forks.php` | CI: Blade name parity (`npm run check:blade-forks`) |
| `scripts/migrations/*.php` | One-shot DB migrations — read file header before running |

Notable migrations: `2026-05-rename-acf-fields.php`, `2026-05-19-component-display-variants.php`, flexible chrome/visibility cleanup scripts.

## Rules

- **Populate scripts write the database** — they are not runtime seeds.
- Prefer **dry-run** modes when a script supports them (`shops-sync-from-live.php`, etc.).
- Never run live-sync scripts against production without explicit confirmation.
- Component registry cache auto-clears on deploy when `app/Components/*.php` changes; no script required.
