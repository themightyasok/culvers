# `scripts/` — CLI utilities

One-shot WP-CLI scripts and shell helpers that operate on the running Culvers
site (database + media library). All PHP scripts are run through
**`with-local-env.sh`** so PHPRC, MYSQL_HOME, and PATH match Local by Flywheel
— never call `wp` or `mysql` directly from a shell that hasn't been wrapped.

## Standard run pattern

From the WordPress root (`app/public`):

```bash
./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file \
  wp-content/themes/culvers/scripts/<script-name>.php [args...]
```

Most scripts that mutate data accept a `dry-run` (or `--execute`) toggle as
their first positional arg — start there before running for real. Read the
top-of-file docblock for each script's exact contract.

---

## Environment / preflight

| Script | What it does |
|---|---|
| `with-local-env.sh` | Resolves the Local site from `~/Library/Application Support/Local/sites.json`, exports `MYSQL_HOME` / `PHPRC` / PATH, then runs whatever you pass it. Use as a wrapper for **every** `wp …` and `mysql …` call. |
| `trust-culvers-local-ssl.sh` | Adds Local's self-signed cert to the macOS keychain so `https://culvers.local` doesn't trip curl / browser warnings. |
| `check-component-blade-forks.php` | Pre-commit/CI guard. Fails when shared component Blade templates branch on post-type or query context (forks should live in PHP, not the view). |

## Deploy

| Script | What it does |
|---|---|
| `deploy-new-shops-staging.sh` | Wrapper around `society-deploy` for the three new-shop seed (Clarks / Fraser Hart / Colchester Aesthetics). Theme code + 9 allowlisted media files + create-only shop import. Defaults to dry-run; pass `--execute` to ship. **Does not** push the database — see `.cursor/rules/culvers-20i-deploy.mdc` for the full deploy playbook. |

---

## Audits (read-only)

| Script | What it does |
|---|---|
| `audit-media-library-usage.php` | Scans every attachment ID against post meta, ACF options, nav-menu meta, theme defaults, and `wp_options` (incl. **bare numeric IDs** — important; the original audit missed those). Writes `storage/media-library-audit.json` with `unused`, `parent-only`, and `referenced` buckets. Always run this before deleting media. |
| `scan-broken-images.php` | Crawls a sampled set of published URLs; reports `<img src>` / `srcset` / CSS-background URLs that 404 or return non-image content. |
| `scan-broken-images-empty-src.php` | Faster narrow scan — finds rendered `<img>` elements with empty `src=""` (a symptom of attachments deleted out from under live posts). |

## Media restore & repair (destructive — read header first)

