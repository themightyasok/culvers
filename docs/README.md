# Culvers theme documentation

**Coding agents / Cursor:** Read the workspace **`AGENTS.md`** (repository
root) first — Local WP-CLI wrapper, content model (ACF vs CLI populate
scripts), Figma reference, and stack overview. Then come back here for
the theme architecture and component contract.

## Where to look first

| If you want to… | Read |
| --- | --- |
| **Build, modify, or review a component** | [COMPONENT-AUTHORING.md](COMPONENT-AUTHORING.md) — canonical contract + `ComponentPostTypes` allowlists. |
| **Author Page Components in the CMS** | [EDITOR-FLEXIBLE-CONTENT.md](EDITOR-FLEXIBLE-CONTENT.md) — Main / Typography / Items / Mobile tabs. |
| **Look up how an existing component works** | [components/](components/) — one document per layout key. The catalogue lives in [components/README.md](components/README.md). |
| **Add a new directory CPT (post type + archive + single)** | [DIRECTORY-POST-TYPES.md](DIRECTORY-POST-TYPES.md) |
| **Pick the right Tailwind size for a Figma value** | [TYPOGRAPHY-SCALE.md](TYPOGRAPHY-SCALE.md) |
| **Animate something with GSAP** | [GSAP.md](GSAP.md) (and `resources/scripts/utils/gsap-manager.js`) |
| **Ship the theme to a server** | [DEPLOYMENT.md](DEPLOYMENT.md) + workspace `.cursor/rules/culvers-20i-deploy.mdc` (20i staging/live) |
| If you want to… | Read |
| --- | --- |
| **Run CLI populate / repair / live-sync scripts** | [SCRIPTS.md](SCRIPTS.md) |

| Doc                                             | Purpose                                                                  |
| ----------------------------------------------- | ------------------------------------------------------------------------ |
| [Flexible content — editors](EDITOR-FLEXIBLE-CONTENT.md) | Short guide for authors working in **Page Components**. |
| [Component catalogue](components/README.md)     | Index of every layout key with a one-liner.                              |
| [Directory post types](DIRECTORY-POST-TYPES.md) | Recipe for adding a new CPT (post type + taxonomies + archive + single). |
| [Typography scale](TYPOGRAPHY-SCALE.md)         | Figma → `text-xs` … `text-9xl` (single ramp).                            |
| [GSAP licensing](GSAP.md)                       | ScrollSmoother / Club GSAP.                                              |
| [Deployment checklist](DEPLOYMENT.md)           | Server requirements, build output paths, CI.                             |
| [CLI scripts](SCRIPTS.md)                       | Populate, nav sync, migrations (DB writers).                             |

## Theme architecture (orientation)

| Layer | Details |
|-------|---------|
| **CMS** | WordPress; **ACF** registers flexible layouts via `App\ComponentRegistry` reading `app/Components/*.php`. **`App\Config\ComponentPostTypes`** limits which layouts appear per post type. |
| **Templates** | **Blade** (`resources/views/`), rendered through theme Blade bootstrap (`app/blade-instance.php`, `functions.php`). Partials mirror layout keys to `resources/views/components/{layout}.blade.php`. |
| **Styling** | **Tailwind CSS v4** via `resources/styles/app.css` + `@theme` tokens in `resources/styles/theme.tokens.css` (colours, type ramp). `App\Config\ThemeTokens` / `TailwindColors` align ACF pickers with CSS tokens. |
| **Layout helpers** | `Padding`, `Grid`, `Background`, `LayoutShell` map ACF → utilities. |
| **Scripts** | **Vite** (`npm run build`): `resources/scripts/app.js` bundles Alpine + GSAP integrations; output copied to `dist/`, `css/`, `js/`, and root `app.css` / `app.js`. |
| **Motion** | **GSAP** stack (ScrollSmoother / ScrollTrigger — see [GSAP.md](GSAP.md)). |
| **Navigation** | `App\Nav\PrimaryNav` builds mega-nav trees from the `primary_navigation` menu location; hover previews use menu-item meta `_culvers_mega_preview_attachment_id` / `_culvers_mega_preview_url`. Optional Figma bootstrap: `App\Nav\CulverSquareFigmaPrimaryMenu`. |
| **Directories** | Six CPTs in `app/Directory/DirectoryPostTypes.php`: `culvers_shop` (`/shops/`), `culvers_eat_drink` (`/eat-drink/`), `culvers_event` (`/latest-events/`), `culvers_offer` (`/latest-offers/`), `culvers_news` (`/latest-news/`), `culvers_career` (careers archive). `/whats-on/` is a **page**, not an event archive. See [DIRECTORY-POST-TYPES.md](DIRECTORY-POST-TYPES.md). |
| **Design source** | **Figma — Culver Square Website Design (Developer Release)** — file key `KoBl6rTY98YnvusBgKLx4A`. |

