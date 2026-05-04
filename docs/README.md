# Culvers theme documentation

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
```

Start the site in Local once so `~/Library/Application Support/Local/run/<site-id>/conf/mysql/my.cnf` exists. Requires Local.app at `/Applications/Local.app`.

**Which URL to use (important):** Local listens on **`127.0.0.1:10013`** (shown in the UI) for **plain HTTP only**. Do **not** open **`https://culvers.local:10013/…`** — Chrome will show **`ERR_SSL_PROTOCOL_ERROR`** because nothing speaks TLS on that port. Use either **`https://culvers.local/…`** (no port; Local’s router terminates HTTPS) or **`http://culvers.local:10013/…`** if you intentionally hit the site container over HTTP. Prefer **Open site** / **WP Admin** in the Local app so the URL is always right.

**HTTPS / pink SSL banner:** If Local’s **Trust** fails only for Culvers, macOS often still needs the router cert trusted in the **System** keychain (Keychain-only imports can remain “not trusted” for TLS). Run **`./scripts/trust-culvers-local-ssl.sh`** once — it uses **`sudo security add-trusted-cert`** and asks for your Mac password. Then quit and reopen the browser.

## Quality checks

- **`npm run verify`** — ESLint, Prettier check, production build, **`composer lint`** (PHPCS), **`composer analyse`** (PHPStan).
- **`composer audit`** / **`npm audit`** — run in CI (`.github/workflows/verify.yml`); run locally before release if you are not using Actions.
