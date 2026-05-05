# Culvers theme documentation

**Coding agents / Cursor:** Read the workspace **`AGENTS.md`** (repository root) first — Local WP-CLI wrapper, content model (ACF vs CLI populate scripts), Figma reference, and stack overview.

## Shops directory (CPT)

- **Archive:** `/shops/` — template `archive-culvers_shop.php` → Blade `archive-culvers-shop.blade.php` (filters + grid).
- **Admin:** **Shops** menu → custom post type **`culvers_shop`** with taxonomies **Shop categories** and **Retailer types**. Default terms are seeded once on load (Figma-aligned labels).
- **Demo retailers + hero / mega URLs:** optional CLI **`scripts/shops-directory-populate.php`** sideloads the Shopping Directory grid logos from the **Figma Developer Release** MCP asset URLs (same pattern as the homepage flexible populate script), assigns categories/types, saves **`Shop directory`** options (**`/shops/` hero slider**), and syncs the primary mega menu so **Shop** → `/shops/` and each category row → `/shops/?category={slug}`. Run via `./scripts/with-local-env.sh wp eval-file wp-content/themes/culvers/scripts/shops-directory-populate.php` from **`app/public`**. Theme **`init`** also runs **`ShopDirectoryNavSync::maybeSync()`** once (option versioned) so existing installs pick up those URLs without re-running the script.
- **Cards:** ACF **Shop listing fields** on each shop: logo + opening-hours line; fallback featured image + placeholder hours text.

| Doc                                   | Purpose                                     |
| ------------------------------------- | ------------------------------------------- |
| [Deployment checklist](DEPLOYMENT.md) | Server requirements, build output paths, CI |
| [GSAP licensing](GSAP.md)             | ScrollSmoother / Club GSAP                  |
| [Typography scale](TYPOGRAPHY-SCALE.md) | Figma → `text-xs` … `text-9xl` (single ramp) |

