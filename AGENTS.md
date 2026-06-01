# Culvers — guidance for coding agents (Cursor, etc.)

This repository is the **Culver Square** WordPress site: theme **`culvers`** under `app/public/wp-content/themes/culvers`, backed by **Local (WP Engine)** for development. **Implementation is primarily done with AI-assisted tooling in Cursor** (agents compose theme PHP/Blade/JS/CSS and operate CLI against the same tree).

---

## WordPress & Local — always use the wrapper

**Never assume WP-CLI or MySQL are unavailable** from this environment until you have **actually tried** the Local helper.

- **WordPress root:** `app/public`
- **Wrapper:** `app/public/wp-content/themes/culvers/scripts/with-local-env.sh`  
  It resolves the Local site from `~/Library/Application Support/Local/sites.json`, sets `MYSQL_HOME`, `PHPRC`, and PATH (same idea as Local’s **Open site shell**), then runs your command.

From `app/public`:

```bash
./wp-content/themes/culvers/scripts/with-local-env.sh wp theme list --status=active
./wp-content/themes/culvers/scripts/with-local-env.sh mysql local -e "SELECT option_name, option_value FROM wp_options WHERE option_name='siteurl';"
./wp-content/themes/culvers/scripts/with-local-env.sh wp eval-file wp-content/themes/culvers/scripts/mega-menu-sync-previews.php
```

If something fails, capture the **real error** (missing Local, site stopped, path wrong)—do not default to “no database access.”

**Site URL:** see `docs/README.md` in the theme for HTTPS vs port quirks (`culvers.local`).

---

## Content model — components live in WordPress, not in “runtime seeds”

- **Source of truth for page layouts** is **ACF flexible content** stored on pages (e.g. field **`components`**), loaded with **`get_field`** and rendered via **`resources/views/partials/flexible-components.blade.php`**.
- **Templates do not merge PHP “seed” arrays at render time** for normal requests. What you see on the front end is **what is saved in the database** for that page.

Optional **one-shot CLI scripts** (run through `with-local-env.sh`) **write into the database**—they are **not** substitutes for proper CMS authoring:

| Script | Role |
|--------|------|
| `scripts/mega-menu-sync-previews.php` | Optional: writes `_culvers_mega_preview_*` meta on **existing** nav menu items |
| `scripts/directory-flexible-backfill.php` | Repairs empty/incomplete flexible `components` stacks on directory singles |

When describing work to humans: **we populate components properly in WP** (ACF / menus / media). Do not say “the seed runs the site.”

---

## Design & technical architecture (high level)

| Layer | Details |
|-------|---------|
| **CMS** | WordPress; **ACF** registers flexible layouts via **`App\ComponentRegistry`** reading **`app/Components/*.php`**. **`App\Config\ComponentPostTypes`** scopes which layouts appear per post type (pages vs each directory CPT). |
| **Templates** | **Blade** (`resources/views/`), rendered through theme Blade bootstrap (`app/blade-instance.php`, `functions.php`). Partials mirror layout keys to **`resources/views/components/{layout}.blade.php`**. |
| **Styling** | **Tailwind CSS v4** via **`resources/styles/app.css`** + **`@theme`** tokens in **`resources/styles/theme.tokens.css`** (colours, type ramp, **Canela** self-hosted headings, **Halyard Display** + **Commuter Sans** via Adobe Typekit — see `app/Assets/FrontendAssets.php`). **`App\Config\ThemeTokens`** / **`TailwindColors`** align ACF pickers with CSS tokens. |
| **Layout helpers** | **`Padding`**, **`Grid`**, **`Background`** map ACF → utilities; full-width / grid patterns documented in theme docs. |
| **Scripts** | **Vite** (`npm run build`): **`resources/scripts/app.js`** bundles Alpine + GSAP integrations; output copied to **`dist/`**, **`css/`**, **`js/`**, and root **`app.css`** / **`app.js`** per theme enqueue rules (`app/setup.php`). |
| **Motion** | **GSAP** stack (e.g. ScrollSmoother / ScrollTrigger—see theme **`docs/GSAP.md`**). |
| **Navigation** | **`App\Nav\PrimaryNav`** builds mega-nav trees from the **`primary_navigation`** menu location; hover previews use menu-item meta **`_culvers_mega_preview_attachment_id`** / **`_culvers_mega_preview_url`** (`NavMenuItemMeta`). Optional Figma bootstrap: **`CulverSquareFigmaPrimaryMenu`**. |
| **Directories** | Six CPTs — see theme **`docs/DIRECTORY-POST-TYPES.md`**. Archives use shared partials (`directory-archive-filter-body`, `directory-archive-chronological-body`) and **`partials/directory-card.blade.php`**. `/whats-on/` is a curated page, not the events archive. |
| **Design reference** | **Figma — Culver Square Website Design (Developer Release)** — file key `KoBl6rTY98YnvusBgKLx4A`. Typography mapping: **`docs/TYPOGRAPHY-SCALE.md`**. |

Further detail: **`app/public/wp-content/themes/culvers/docs/README.md`** (tokens, build, Local, quality commands).

---

## Verification

From theme directory: **`npm run verify`**, **`composer analyse`**, etc.—see theme **`docs/README.md`**.

---

## Deployment (20i)

**No live site yet** — only **20i staging** (`https://culversquare-co-uk.stackstaging.com`) and **Local** (`https://culvers.local`). Do not treat `culversquare.co.uk` as a deploy/verify target; legacy `www.culversquare.co.uk` is an old import source only.

Deploy via **`society-deploy`** at `/Users/admin/Work/Society/society-deploy`. Full playbook: **`.cursor/rules/culvers-20i-deploy.mdc`**.

---

## Rule of thumb for agents

Execute **`with-local-env.sh`** yourself for WP/DB tasks here. Treat **`AGENTS.md`** + **`.cursor/rules/culvers-agent-execution.mdc`** + **`.cursor/rules/culvers-20i-deploy.mdc`** as mandatory onboarding before claiming tooling limits.
