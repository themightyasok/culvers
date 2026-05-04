# Culvers theme documentation

| Doc | Purpose |
|-----|---------|
| [Deployment checklist](DEPLOYMENT.md) | Server requirements, build output paths, CI |
| [GSAP licensing](GSAP.md) | ScrollSmoother / Club GSAP |

## Assets and Tailwind CSS v4

- **Entry stylesheet:** `resources/styles/app.css` — imports Tailwind, `@config '../../tailwind.config.js'`, third-party CSS (e.g. Splide).
- **Design tokens:** `resources/styles/theme.tokens.css` — `@theme` blocks for `--color-*`, `--spacing-*`, `--font-*`, `--shadow-*`, etc. This file is the canonical token source; PHP reads `--color-*` via `App\Config\ThemeTokens` for ACF palettes.
- **JS bundle:** `resources/scripts/app.js` (Vite input).
- **Build:** `npm run build` writes to **`dist/css/app.css`** and **`dist/js/app.js`** (Vite `outDir`). The same script copies CSS/JS into **`app.css`**, **`css/app.css`**, and **`js/app.js`** so WordPress can load familiar paths. **`dist/`** is listed in `.gitignore`; commit **`app.css`** when you ship compiled CSS without generating `dist/` on the server.
- **Enqueue order:** `app/setup.php` prefers `dist/` assets, then `css/` / root fallbacks.

## Tailwind config vs tokens

- **`tailwind.config.js`** — content globs (template scan paths) and the Typography plugin. Not used for colour scales (v4 uses `@theme` in CSS).
- **PHP helpers** (`TailwindColors`, `Padding`, `Grid`, `Background`) map ACF values to utility class strings; details are in each class docblock.