**Figma (developer release):** [Culver Square Website Design](https://www.figma.com/design/KoBl6rTY98YnvusBgKLx4A/Culver-Square-Website-Design--Developer-Release-?node-id=2-3) — file key `KoBl6rTY98YnvusBgKLx4A`. Header pill component instance `51:4999`; Shop mega dropdown frame `72:4967` (“Culver Square - Dropdown Menu - Shop”). Primary labels in file: Shop, Eat & Drink, Plan my visit, what’s on, Guest Services; utilities Centre Map, Getting Here.

**Primary mega menu from Figma:** When **Primary navigation** has no menu or no items, the theme installs **“Culver Square — Figma primary”** via `App\Nav\CulverSquareFigmaPrimaryMenu`. It downloads each mega hero image into the **Media Library** (via `download_url` / `media_handle_sideload`), stores **`_culvers_mega_preview_attachment_id`** on submenu rows, and caches source→attachment mapping in **`culvers_figma_panel_attachment_map`**. If Figma blocks server downloads, items keep `_culvers_mega_preview_url` until hydration succeeds on a later request. Disable auto-install: theme mod **`culvers_disable_figma_primary_menu_install`** = true.

## Assets and Tailwind CSS v4

- **Entry stylesheet:** `resources/styles/app.css` — imports Tailwind, `@config '../../tailwind.config.js'`, tokens, and third-party CSS (e.g. Splide).
- **Design tokens:** `resources/styles/theme.tokens.css` — `@theme` for `--color-*`, `--font-*`, **`text-xs`–`text-9xl`**, `--tracking-*`, `--shadow-*`, etc. **`App\Config\ThemeTokens`** parses `--color-*` hex values for ACF colour pickers. **`App\Helpers\TailwindColors`** builds text/bg utility dropdown choices from the same definitions (order follows the CSS file). **Typography mapping:** [TYPOGRAPHY-SCALE.md](TYPOGRAPHY-SCALE.md).
- **Spacing:** Layout helpers (`Padding`, `Grid`) and Blade markup use Tailwind’s **default spacing scale** (`pt-16`, `gap-x-6`, `px-5`, …). You can still tune the global scale by defining **`--spacing-*`** in `@theme` if needed.
- **Backgrounds:** **`App\Helpers\Background`** uses **`bg-{slug}`** when a solid `#hex` matches a theme colour; otherwise it keeps inline `background-color` / gradients / media as before.
- **Templates:** Blade files live under **`resources/views/`**; Tailwind scans them via **`tailwind.config.js`** `content` paths.
- **JS bundle:** `resources/scripts/app.js` (Vite input).
- **Build:** `npm run build` writes to **`dist/css/app.css`** and **`dist/js/app.js`**, then copies into **`app.css`**, **`css/app.css`**, and **`js/app.js`**. **`dist/`** is gitignored; **`app.css`** at the theme root is committed so installs without Node still get CSS.
- **Enqueue:** `app/setup.php` prefers **`dist/`**, then **`css/app.css`**, then root **`app.css`**; scripts mirror that pattern for **`dist/js`** vs **`js/`**.
- **Site Editor:** **`theme.json`** registers semantic colours and the sans font family for WordPress (alongside Tailwind-driven front-end styles).

## Tailwind config vs tokens

- **`tailwind.config.js`** — `content` globs (Blade, PHP under `app/`, scripts under `resources/scripts`) and the **`@tailwindcss/typography`** plugin. Colour scales live in CSS `@theme`, not in this file.
- **PHP helpers** (`TailwindColors`, `Padding`, `Grid`, `Background`) map ACF fields to utility classes; see each class for specifics.

## Local (WP Engine) — terminal without touching wp-config

Local runs MySQL bound to `127.0.0.1` **and** a site socket; PHP inside Local uses `PHPRC` so WordPress can connect with `DB_HOST` left as `localhost`. Host-side `wp` / Homebrew PHP usually fail because they do not see that environment.

Use the helper (matches your WordPress root to a site in `~/Library/Application Support/Local/sites.json`, then sets `MYSQL_HOME`, `PHPRC`, and PATH like Local’s **Open site shell**):

```bash
cd wp-content/themes/culvers   # or stay anywhere and pass absolute path to script
./scripts/with-local-env.sh wp theme list --status=active
./scripts/with-local-env.sh mysql local -e "SHOW TABLES;"
./scripts/with-local-env.sh wp eval-file wp-content/themes/culvers/scripts/homepage-populate-flexible.php
./scripts/with-local-env.sh wp eval-file wp-content/themes/culvers/scripts/shops-directory-populate.php
```

The **homepage** command creates/finds the **Home** page, sets **Settings → Reading** to that **static front page**, and saves the canonical **Page Components** stack (hero → three video cards → horizontal scroller → video → info grid → three posts → opening hours) via **`update_field('components', …)`** — the same payload as `HomepageFlexibleSeedData` / the former runtime defaults. Re-running replaces that flexible field.

The **shops** command upserts **Shops** demo posts from Figma MCP imagery, saves **`Shop directory`** hero slides (see WP admin → **Shop directory**), and syncs **Shop** mega-menu URLs to **`/shops/`** plus **`?category=`** deep links.

**Note:** do not put `declare(strict_types=1);` in scripts executed by **`wp eval-file`** with Local’s bundled PHP (it fatal-errors); the populate scripts omit it on purpose.

Start the site in Local once so `~/Library/Application Support/Local/run/<site-id>/conf/mysql/my.cnf` exists. Requires Local.app at `/Applications/Local.app`.

**Which URL to use (important):** Local listens on **`127.0.0.1:10013`** (shown in the UI) for **plain HTTP only**. Do **not** open **`https://culvers.local:10013/…`** — Chrome will show **`ERR_SSL_PROTOCOL_ERROR`** because nothing speaks TLS on that port. Use either **`https://culvers.local/…`** (no port; Local’s router terminates HTTPS) or **`http://culvers.local:10013/…`** if you intentionally hit the site container over HTTP. Prefer **Open site** / **WP Admin** in the Local app so the URL is always right.

**HTTPS / pink SSL banner:** If Local’s **Trust** fails only for Culvers, macOS often still needs the router cert trusted in the **System** keychain (Keychain-only imports can remain “not trusted” for TLS). Run **`./scripts/trust-culvers-local-ssl.sh`** once — it uses **`sudo security add-trusted-cert`** and asks for your Mac password. Then quit and reopen the browser.

## Quality checks

- **`npm run verify`** — ESLint, Prettier check, production build, **`composer lint`** (PHPCS), **`composer analyse`** (PHPStan).
- **`composer audit`** / **`npm audit`** — run in CI (`.github/workflows/verify.yml`); run locally before release if you are not using Actions.