## Shops directory (CPT) — the reference example

- **Archive:** `/shops/` — template `archive-culvers_shop.php` → Blade
  `archive-culvers-shop.blade.php` (filters + grid).
- **Admin:** **Shops** menu → custom post type **`culvers_shop`** with
  taxonomies **Shop categories** and **Retailer types**. Default terms
  are seeded once on load (Figma-aligned labels).
- **Demo retailers + hero / mega URLs:** managed in **WP admin** (Shops
  CPT, **Shop directory** options, primary nav). Theme `init` runs
  `ShopDirectoryNavSync::maybeSync()` once (option-versioned) so
  category mega links point at `/shops/?category={slug}`.
- **Cards:** ACF **Shop listing fields** on each shop: logo + opening
  hours line; fallback featured image + placeholder hours text.

All six directory CPTs follow the same pattern (archive + single + flexible components). See [DIRECTORY-POST-TYPES.md](DIRECTORY-POST-TYPES.md).

**Figma (developer release):**
[Culver Square Website Design](https://www.figma.com/design/KoBl6rTY98YnvusBgKLx4A/Culver-Square-Website-Design--Developer-Release-?node-id=2-3)
— file key `KoBl6rTY98YnvusBgKLx4A`. Header pill component instance
`51:4999`; Shop mega dropdown frame `72:4967`. Primary labels in
file: Shop, Eat & Drink, Plan my visit, what's on, Guest Services;
utilities Centre Map, Getting Here.

**Primary mega menu from Figma:** when **Primary navigation** has no
menu or no items, the theme installs **"Culver Square — Figma
primary"** via `App\Nav\CulverSquareFigmaPrimaryMenu`. It downloads
each mega hero image into the **Media Library** (via `download_url` /
`media_handle_sideload`), stores
`_culvers_mega_preview_attachment_id` on submenu rows, and caches
source → attachment mapping in
`culvers_figma_panel_attachment_map`. If Figma blocks server
downloads, items keep `_culvers_mega_preview_url` until hydration
succeeds on a later request. Disable auto-install: theme mod
`culvers_disable_figma_primary_menu_install` = true.

**Mega-menu hover preview CLIs (pick one job):**

| Script | When to use |
|--------|-------------|
| `scripts/mega-menu-sync-previews.php` | After **Figma bootstrap** installs: re-applies preview URLs from `CulverSquareFigmaPrimaryMenu` config / attachment map (`cliSyncDistinctChildPreviews`). |
| `scripts/mega-menu-distinct-previews.php` | **Generic installs:** siblings share the same preview URL or lack meta — assigns distinct **attachment IDs** from the media library (`MegaMenuDistinctPreviews`). |

Run both only when both problems apply; they solve overlapping but not identical cases.

## Assets and Tailwind CSS v4

- **Entry stylesheet:** `resources/styles/app.css` — imports Tailwind,
  `@config '../../tailwind.config.js'`, tokens, and third-party CSS
  (e.g. Splide).
- **Design tokens:** `resources/styles/theme.tokens.css` — `@theme`
  for `--color-*`, `--font-*`, the **stock Tailwind type ladder**
  (`text-xs`–`text-9xl`, body tier keeps Tailwind defaults, display
  tier retuned to Figma), `--container-8xl` (1440 px shell — extends
  Tailwind's `max-w-{xs..7xl}`), `--z-index-{60,70,80}` (extends
  `z-{10..50}`), and `--shadow-*`. Tracking and leading use stock
  Tailwind utilities (`tracking-tight…widest`, `leading-tight…loose`);
  for off-scale Figma values, prefer arbitrary utilities
  (`tracking-[0.22em]`, `leading-[26px]`). No semantic
  `text-display-*` / `text-caption` tokens — close-fit Figma values
  snap onto the ladder (≤ 2 px). `App\Config\ThemeTokens` parses
  `--color-*` hex values for ACF colour pickers. `App\Helpers\TailwindColors`
  builds text/bg utility dropdown choices from the same definitions.
  **Typography mapping:** [TYPOGRAPHY-SCALE.md](TYPOGRAPHY-SCALE.md).
- **Fonts:** **Canela** (headings — Light + Regular only) is self-hosted
  (`resources/styles/fonts-canela.css`, files under `resources/fonts/canela/`);
  Vite `base` includes `/dist/` so `@font-face` URLs resolve in production.
  **Halyard Display** and **Commuter Sans** come from **Adobe Fonts** /
  Typekit (`https://use.typekit.net/gqo7cfj.css`, enqueued in
  `app/Assets/FrontendAssets.php`). `theme.tokens.css` defines `--font-heading`,
  `--font-sans`, and `--font-label`; the block editor loads the same faces via
  `resources/styles/editor.css` and `theme.json`.
- **Spacing:** layout helpers (`Padding`, `Grid`) and Blade markup use
  Tailwind's default spacing scale (`pt-16`, `gap-x-6`, `px-5`, …).
  You can still tune the global scale by defining `--spacing-*` in
  `@theme` if needed.
- **Backgrounds:** `App\Helpers\Background` uses `bg-{slug}` when a
  solid `#hex` matches a theme colour; otherwise it keeps inline
  `background-color` / gradients / media as before.
- **Templates:** Blade files live under `resources/views/`; Tailwind
  scans them via `tailwind.config.js` `content` paths.
- **JS bundle:** `resources/scripts/app.js` (Vite input).
- **Build:** `npm run build` writes to `dist/css/app.css` and
  `dist/js/app.js`, then copies into `app.css`, `css/app.css`, and
  `js/app.js`. `dist/` is gitignored; `app.css` at the theme root is
  committed so installs without Node still get CSS.
- **Enqueue:** `app/setup.php` prefers `dist/`, then `css/app.css`,
  then root `app.css`; scripts mirror that pattern for `dist/js`
  vs `js/`.
- **Site Editor:** `theme.json` registers semantic colours and font
  presets (Canela heading stack, Halyard body, Commuter-style labels)
  for the block editor — alongside Tailwind-driven front-end utilities.

## Tailwind config vs tokens

- **`tailwind.config.js`** — `content` globs (Blade, PHP under `app/`,
  scripts under `resources/scripts`) and the `@tailwindcss/typography`
  plugin. Colour scales live in CSS `@theme`, not in this file.
- **PHP helpers** (`TailwindColors`, `Padding`, `Grid`, `Background`)
  map ACF fields to utility classes; see each class for specifics.

## Local (WP Engine) — terminal without touching wp-config

Local runs MySQL bound to `127.0.0.1` **and** a site socket; PHP
inside Local uses `PHPRC` so WordPress can connect with `DB_HOST` left
as `localhost`. Host-side `wp` / Homebrew PHP usually fail because
they do not see that environment.

Use the helper (matches your WordPress root to a site in
`~/Library/Application Support/Local/sites.json`, then sets
`MYSQL_HOME`, `PHPRC`, and PATH like Local's **Open site shell**):

```bash
cd app/public   # WordPress root
./wp-content/themes/culvers/scripts/with-local-env.sh wp theme list --status=active
./wp-content/themes/culvers/scripts/with-local-env.sh mysql local -e "SHOW TABLES;"
./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file wp-content/themes/culvers/scripts/mega-menu-sync-previews.php
```

Content lives in **WordPress** (ACF flexible fields, post meta, options,
media library). Use WP admin or targeted CLI scripts under
`scripts/` to change it — there is no theme-side seed layer that runs
at request time.

> Do not put `declare(strict_types=1);` in scripts executed by
> `wp eval-file` with Local's bundled PHP (it fatal-errors); the
> populate scripts omit it on purpose.

Start the site in Local once so
`~/Library/Application Support/Local/run/<site-id>/conf/mysql/my.cnf`
exists. Requires Local.app at `/Applications/Local.app`.

**Which URL to use (important):** Local listens on `127.0.0.1:10013`
(shown in the UI) for **plain HTTP only**. Do **not** open
`https://culvers.local:10013/…` — Chrome will show
`ERR_SSL_PROTOCOL_ERROR` because nothing speaks TLS on that port. Use
either `https://culvers.local/…` (no port; Local's router terminates
HTTPS) or `http://culvers.local:10013/…` if you intentionally hit the
site container over HTTP. Prefer **Open site** / **WP Admin** in the
Local app so the URL is always right.

**HTTPS / pink SSL banner:** if Local's **Trust** fails only for
Culvers, macOS often still needs the router cert trusted in the
**System** keychain (Keychain-only imports can remain "not trusted"
for TLS). Run `./scripts/trust-culvers-local-ssl.sh` once — it uses
`sudo security add-trusted-cert` and asks for your Mac password. Then
quit and reopen the browser.

## Quality checks

- **`npm run verify`** — ESLint, Prettier check, production build,
  `composer lint` (PHPCS), `composer analyse` (PHPStan),
  `npm run check:blade-forks` (component Blade name parity).
- **`composer audit`** / **`npm audit`** — run in CI
  (`.github/workflows/verify.yml`); run locally before release if you
  are not using Actions.