| Script | What it does |
|---|---|
| `delete-unused-media-from-audit.php` | Reads a JSON report from `audit-media-library-usage.php` and deletes the listed attachments. **Local only.** Pass the absolute report path as the script arg. |
| `restore-missing-media-from-staging-backup.php` | Re-registers attachment posts whose files were deleted but are still referenced by ACF options or post meta. Reads attachment rows from the staging DB backup SQL and pulls the file bytes from the staging uploads zip. |
| `repair-zero-byte-uploads-from-backup.php` | Replaces `wp-content/uploads/**` files that exist on disk but are 0 bytes (corruption from earlier restore attempts). Sources from the same staging uploads zip. |
| `fix-svg-attachment-meta.php` | Patches SVG attachments whose `_wp_attachment_metadata` width/height are 0 (WP can't introspect SVG `viewBox`). Required so `App\Helpers\Image::render()` emits intrinsic dimensions. |
| `fix-figma-svg-aspect.php` | Rewrites `preserveAspectRatio="none"` + `width="100%" height="100%"` artifacts in Figma-exported SVGs back to a sensible `viewBox` + ratio. |
| `fix-shop-jpg-svg-logos.php` | Repairs shop logos imported with a `.jpg` extension whose bytes are actually SVG (Figma CDN URL mismatch). Re-extensions and updates attachment MIME. |
| `upload-homepage-info-icons.php` | Sideloads the 8 hand-drawn homepage info-block icons from Figma and wires them onto the info_block flexible row. Idempotent. |

---

## Directory CPT — sync from live & repair

The `culversquare.co.uk` legacy site is the **import source only**. These
scripts pull retailer copy / store details / opening hours into the local CPTs
and never write back to live.

| Script | What it does |
|---|---|
| `shops-sync-from-live.php` | Creates missing `culvers_shop` posts, then updates intro/split copy, hero logo + image, store details, opening hours, and taxonomies. Supports `dry-run`. |
| `shops-sync-live-intro-copy.php` | Narrower variant — only `shop_intro_block.intro_body` + `shop_split_highlight` copy. Doesn't touch hero, hours, or related shops. |
| `shops-sync-intro-cta.php` | Sets `shop_intro_block` CTA label + URL from live retailer pages (with hardcoded fallbacks). |
| `shops-store-details-populate.php` | Fills the `shop_store_details` row on every published `culvers_shop` with researched phone, address, and Instagram. |
| `shops-repair-opening-hours.php` | Repairs retailer opening hours (heading, context, day rows). Optional slug filter. |
| `shops-repair-deploy-logos.php` | Registers on-disk SVG/PNG logos and writes `shop_logo` + hero `hero_logo` for the `ShopLiveSync::DEPLOY_MEDIA_BY_SLUG` slugs. Optional `fix-cosmic-tattoo-card`. |
| `shops-repair-centre-map.php` | Repairs empty `centre_map` repeaters on shop / eat-drink singles. Defaults to the five deploy shops + cosmic-tattoo. |
| `eat-drink-sync-from-live.php` | Eat & Drink equivalent of `shops-sync-from-live` — intro, split copy, hero logo, store details. |
| `directory-offers-whats-on-import.php` | Imports live offers + what's-on content into the local `culvers_offer` / `culvers_event` CPTs. |
| `directory-flexible-backfill.php` | Persists the standard Page Components stack on directory singles whose flexible content was empty. |
| `strip-event-meta-flexible-row.php` | Removes deprecated `event_meta` flexible rows from any directory singles that still have them. |
| `sync-directory-filter-terms.php` | Re-runs `ShopTaxonomySeeder::syncNow()` + `EatDrinkTaxonomySeeder::syncNow()` and re-assigns Eat & Drink venue types after deploys. |

## Homepage

| Script | What it does |
|---|---|
| `homepage-brand-logos-sync.php` | Sideloads optically-normalized brand SVGs and points the homepage `horizontal_scroller` row at them. Run after `python3 scripts/normalize-brand-logos.py`. |
| `homepage-brand-scroller-sync.php` | Patches `horizontal_scroller` copy/CTA fields in place and dedupes Accessorize + London logo tiles. Doesn't replace the full homepage stack. |
| `homepage-three-card-mobile-videos-sync.php` | Wires homepage three-card mobile video fields to existing media-library landscape crops. Idempotent. |

## Pages / one-shot fixes

| Script | What it does |
|---|---|
| `clone-commercialisation-opportunities-page.php` | Clones `/leasing-opportunities/` → `/commercialisation-opportunities/` keeping the same flexible components stack. |
| `fix-senior-supervisor-career-layout.php` | One-shot: aligns the Senior Supervisor career single's flexible rows with Figma `51:6450`. |

## Navigation / mega menu

| Script | What it does |
|---|---|
| `nav-sync-pages.php` | Wires the assigned WP nav menus (primary mega + three footer locations) to the live page set. Creates stub pages for legal/accessibility surfaces that don't exist yet. |
| `rebuild-primary-nav-menu.php` | Rewrites the `primary_navigation` menu from `App\Nav\CulverSquareFigmaPrimaryMenu`. Safe to re-run; deletes every item then recreates. Use when Appearance → Menus drifts out of sync with the rendered header. |
| `mega-menu-sync-previews.php` | **Figma-bootstrap path** — re-applies hover preview URLs from `CulverSquareFigmaPrimaryMenu::cliSyncDistinctChildPreviews()`. Use after the Figma-seeded primary menu exists. |
| `mega-menu-distinct-previews.php` | **Generic media-library path** — when sibling submenu rows share the same resolved preview URL (or lack meta), writes distinct `_culvers_mega_preview_attachment_id` values from the library. |

---

## Authoring new scripts

* Top of file: docblock with one-line summary + the exact `with-local-env.sh wp eval-file …` invocation, plus any `dry-run` / argument forms.
* Guard with `if (! defined('WP_CLI') || ! WP_CLI) { exit(1); }` so the file can't be hit from a web request.
* `declare(strict_types=1);` (matches `app/Components/*.php`).
* No `@phpstan-ignore-file` unless the script genuinely can't satisfy strict typing (e.g. ad-hoc disaster-recovery shims).
* Default to dry-run; require an explicit flag to mutate. Print a per-row summary so the operator can tail the output and stop the run.
* Use `App\Helpers\Image::*`, `App\Directory\*` factories, and the existing CPT seeders rather than reaching into `$wpdb` directly.
